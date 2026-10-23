<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\VehicleDocuments;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VehicleDocumentFollowupController extends Controller
{
    private function authorizeDocument(string $document, bool $edit = false): void
    {
        abort_unless(Gate::allows('vehicle_documents_area_access') && Gate::allows($edit ? 'vehicle_edit' : 'vehicle_access'), 403);
        abort_unless(array_key_exists($document, VehicleDocuments::applicabilityDocuments()), 404);
    }

    private function uploadLimitKb(): int
    {
        $limits = [10240];
        foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
            $value = trim(ini_get($setting));
            $bytes = (float) $value * match (strtolower(substr($value, -1))) {
                'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1,
            };
            if ($bytes > 0) {
                $limits[] = max(1, (int) floor(($bytes - ($setting === 'post_max_size' ? 65536 : 0)) / 1024));
            }
        }

        return min($limits);
    }

    public function show(Vehicle $vehicle, string $document)
    {
        $this->authorizeDocument($document);
        $uploadLimitKb = $this->uploadLimitKb();
        $followup = $vehicle->documentFollowups()->where('document', $document)->first();
        $users = User::with('roles.permissions')->orderBy('name')->get()->filter(fn ($u) => Gate::forUser($u)->allows('vehicle_access') && Gate::forUser($u)->allows('vehicle_documents_area_access') && Gate::forUser($u)->allows('vehicle_edit'));
        // Select Rita only when unambiguous and already authorized; never grant access.
        $ritas = $users->filter(fn ($u) => preg_match('/^rita(?:\s|$)/iu', trim($u->name)));
        $defaultResponsible = $ritas->count() === 1 ? $ritas->first()->id : null;
        $files = VehicleDocuments::files($vehicle)->filter(fn ($file) => VehicleDocuments::matches($file, $document));
        if (request()->is('api/v1/backoffice/*')) {
            return response()->json(['vehicle_id' => $vehicle->id, 'document' => $document, 'followup' => $followup,
                'responsible_options' => $users->map->only(['id', 'name'])->values(), 'default_responsible_id' => $defaultResponsible,
                'upload_max_kb' => $uploadLimitKb,
                'files' => $files->map(fn ($f) => ['id' => $f->id, 'file_name' => $f->file_name])->values()]);
        }

        return view('admin.vehicles.document-followup', compact('vehicle', 'document', 'followup', 'users', 'defaultResponsible', 'files', 'uploadLimitKb'));
    }

    public function store(Request $request, Vehicle $vehicle, string $document)
    {
        $this->authorizeDocument($document, true);
        $data = $request->validate([
            'action' => ['required', Rule::in(['contact', 'reschedule', 'complete', 'upload'])],
            'version' => ['required', 'integer', 'min:0'],
            'assigned_to_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'contact' => ['required', 'string', 'max:191'],
            'requested_on' => ['required', 'date_format:Y-m-d'],
            'next_contact_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:requested_on'],
            'note' => ['required_unless:action,upload', 'nullable', 'string', 'max:1000'],
            'document_file' => ['required_if:action,upload', 'prohibited_unless:action,upload', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.$this->uploadLimitKb()],
            'media_id' => ['required_if:action,complete', 'nullable', 'integer'],
            'confirmed' => ['exclude_unless:action,complete', 'required', 'accepted'],
        ], [
            'document_file.required_if' => 'Escolha o ficheiro antes de carregar em Anexar cópia digital.',
            'document_file.prohibited_unless' => 'Carregue primeiro em Anexar cópia digital para guardar o ficheiro escolhido.',
            'document_file.mimes' => 'Escolha um ficheiro PDF, JPG, PNG ou WebP.',
            'document_file.max' => 'O ficheiro ultrapassa o tamanho máximo indicado no formulário.',
            'document_file.uploaded' => 'Não foi possível enviar o ficheiro. Confira o tamanho e volte a tentar.',
        ]);
        $responsible = User::findOrFail($data['assigned_to_id']);
        abort_unless(Gate::forUser($responsible)->allows('vehicle_access') && Gate::forUser($responsible)->allows('vehicle_edit') && Gate::forUser($responsible)->allows('vehicle_documents_area_access'), 422, 'O responsável tem de ter acesso à documentação.');
        $uploaded = null;
        try {
            DB::transaction(function () use ($request, $vehicle, $document, $data, &$uploaded) {
                $locked = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
                abort_if(VehicleDocuments::notApplicable($locked, $document), 422, 'Este documento está dispensado. Reveja primeiro o Não se aplica.');
                $f = $locked->documentFollowups()->where('document', $document)->lockForUpdate()->firstOrNew();
                abort_unless((int) ($f->version ?? 0) === (int) $data['version'], 409, 'O acompanhamento mudou. Atualize a página antes de gravar.');
                $f->fill(collect($data)->only(['assigned_to_id', 'contact', 'requested_on', 'next_contact_on', 'note'])->all());
                $f->document = $document;
                if ($data['action'] === 'upload') {
                    $type = $document === 'vehicle_ownership_title' ? 'dua' : $document;
                    $collection = $type === 'dav' ? 'dav' : (in_array($type, ['dua', 'inspection']) ? 'documents' : 'additional_documents');
                    $source = $request->file('document_file');
                    $name = \Illuminate\Support\Str::slug(pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME));
                    $name = mb_substr($name ?: 'documento', 0, 100).'-'.\Illuminate\Support\Str::random(8).'.'.$source->extension();
                    $uploaded = $locked->addMedia($source)->usingFileName($name)
                        ->withCustomProperties(['document_classification' => [
                            'type' => $type, 'user_id' => $request->user()->id, 'classified_at' => now()->toIso8601String(),
                        ]])->toMediaCollection($collection, 'public');
                    $f->status = $f->status ?? 'open';
                    $f->record('upload', 'Cópia digital anexada: '.$uploaded->file_name.'. '.($data['note'] ?? ''));

                    return;
                }
                $f->status = 'open';
                $f->media_id = null;
                if ($data['action'] === 'complete') {
                    $file = $locked->media()->whereKey($data['media_id'])->lockForUpdate()->first();
                    abort_unless($file && VehicleDocuments::matches($file, $document), 422, 'Escolha uma cópia digital identificada para este documento e esta viatura.');
                    $f->media_id = $file->id;
                    $f->status = 'completed';
                }
                $f->record($data['action'], $data['note']);
            });
        } catch (\Throwable $error) {
            // Database rollback does not remove the file already written to storage.
            if ($uploaded) {
                app(\Spatie\MediaLibrary\MediaCollections\Filesystem::class)->removeAllFiles($uploaded);
            }
            throw $error;
        }

        return redirect()->route('admin.vehicles.documents.followup', [$vehicle, $document])->with('message', $uploaded ? 'Cópia digital guardada. Abra o ficheiro e confira o conteúdo antes de concluir.' : 'Acompanhamento guardado.')->with('uploaded_media_id', $uploaded?->id);
    }
}

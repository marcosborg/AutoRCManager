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

    public function show(Vehicle $vehicle, string $document)
    {
        $this->authorizeDocument($document);
        $followup = $vehicle->documentFollowups()->where('document', $document)->first();
        $users = User::with('roles.permissions')->orderBy('name')->get()->filter(fn ($u) => Gate::forUser($u)->allows('vehicle_access') && Gate::forUser($u)->allows('vehicle_documents_area_access') && Gate::forUser($u)->allows('vehicle_edit'));
        // Select Rita only when unambiguous and already authorized; never grant access.
        $ritas = $users->filter(fn ($u) => preg_match('/^rita(?:\s|$)/iu', trim($u->name)));
        $defaultResponsible = $ritas->count() === 1 ? $ritas->first()->id : null;
        $files = VehicleDocuments::files($vehicle)->filter(fn ($file) => VehicleDocuments::matches($file, $document));
        if (request()->is('api/v1/backoffice/*')) {
            return response()->json(['vehicle_id' => $vehicle->id, 'document' => $document, 'followup' => $followup,
                'responsible_options' => $users->map->only(['id', 'name'])->values(), 'default_responsible_id' => $defaultResponsible,
                'files' => $files->map(fn ($f) => ['id' => $f->id, 'file_name' => $f->file_name])->values()]);
        }

        return view('admin.vehicles.document-followup', compact('vehicle', 'document', 'followup', 'users', 'defaultResponsible', 'files'));
    }

    public function store(Request $request, Vehicle $vehicle, string $document)
    {
        $this->authorizeDocument($document, true);
        $data = $request->validate([
            'action' => ['required', Rule::in(['contact', 'reschedule', 'complete'])],
            'version' => ['required', 'integer', 'min:0'],
            'assigned_to_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'contact' => ['required', 'string', 'max:191'],
            'requested_on' => ['required', 'date_format:Y-m-d'],
            'next_contact_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:requested_on'],
            'note' => ['required', 'string', 'max:1000'],
            'media_id' => ['required_if:action,complete', 'nullable', 'integer'],
            'confirmed' => ['exclude_unless:action,complete', 'required', 'accepted'],
        ]);
        $responsible = User::findOrFail($data['assigned_to_id']);
        abort_unless(Gate::forUser($responsible)->allows('vehicle_access') && Gate::forUser($responsible)->allows('vehicle_edit') && Gate::forUser($responsible)->allows('vehicle_documents_area_access'), 422, 'O responsável tem de ter acesso à documentação.');
        DB::transaction(function () use ($vehicle, $document, $data) {
            $locked = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            abort_if(VehicleDocuments::notApplicable($locked, $document), 422, 'Este documento está dispensado. Reveja primeiro o Não se aplica.');
            $f = $locked->documentFollowups()->where('document', $document)->lockForUpdate()->firstOrNew();
            abort_unless((int) ($f->version ?? 0) === (int) $data['version'], 409, 'O acompanhamento mudou. Atualize a página antes de gravar.');
            $f->fill(collect($data)->only(['assigned_to_id', 'contact', 'requested_on', 'next_contact_on', 'note'])->all());
            $f->document = $document;
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

        return redirect()->route('admin.vehicles.documents.followup',[$vehicle, $document])->with('message','Acompanhamento guardado.');
    }
}

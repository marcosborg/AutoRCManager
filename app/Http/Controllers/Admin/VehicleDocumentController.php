<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Support\VehicleDocuments;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VehicleDocumentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Gate::allows('vehicle_access') && Gate::allows('vehicle_documents_area_access'), 403);
        $filters = $request->validate([
            'license' => ['nullable', 'string', 'max:100'],
            'destination_company' => ['nullable', 'string', Rule::in(array_merge(array_keys(Vehicle::DESTINATION_COMPANIES), ['unassigned']))],
            'scope' => ['nullable', Rule::in(['stock', 'all'])],
            'reason' => ['nullable', Rule::in(['any', 'checks', 'unidentified'])],
        ]);
        $scope = $filters['scope'] ?? 'stock';
        $reason = $filters['reason'] ?? 'any';
        $vehicles = null;
        if ($scope) {
            $query = Vehicle::query()->with(['brand', 'general_state', 'documentFollowups.responsible'])
                ->withCount(['media as unidentified_count' => fn ($media) => VehicleDocuments::unidentifiedQuery($media)]);
            if ($scope === 'stock') $query->inStock();
            if (!empty($filters['destination_company'])) $query->forDestinationCompany($filters['destination_company']);
            if (trim($filters['license'] ?? '') !== '') $query->searchByLicense(trim($filters['license']));
            $query->where(function ($pending) use ($reason) {
                if ($reason !== 'unidentified') {
                    $pending->where(function ($checks) {
                        VehicleDocuments::pendingChecksQuery($checks);
                    });
                }
                if ($reason === 'any') {
                    $pending->orWhereHas('documentFollowups', fn ($q) => $q->whereIn('status',['open','review']));
                }
                if ($reason !== 'checks') {
                    $pending->orWhereHas('media', fn ($media) => VehicleDocuments::unidentifiedQuery($media));
                }
            });
            $vehicles = $query->orderBy('license')->orderBy('id')->paginate(25)->withQueryString();
        }
        if ($request->is('api/v1/backoffice/*')) {
            $vehicles?->through(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'license' => $vehicle->license,
                'foreign_license' => $vehicle->foreign_license,
                'destination_company' => $vehicle->destination_company,
                'pending_checks' => VehicleDocuments::pendingChecks($vehicle),
                'unidentified_count' => $vehicle->unidentified_count,
            ]);
            return response()->json(compact('vehicles', 'scope', 'reason', 'filters'));
        }
        return view('admin.vehicles.document-review', compact('vehicles', 'scope', 'reason', 'filters'));
    }

    public function applicability(Request $request, Vehicle $vehicle, string $document)
    {
        abort_unless(Gate::allows('vehicle_edit') && Gate::allows('vehicle_documents_area_access'), 403);
        abort_unless(array_key_exists($document, VehicleDocuments::applicabilityDocuments()), 404);
        if (is_string($request->input('reason'))) {
            $request->merge(['reason' => trim($request->input('reason'))]);
        }
        $data = $request->validate([
            'status' => ['required', Rule::in(['not_applicable', 'review'])],
            'reason' => ['required', 'string', 'max:500'],
            'revision' => ['required', 'string', 'size:64'],
        ]);
        DB::transaction(function () use ($request, $vehicle, $document, $data) {
            $locked = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            $decisions = $locked->document_applicability ?? [];
            $before = $decisions[$document] ?? null;
            // Retries are harmless; a stale different decision must be reviewed.
            if (($before['status'] ?? null) === $data['status'] && ($before['reason'] ?? null) === $data['reason']) return;
            abort_unless(hash_equals(VehicleDocuments::applicabilityRevision($locked, $document), $data['revision']), 409,
                'Esta decisão já foi alterada. Atualize a página e confira antes de gravar.');
            $entry = ['status' => $data['status'], 'reason' => $data['reason'],
                'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                'at' => now()->toIso8601String()];
            $history = $before['history'] ?? [];
            $history[] = $entry;
            $decisions[$document] = $entry + ['history' => $history];
            $locked->document_applicability = $decisions;
            $locked->save();
            if ($followup = $locked->documentFollowups()->where('document',$document)->lockForUpdate()->first()) {
                $followup->status = $data['status'] === 'not_applicable' ? 'dispensed' : 'review';
                $followup->record('applicability', $data['reason']);
            }
            AuditLog::create([
                'description' => 'vehicle_document:applicability', 'subject_id' => $locked->id,
                'subject_type' => Vehicle::class.'#'.$locked->id, 'user_id' => $request->user()->id,
                'properties' => ['document' => $document, 'before' => $before ? array_diff_key($before, ['history' => true]) : null, 'after' => $entry],
                'host' => $request->ip(),
            ]);
        });
        return response()->json([
            'message' => 'Decisão guardada. As confirmações de receção e os ficheiros mantêm-se.',
            'summary_html' => view('admin.vehicles.partials.document-summary', ['vehicle' => $vehicle->fresh('media')])->render(),
        ]);
    }

    public function history(Request $request, Vehicle $vehicle, string $media)
    {
        abort_unless(Gate::allows('vehicle_documents_area_access') && (Gate::allows('vehicle_access') || Gate::allows('vehicle_edit')), 403);
        abort_unless(ctype_digit($media), 404);
        $file = $vehicle->media()->whereKey($media)->whereIn('collection_name', VehicleDocuments::COLLECTIONS)->firstOrFail();
        $history = AuditLog::where('subject_id', $file->id)->where('subject_type', get_class($file).'#'.$file->id)
            ->whereIn('description', ['vehicle_document:classified', 'vehicle_document:annulled'])->orderByDesc('id')->get();
        $authors = \App\Models\User::withTrashed()->whereIn('id', $history->pluck('user_id'))->pluck('name', 'id');
        if ($request->is('api/v1/backoffice/*')) {
            return response()->json(['media_id' => $file->id, 'annulled' => VehicleDocuments::annulled($file),
                'history' => $history->map(fn ($entry) => ['at' => $entry->created_at, 'user_name' => $entry->properties['user_name'] ?? $authors[$entry->user_id] ?? 'Utilizador indisponível', 'properties' => $entry->properties])]);
        }
        return view('admin.vehicles.document-history', compact('vehicle', 'file', 'history', 'authors'));
    }

    public function annul(Request $request, Vehicle $vehicle, string $media)
    {
        abort_unless(Gate::allows('vehicle_edit') && Gate::allows('vehicle_documents_area_access'), 403);
        abort_unless(ctype_digit($media), 404);
        $request->merge(['reason' => is_string($request->reason) ? trim($request->reason) : $request->reason]);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'revision' => ['required', 'string', 'size:64']]);
        DB::transaction(function () use ($request, $vehicle, $media, $data) {
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            $file = $vehicle->media()->whereKey($media)->whereIn('collection_name', VehicleDocuments::COLLECTIONS)->lockForUpdate()->firstOrFail();
            if (VehicleDocuments::annulled($file)) return;
            abort_unless(hash_equals(VehicleDocuments::revision($file), $data['revision']), 409, 'O anexo mudou. Atualize a página antes de anular.');
            $entry = ['reason' => $data['reason'], 'user_id' => $request->user()->id, 'user_name' => $request->user()->name, 'at' => now()->toIso8601String()];
            $file->setCustomProperty('document_annulment', $entry);
            $file->save();
            AuditLog::create(['description' => 'vehicle_document:annulled', 'subject_id' => $file->id,
                'subject_type' => get_class($file).'#'.$file->id, 'user_id' => $request->user()->id,
                'properties' => $entry + ['vehicle_id' => $vehicle->id, 'media_id' => $file->id, 'before' => VehicleDocuments::type($file), 'after' => 'annulled'], 'host' => $request->ip()]);
        });
        if ($request->expectsJson()) return response()->json(['message' => 'Anexo anulado. O ficheiro e o histórico foram preservados.']);
        return redirect()->route('admin.vehicles.documents.history', [$vehicle, $media])->with('message', 'Anexo anulado. O ficheiro e o histórico foram preservados.');
    }

    public function update(Request $request, Vehicle $vehicle, string $media)
    {
        abort_unless(Gate::allows('vehicle_edit') && Gate::allows('vehicle_documents_area_access'), 403);
        abort_unless(ctype_digit($media), 404);
        $request->merge(['reason' => is_string($request->reason) ? trim($request->reason) : $request->reason]);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', Rule::in(array_keys(VehicleDocuments::TYPES))],
            'revision' => ['required', 'string', 'size:64'],
        ]);

        $file = DB::transaction(function () use ($request, $vehicle, $media, $data) {
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            $file = $vehicle->media()->whereKey($media)->whereIn('collection_name', VehicleDocuments::COLLECTIONS)->lockForUpdate()->firstOrFail();
            abort_if(VehicleDocuments::annulled($file), 422, 'Este anexo está anulado. O histórico e o ficheiro mantêm-se disponíveis.');
            $before = VehicleDocuments::type($file);
            if ($before === $data['type']) {
                return $file;
            }
            abort_unless(hash_equals(VehicleDocuments::revision($file), $data['revision']), 409, 'Este tipo já foi alterado. Atualize a página e confira antes de gravar.');
            if (($before !== 'unknown' || $file->getCustomProperty('document_classification') !== null) && empty($data['reason'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['reason' => 'Indique o motivo da correção do tipo de documento.']);
            }
            $file->setCustomProperty('document_classification', [
                'type' => $data['type'],
                'user_id' => $request->user()->id,
                'classified_at' => now()->toIso8601String(),
            ]);
            $file->save();
            AuditLog::create([
                'description' => 'vehicle_document:classified',
                'subject_id' => $file->id,
                'subject_type' => get_class($file).'#'.$file->id,
                'user_id' => $request->user()->id,
                'properties' => ['vehicle_id' => $vehicle->id, 'media_id' => $file->id, 'before' => $before, 'after' => $data['type'], 'reason' => $data['reason'] ?? 'Identificação inicial', 'user_name' => $request->user()->name],
                'host' => $request->ip(),
            ]);
            return $file;
        });

        return response()->json([
            'message' => 'Tipo guardado. As confirmações de receção mantêm-se.',
            'type' => VehicleDocuments::type($file),
            'revision' => VehicleDocuments::revision($file),
            'summary_html' => view('admin.vehicles.partials.document-summary', ['vehicle' => $vehicle->fresh('media')])->render(),
        ]);
    }
}

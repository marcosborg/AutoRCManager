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

    public function update(Request $request, Vehicle $vehicle, string $media)
    {
        abort_unless(Gate::allows('vehicle_edit') && Gate::allows('vehicle_documents_area_access'), 403);
        abort_unless(ctype_digit($media), 404);
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(VehicleDocuments::TYPES))],
            'revision' => ['required', 'string', 'size:64'],
        ]);

        $file = DB::transaction(function () use ($request, $vehicle, $media, $data) {
            $file = $vehicle->media()->whereKey($media)->whereIn('collection_name', VehicleDocuments::COLLECTIONS)->lockForUpdate()->firstOrFail();
            $before = VehicleDocuments::type($file);
            if ($before === $data['type']) {
                return $file;
            }
            abort_unless(hash_equals(VehicleDocuments::revision($file), $data['revision']), 409, 'Este tipo já foi alterado. Atualize a página e confira antes de gravar.');
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
                'properties' => ['vehicle_id' => $vehicle->id, 'media_id' => $file->id, 'before' => $before, 'after' => $data['type']],
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

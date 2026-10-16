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

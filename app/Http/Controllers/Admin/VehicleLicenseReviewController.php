<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\AuditLog;
use App\Support\WorkshopPrivacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VehicleLicenseReviewController extends Controller
{
    public function markForeign(Request $request, Vehicle $vehicle)
    {
        abort_unless(Gate::allows('vehicle_access') && Gate::allows('vehicle_edit') && !WorkshopPrivacy::restricted(), 403);
        $data = $request->validate(['expected_license' => ['required', 'string', 'max:255'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $vehicle, $data) {
            $locked = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            abort_unless((string) $locked->license === $data['expected_license'], 409, 'A matrícula foi alterada. Atualize a página e confira novamente.');
            $current = trim((string) $locked->foreign_license);
            if ($current === trim($data['expected_license'])) return;
            abort_unless($current === '', 409, 'Já existe uma matrícula estrangeira. Confira os dados na ficha da viatura.');
            $locked->foreign_license = $locked->license;
            $locked->save();
            AuditLog::create(['description' => 'vehicle_license:confirmed_foreign', 'subject_id' => $locked->id,
                'subject_type' => Vehicle::class, 'user_id' => auth()->id(), 'host' => $request->ip(),
                'properties' => ['license' => $locked->license, 'foreign_license' => $locked->foreign_license]]);
        });
        return redirect()->route('admin.vehicles.licenses.review')->with('message', 'Matrícula assinalada como estrangeira. O valor original foi mantido.');
    }

    public function index(Request $request)
    {
        abort_unless(Gate::allows('vehicle_access') && Gate::allows('vehicle_edit') && !WorkshopPrivacy::restricted(), 403);
        $filters = $request->validate([
            'reason' => ['nullable', Rule::in(['review', 'missing', 'format', 'foreign'])],
            'scope' => ['nullable', Rule::in(['all', 'stock'])],
            'license' => ['nullable', 'string', 'max:100'],
            'destination_company' => ['nullable', Rule::in(array_merge(array_keys(Vehicle::DESTINATION_COMPANIES), ['unassigned']))],
        ]);
        $reason = $filters['reason'] ?? 'review';
        $query = Vehicle::query()->select(['id', 'license', 'foreign_license', 'brand_id', 'model', 'destination_company'])->with('brand:id,name');
        if (($filters['scope'] ?? 'all') === 'stock') $query->inStock();
        if (!empty($filters['destination_company'])) $query->forDestinationCompany($filters['destination_company']);
        if (trim($filters['license'] ?? '') !== '') $query->searchByLicense(trim($filters['license']));
        // A triage aid, not legal validation. Foreign registrations are reviewed separately.
        $national = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(license, ''))), '-', ''), ' ', ''), '.', '')";
        $foreign = "TRIM(COALESCE(foreign_license, ''))";
        $pattern = '^([A-Z]{2}[0-9]{4}|[0-9]{4}[A-Z]{2}|[0-9]{2}[A-Z]{2}[0-9]{2}|[A-Z]{2}[0-9]{2}[A-Z]{2})$';
        if ($reason === 'foreign') {
            $query->whereRaw("$foreign <> ''");
        } else {
            $query->whereRaw("$foreign = ''");
            if ($reason === 'missing') {
                $query->whereRaw("TRIM(COALESCE(license, '')) = ''");
            } else {
                $query->whereRaw("$national NOT REGEXP ?", [$pattern]);
                if ($reason === 'format') $query->whereRaw("TRIM(COALESCE(license, '')) <> ''");
            }
        }
        $vehicles = $query->orderBy('id')->paginate(25)->withQueryString();
        return response()->view('admin.vehicles.license-review', compact('vehicles', 'filters', 'reason'))
            ->header('Cache-Control', 'private, no-store');
    }
}

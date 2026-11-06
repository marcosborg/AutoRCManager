<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\WorkshopPrivacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VehicleLicenseReviewController extends Controller
{
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleConsignmentAudit;
use App\Support\LicensePlate;
use Gate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleConsignmentAuditController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_consignment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $license = trim((string) $request->query('license', ''));
        $normalizedLicense = LicensePlate::normalize($license);
        $occurrenceDate = trim((string) $request->query('occurrence_date', ''));
        $occurrenceDay = null;
        if ($occurrenceDate !== '') {
            try {
                $occurrenceDay = Carbon::createFromFormat('Y-m-d', $occurrenceDate)->startOfDay();
            } catch (\Throwable) {
                return back()->withErrors(['occurrence_date' => 'A data da ocorrência é inválida.']);
            }
        }
        $audits = VehicleConsignmentAudit::query()
            ->when($normalizedLicense !== '', function ($query) use ($normalizedLicense) {
                $like = '%' . $normalizedLicense . '%';
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->whereRaw("REPLACE(REPLACE(UPPER(vehicle_license_before), '-', ''), ' ', '') LIKE ?", [$like])
                        ->orWhereRaw("REPLACE(REPLACE(UPPER(vehicle_license_after), '-', ''), ' ', '') LIKE ?", [$like]);
                });
            })
            ->when($occurrenceDay, function ($query) use ($occurrenceDay) {
                $endOfDay = $occurrenceDay->copy()->endOfDay();
                $query
                    ->where('effective_starts_at', '<=', $endOfDay)
                    ->where(function ($subQuery) use ($occurrenceDay) {
                        $subQuery->whereNull('effective_ends_at')
                            ->orWhere('effective_ends_at', '>=', $occurrenceDay);
                    });
            })
            ->latest('id')
            ->paginate(100)
            ->withQueryString();

        return view('admin.vehicleConsignmentAudits.index', compact('audits', 'license', 'occurrenceDate'));
    }
}

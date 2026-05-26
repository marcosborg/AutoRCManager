<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleStateTransfer;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleStateTransferController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $license = trim((string) $request->query('license', ''));
        $normalizedLicense = strtoupper(str_replace(['-', ' '], '', $license));

        $transfers = VehicleStateTransfer::with(['vehicle', 'from_general_state', 'to_general_state', 'user', 'checked_by'])
            ->when($license !== '', function ($query) use ($license, $normalizedLicense) {
                $query->whereHas('vehicle', function ($vehicleQuery) use ($license, $normalizedLicense) {
                    $vehicleQuery
                        ->where('license', 'like', '%' . $license . '%')
                        ->orWhere('foreign_license', 'like', '%' . $license . '%')
                        ->orWhereRaw("REPLACE(REPLACE(UPPER(COALESCE(license, '')), '-', ''), ' ', '') LIKE ?", ['%' . $normalizedLicense . '%'])
                        ->orWhereRaw("REPLACE(REPLACE(UPPER(COALESCE(foreign_license, '')), '-', ''), ' ', '') LIKE ?", ['%' . $normalizedLicense . '%']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.vehicleStateTransfers.index', compact('transfers', 'license'));
    }

    public function check(Request $request, VehicleStateTransfer $transfer)
    {
        abort_if(Gate::denies('setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (! $transfer->checked_at) {
            $transfer->update([
                'checked_at' => now(),
                'checked_by_id' => $request->user()?->id,
            ]);
        }

        return redirect()->back()->with('message', 'Mudanca de estado marcada como verificada.');
    }
}

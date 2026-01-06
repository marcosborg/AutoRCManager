<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleStateTransfer;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class VehicleStateTransferController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $transfers = VehicleStateTransfer::with(['vehicle', 'from_general_state', 'to_general_state', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.vehicleStateTransfers.index', compact('transfers'));
    }
}

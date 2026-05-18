<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LotPayment;
use App\Models\VehicleGroup;
use Symfony\Component\HttpFoundation\Response;
use Gate;

class ApprovalController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $pendingPayments = LotPayment::with(['lot.customer', 'payment_method', 'creator'])
            ->where('approval_status', LotPayment::STATUS_PENDING)
            ->latest()
            ->get();

        $pendingLots = VehicleGroup::with(['customer'])
            ->whereNull('approved_at')
            ->latest()
            ->get();

        return view('admin.approvals.index', compact('pendingPayments', 'pendingLots'));
    }
}

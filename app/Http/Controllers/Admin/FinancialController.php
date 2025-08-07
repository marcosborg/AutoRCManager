<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountOperation;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Brand;
use App\Models\GeneralState;
use App\Models\Timelog;

class FinancialController extends Controller
{
    public function index($vehicle_id)
    {

        $vehicle = Vehicle::find($vehicle_id)->load('brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client', 'acquisition_operations.account_item.account_category', 'client_operations.account_item.account_category');
        $general_states = GeneralState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $timelogs = Timelog::with('user')
            ->where('vehicle_id', $vehicle_id)
            ->whereNotNull('rounded_minutes')
            ->orderBy('start_time')
            ->get();


        // Agrupar operações por departamento
        $operationsByDepartment = [
            'aquisition' => AccountOperation::with(['account_item.account_category'])
                ->where('vehicle_id', $vehicle_id)
                ->whereHas('account_item.account_category', function ($q) {
                    $q->where('account_department_id', 1);
                })
                ->get(),

            'garage' => AccountOperation::with(['account_item.account_category'])
                ->where('vehicle_id', $vehicle_id)
                ->whereHas('account_item.account_category', function ($q) {
                    $q->where('account_department_id', 2);
                })
                ->get(),

            'sale' => AccountOperation::with(['account_item.account_category'])
                ->where('vehicle_id', $vehicle_id)
                ->whereHas('account_item.account_category', function ($q) {
                    $q->where('account_department_id', 3);
                })
                ->get(),
        ];

        return view('admin.financial.index', compact('vehicle', 'general_states', 'brands', 'operationsByDepartment', 'timelogs'));
    }
}

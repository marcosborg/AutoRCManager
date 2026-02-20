<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\GeneralState;
use App\Models\PaymentStatus;
use App\Models\PickupState;
use App\Models\Repair;
use App\Models\Suplier;
use App\Models\Vehicle;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class CreateCarForRepairController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('create_car_for_repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $general_states = GeneralState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.createCarForRepairs.index', compact(
            'general_states',
            'brands',
            'carriers',
            'clients',
            'payment_statuses',
            'pickup_states',
            'supliers'
        ));
    }

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->all());

        $repair = Repair::create([
            'vehicle_id' => $vehicle->id,
        ]);

        return redirect()->route('admin.repairs.edit', $repair->id)->with('message', 'Criado com sucesso');
    }
}

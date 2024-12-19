<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarrierRequest;
use App\Http\Requests\UpdateCarrierRequest;
use App\Http\Resources\Admin\CarrierResource;
use App\Models\Carrier;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CarrierApiController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('carrier_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new CarrierResource(Carrier::all());
    }

    public function store(StoreCarrierRequest $request)
    {
        $carrier = Carrier::create($request->all());

        return (new CarrierResource($carrier))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Carrier $carrier)
    {
        abort_if(Gate::denies('carrier_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new CarrierResource($carrier);
    }

    public function update(UpdateCarrierRequest $request, Carrier $carrier)
    {
        $carrier->update($request->all());

        return (new CarrierResource($carrier))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Carrier $carrier)
    {
        abort_if(Gate::denies('carrier_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $carrier->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePickupStateRequest;
use App\Http\Requests\UpdatePickupStateRequest;
use App\Http\Resources\Admin\PickupStateResource;
use App\Models\PickupState;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupStateApiController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('pickup_state_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new PickupStateResource(PickupState::all());
    }

    public function store(StorePickupStateRequest $request)
    {
        $pickupState = PickupState::create($request->all());

        return (new PickupStateResource($pickupState))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(PickupState $pickupState)
    {
        abort_if(Gate::denies('pickup_state_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new PickupStateResource($pickupState);
    }

    public function update(UpdatePickupStateRequest $request, PickupState $pickupState)
    {
        $pickupState->update($request->all());

        return (new PickupStateResource($pickupState))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(PickupState $pickupState)
    {
        abort_if(Gate::denies('pickup_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $pickupState->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}

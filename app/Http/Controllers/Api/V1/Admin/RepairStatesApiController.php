<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepairStateRequest;
use App\Http\Requests\UpdateRepairStateRequest;
use App\Http\Resources\Admin\RepairStateResource;
use App\Models\RepairState;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RepairStatesApiController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('repair_state_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new RepairStateResource(RepairState::all());
    }

    public function store(StoreRepairStateRequest $request)
    {
        $repairState = RepairState::create($request->all());

        return (new RepairStateResource($repairState))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(RepairState $repairState)
    {
        abort_if(Gate::denies('repair_state_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new RepairStateResource($repairState);
    }

    public function update(UpdateRepairStateRequest $request, RepairState $repairState)
    {
        $repairState->update($request->all());

        return (new RepairStateResource($repairState))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(RepairState $repairState)
    {
        abort_if(Gate::denies('repair_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repairState->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}

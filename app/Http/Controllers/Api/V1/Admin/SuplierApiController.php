<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSuplierRequest;
use App\Http\Requests\UpdateSuplierRequest;
use App\Http\Resources\Admin\SuplierResource;
use App\Models\Suplier;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuplierApiController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('suplier_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new SuplierResource(Suplier::all());
    }

    public function store(StoreSuplierRequest $request)
    {
        $suplier = Suplier::create($request->all());

        return (new SuplierResource($suplier))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new SuplierResource($suplier);
    }

    public function update(UpdateSuplierRequest $request, Suplier $suplier)
    {
        $suplier->update($request->all());

        return (new SuplierResource($suplier))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Suplier $suplier)
    {
        abort_if(Gate::denies('suplier_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $suplier->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}

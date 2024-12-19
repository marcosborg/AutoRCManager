<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAcquisitionRequest;
use App\Http\Requests\StoreAcquisitionRequest;
use App\Http\Requests\UpdateAcquisitionRequest;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcquisitionController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('acquisition_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.acquisitions.index');
    }

    public function create()
    {
        abort_if(Gate::denies('acquisition_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.acquisitions.create');
    }

    public function store(StoreAcquisitionRequest $request)
    {
        $acquisition = Acquisition::create($request->all());

        return redirect()->route('admin.acquisitions.index');
    }

    public function edit(Acquisition $acquisition)
    {
        abort_if(Gate::denies('acquisition_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.acquisitions.edit', compact('acquisition'));
    }

    public function update(UpdateAcquisitionRequest $request, Acquisition $acquisition)
    {
        $acquisition->update($request->all());

        return redirect()->route('admin.acquisitions.index');
    }

    public function show(Acquisition $acquisition)
    {
        abort_if(Gate::denies('acquisition_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.acquisitions.show', compact('acquisition'));
    }

    public function destroy(Acquisition $acquisition)
    {
        abort_if(Gate::denies('acquisition_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $acquisition->delete();

        return back();
    }

    public function massDestroy(MassDestroyAcquisitionRequest $request)
    {
        $acquisitions = Acquisition::find(request('ids'));

        foreach ($acquisitions as $acquisition) {
            $acquisition->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}

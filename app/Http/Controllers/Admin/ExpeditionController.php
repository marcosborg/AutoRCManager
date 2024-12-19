<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyExpeditionRequest;
use App\Http\Requests\StoreExpeditionRequest;
use App\Http\Requests\UpdateExpeditionRequest;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpeditionController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('expedition_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.expeditions.index');
    }

    public function create()
    {
        abort_if(Gate::denies('expedition_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.expeditions.create');
    }

    public function store(StoreExpeditionRequest $request)
    {
        $expedition = Expedition::create($request->all());

        return redirect()->route('admin.expeditions.index');
    }

    public function edit(Expedition $expedition)
    {
        abort_if(Gate::denies('expedition_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.expeditions.edit', compact('expedition'));
    }

    public function update(UpdateExpeditionRequest $request, Expedition $expedition)
    {
        $expedition->update($request->all());

        return redirect()->route('admin.expeditions.index');
    }

    public function show(Expedition $expedition)
    {
        abort_if(Gate::denies('expedition_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.expeditions.show', compact('expedition'));
    }

    public function destroy(Expedition $expedition)
    {
        abort_if(Gate::denies('expedition_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expedition->delete();

        return back();
    }

    public function massDestroy(MassDestroyExpeditionRequest $request)
    {
        $expeditions = Expedition::find(request('ids'));

        foreach ($expeditions as $expedition) {
            $expedition->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}

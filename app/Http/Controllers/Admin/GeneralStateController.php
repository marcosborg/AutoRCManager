<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyGeneralStateRequest;
use App\Http\Requests\StoreGeneralStateRequest;
use App\Http\Requests\UpdateGeneralStateRequest;
use App\Models\GeneralState;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class GeneralStateController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('general_state_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $generalStates = GeneralState::all();

        return view('admin.generalStates.index', compact('generalStates'));
    }

    public function create()
    {
        abort_if(Gate::denies('general_state_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.generalStates.create');
    }

    public function store(StoreGeneralStateRequest $request)
    {
        $generalState = GeneralState::create($request->all());

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $generalState->id]);
        }

        return redirect()->route('admin.general-states.index');
    }

    public function edit(GeneralState $generalState)
    {
        abort_if(Gate::denies('general_state_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.generalStates.edit', compact('generalState'));
    }

    public function update(UpdateGeneralStateRequest $request, GeneralState $generalState)
    {
        $generalState->update($request->all());

        return redirect()->route('admin.general-states.index');
    }

    public function show(GeneralState $generalState)
    {
        abort_if(Gate::denies('general_state_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.generalStates.show', compact('generalState'));
    }

    public function destroy(GeneralState $generalState)
    {
        abort_if(Gate::denies('general_state_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $generalState->delete();

        return back();
    }

    public function massDestroy(MassDestroyGeneralStateRequest $request)
    {
        $generalStates = GeneralState::find(request('ids'));

        foreach ($generalStates as $generalState) {
            $generalState->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('general_state_create') && Gate::denies('general_state_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new GeneralState();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiTrainingContentRequest;
use App\Http\Requests\UpdateAiTrainingContentRequest;
use App\Models\AiAssistant;
use App\Models\AiTrainingContent;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AiTrainingContentController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('ai_training_content_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contents = AiTrainingContent::with('assistant')->orderBy('sort_order')->latest()->paginate(50);

        return view('admin.aiTrainingContents.index', compact('contents'));
    }

    public function create()
    {
        abort_if(Gate::denies('ai_training_content_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $assistants = AiAssistant::pluck('name', 'id')->prepend('Global', '');
        $types = AiTrainingContent::TYPE_SELECT;

        return view('admin.aiTrainingContents.create', compact('assistants', 'types'));
    }

    public function store(StoreAiTrainingContentRequest $request)
    {
        AiTrainingContent::create($request->validated() + ['active' => $request->boolean('active', true)]);

        return redirect()->route('admin.ai-training-contents.index');
    }

    public function show(AiTrainingContent $aiTrainingContent)
    {
        abort_if(Gate::denies('ai_training_content_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.aiTrainingContents.show', compact('aiTrainingContent'));
    }

    public function edit(AiTrainingContent $aiTrainingContent)
    {
        abort_if(Gate::denies('ai_training_content_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $assistants = AiAssistant::pluck('name', 'id')->prepend('Global', '');
        $types = AiTrainingContent::TYPE_SELECT;

        return view('admin.aiTrainingContents.edit', compact('aiTrainingContent', 'assistants', 'types'));
    }

    public function update(UpdateAiTrainingContentRequest $request, AiTrainingContent $aiTrainingContent)
    {
        $aiTrainingContent->update($request->validated() + ['active' => $request->boolean('active')]);

        return redirect()->route('admin.ai-training-contents.index');
    }

    public function destroy(AiTrainingContent $aiTrainingContent)
    {
        abort_if(Gate::denies('ai_training_content_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $aiTrainingContent->delete();

        return back();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiAssistantRequest;
use App\Http\Requests\UpdateAiAssistantRequest;
use App\Models\AiAssistant;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AiAssistantController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('ai_assistant_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $aiAssistants = AiAssistant::latest()->paginate(50);

        return view('admin.aiAssistants.index', compact('aiAssistants'));
    }

    public function create()
    {
        abort_if(Gate::denies('ai_assistant_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.aiAssistants.create');
    }

    public function store(StoreAiAssistantRequest $request)
    {
        AiAssistant::create($request->validated() + ['active' => $request->boolean('active')]);

        return redirect()->route('admin.ai-assistants.index');
    }

    public function show(AiAssistant $aiAssistant)
    {
        abort_if(Gate::denies('ai_assistant_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $aiAssistant->load('training_contents');

        return view('admin.aiAssistants.show', compact('aiAssistant'));
    }

    public function edit(AiAssistant $aiAssistant)
    {
        abort_if(Gate::denies('ai_assistant_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.aiAssistants.edit', compact('aiAssistant'));
    }

    public function update(UpdateAiAssistantRequest $request, AiAssistant $aiAssistant)
    {
        $aiAssistant->update($request->validated() + ['active' => $request->boolean('active')]);

        return redirect()->route('admin.ai-assistants.index');
    }

    public function destroy(AiAssistant $aiAssistant)
    {
        abort_if(Gate::denies('ai_assistant_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $aiAssistant->delete();

        return back();
    }
}

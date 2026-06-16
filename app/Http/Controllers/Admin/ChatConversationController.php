<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\AiLeadAssistantService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ChatConversationController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('chat_conversation_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $conversations = ChatConversation::with(['lead', 'channel', 'assistant'])
            ->latest('last_message_at')
            ->paginate(50);

        return view('admin.chatConversations.index', compact('conversations'));
    }

    public function show(ChatConversation $chatConversation)
    {
        abort_if(Gate::denies('chat_conversation_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $chatConversation->load(['lead.assigned_user', 'channel', 'assistant', 'messages']);

        return view('admin.chatConversations.show', compact('chatConversation'));
    }

    public function takeover(ChatConversation $chatConversation, AiLeadAssistantService $service)
    {
        abort_if(Gate::denies('chat_conversation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->markTakenOver($chatConversation);

        return back();
    }

    public function release(ChatConversation $chatConversation, AiLeadAssistantService $service)
    {
        abort_if(Gate::denies('chat_conversation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->releaseToAi($chatConversation);

        return back();
    }

    public function close(ChatConversation $chatConversation, AiLeadAssistantService $service)
    {
        abort_if(Gate::denies('chat_conversation_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->close($chatConversation);

        return back();
    }
}

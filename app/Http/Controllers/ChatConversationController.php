<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Services\AiLeadAssistantService;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(ChatConversation::query()
            ->with(['lead', 'channel', 'assistant'])
            ->latest('last_message_at')
            ->paginate(min(max((int) $request->integer('per_page', 25), 1), 100)));
    }

    public function show(ChatConversation $conversation)
    {
        return response()->json($conversation->load(['lead', 'channel', 'assistant', 'messages']));
    }

    public function takeover(ChatConversation $conversation, AiLeadAssistantService $assistantService)
    {
        return response()->json($assistantService->markTakenOver($conversation));
    }

    public function release(ChatConversation $conversation, AiLeadAssistantService $assistantService)
    {
        return response()->json($assistantService->releaseToAi($conversation));
    }

    public function close(ChatConversation $conversation, AiLeadAssistantService $assistantService)
    {
        return response()->json($assistantService->close($conversation));
    }
}

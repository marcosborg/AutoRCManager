<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\AiLeadAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class WhatsappNodeController extends Controller
{
    public function incomingMessage(Request $request, AiLeadAssistantService $assistantService)
    {
        $payload = $request->validate([
            'channel' => ['nullable', 'string', 'max:50'],
            'phone' => ['required_without:from', 'nullable', 'string', 'max:255'],
            'from' => ['required_without:phone', 'nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'message' => ['required_without:body', 'nullable', 'string'],
            'body' => ['required_without:message', 'nullable', 'string'],
            'message_id' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'vehicle_reference' => ['nullable', 'string', 'max:255'],
            'vehicle_title' => ['nullable', 'string', 'max:255'],
            'vehicle_interest' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json($assistantService->handleIncomingMessage($payload));
    }

    public function outgoingMessages(Request $request)
    {
        $limit = min(max((int) $request->integer('limit', 20), 1), 100);

        $messages = ChatMessage::query()
            ->with(['conversation.lead', 'conversation.channel'])
            ->where('sender', 'assistant')
            ->where('delivery_status', 'pending')
            ->whereHas('conversation.channel', fn ($query) => $query->where('slug', 'whatsapp'))
            ->oldest()
            ->limit($limit)
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'lead_id' => $message->conversation?->lead_id,
                'phone' => $message->conversation?->customer_phone ?: $message->conversation?->lead?->phone,
                'message' => $message->message,
                'metadata' => $message->metadata,
            ])
            ->values();

        return response()->json(['data' => $messages]);
    }

    public function markOutgoingSent(Request $request, ChatMessage $message)
    {
        $data = $request->validate([
            'external_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $message->update([
            'external_id' => $data['external_id'] ?? $message->external_id,
            'delivery_status' => 'sent',
            'sent_at' => now(),
            'metadata' => array_filter(array_merge($message->metadata ?? [], $data['metadata'] ?? [])),
        ]);

        return response()->json(['ok' => true]);
    }

    public function messageStatus(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:pending,sent,delivered,read,failed'],
            'metadata' => ['nullable', 'array'],
        ]);

        $message = ChatMessage::where('external_id', $data['external_id'])->first();
        if (! $message) {
            return response()->json(['message' => 'Message not found'], Response::HTTP_NOT_FOUND);
        }

        $message->update([
            'delivery_status' => $data['status'],
            'metadata' => array_filter(array_merge($message->metadata ?? [], $data['metadata'] ?? [])),
        ]);

        return response()->json(['ok' => true]);
    }

    public function conversations(Request $request)
    {
        $conversations = ChatConversation::query()
            ->with(['lead', 'channel', 'assistant'])
            ->latest('last_message_at')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json($conversations);
    }

    public function conversation(ChatConversation $conversation)
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

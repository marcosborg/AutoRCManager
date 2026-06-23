<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\LeadAccessToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadAccessController extends Controller
{
    public function show(Request $request, string $token)
    {
        abort_if(! preg_match('/^[A-Za-z0-9]{40,120}$/', $token), Response::HTTP_NOT_FOUND);

        $accessToken = LeadAccessToken::with(['lead.assigned_user', 'user'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_if(! $accessToken || ! $accessToken->isUsable(), Response::HTTP_NOT_FOUND);

        $accessToken->update(['last_used_at' => now()]);

        $lead = $accessToken->lead;
        $messages = $this->messagesFor($lead);
        $customerPhone = $this->normalizePhone($lead->phone);
        $callUrl = $customerPhone ? 'tel:+' . $customerPhone : null;
        $whatsappUrl = $customerPhone ? $this->whatsappUrl($customerPhone, $lead) : null;

        return view('leadAccess.show', compact('lead', 'accessToken', 'messages', 'callUrl', 'whatsappUrl'));
    }

    private function messagesFor($lead)
    {
        if (data_get($lead->raw_data, 'source') !== 'ai_whatsapp') {
            return collect();
        }

        $conversationId = data_get($lead->raw_data, 'chat_conversation_id');
        if ($conversationId) {
            $conversation = ChatConversation::with(['messages' => fn ($query) => $query->orderBy('id')])
                ->find($conversationId);

            if ($conversation) {
                return $conversation->messages->map(fn ($message) => [
                    'sender' => $message->sender,
                    'message' => $message->message,
                    'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                ]);
            }
        }

        return collect(data_get($lead->raw_data, 'messages', []));
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            return '351' . $digits;
        }

        return $digits;
    }

    private function whatsappUrl(string $customerPhone, $lead): string
    {
        $name = $lead->full_name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: '';
        $message = trim('Olá' . ($name ? ' ' . $name : '') . ', fala o comercial da Car 7. Estou a contactar para dar seguimento ao seu pedido.');

        return 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode($message);
    }
}

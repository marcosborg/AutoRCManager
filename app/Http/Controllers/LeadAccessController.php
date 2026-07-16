<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\LeadAccessToken;
use App\Models\LeadContactEvent;
use App\Services\LeadAccessEscalationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadAccessController extends Controller
{
    public function show(Request $request, string $token, LeadAccessEscalationService $escalationService)
    {
        abort_if(! preg_match('/^[A-Za-z0-9]{40,120}$/', $token), Response::HTTP_NOT_FOUND);

        $accessToken = LeadAccessToken::with(['lead.assigned_user', 'user'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_if(! $accessToken, Response::HTTP_NOT_FOUND);

        if ($accessToken->revoked_reason === LeadAccessEscalationService::REVOKED_NO_OPEN_TIMEOUT) {
            return response()->view('leadAccess.transferred', compact('accessToken'), Response::HTTP_GONE);
        }

        if ($accessToken->firstOpenDeadlinePassed()) {
            $escalationService->expireTokenAndReassign($accessToken);
            $accessToken->refresh();

            return response()->view('leadAccess.transferred', compact('accessToken'), Response::HTTP_GONE);
        }

        abort_if(! $accessToken->isUsable(), Response::HTTP_NOT_FOUND);

        $firstOpen = $accessToken->last_used_at === null;
        $accessToken->update(array_filter([
            'last_used_at' => now(),
            'expires_at' => $firstOpen ? now()->addDays(7) : null,
        ]));

        $lead = $accessToken->lead;
        $messages = $this->messagesFor($lead);
        $customerPhone = $this->normalizePhone($lead->phone);
        $callUrl = $customerPhone ? route('lead-access.contact', [$token, 'call']) : null;
        $whatsappUrl = $customerPhone ? route('lead-access.contact', [$token, 'whatsapp']) : null;

        return view('leadAccess.show', compact('lead', 'accessToken', 'messages', 'callUrl', 'whatsappUrl'));
    }

    public function contact(string $token, string $channel)
    {
        abort_if(! preg_match('/^[A-Za-z0-9]{40,120}$/', $token), Response::HTTP_NOT_FOUND);

        $accessToken = LeadAccessToken::with('lead')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_if(
            ! $accessToken
            || ! $accessToken->isUsable()
            || ! $accessToken->last_used_at
            || ! $accessToken->assignment_history_id
            || (int) $accessToken->lead?->assigned_user_id !== (int) $accessToken->user_id,
            Response::HTTP_GONE
        );

        $phone = $this->normalizePhone($accessToken->lead->phone);
        abort_if(! $phone, Response::HTTP_NOT_FOUND);

        LeadContactEvent::create([
            'lead_id' => $accessToken->lead_id,
            'user_id' => $accessToken->user_id,
            'assignment_history_id' => $accessToken->assignment_history_id,
            'access_token_id' => $accessToken->id,
            'channel' => $channel,
            'clicked_at' => now(),
        ]);

        $destination = $channel === 'call'
            ? 'tel:+'.$phone
            : $this->whatsappUrl($phone, $accessToken->lead);

        return redirect()->away($destination);
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

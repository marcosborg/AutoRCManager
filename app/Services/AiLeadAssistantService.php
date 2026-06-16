<?php

namespace App\Services;

use App\Models\AiAssistant;
use App\Models\ChatChannel;
use App\Models\ChatConversation;
use App\Models\ChatLead;
use App\Models\ChatMessage;
use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiLeadAssistantService
{
    public function syncFromMetaLead(Lead $lead, bool $queueGreeting = true): ?ChatConversation
    {
        if (blank($lead->phone)) {
            return null;
        }

        $assistant = $this->assistant();
        $channel = $this->channel('whatsapp', 'WhatsApp');
        $phone = $this->normalizePhone($lead->phone);

        $chatLead = ChatLead::query()
            ->where('lead_id', $lead->id)
            ->orWhere(function ($query) use ($phone) {
                $query->whereNotNull('phone')->where('phone', $phone);
            })
            ->first();

        $chatLead = $chatLead ?: new ChatLead();
        $chatLead->fill([
            'lead_id' => $lead->id,
            'channel_id' => $channel->id,
            'name' => $lead->full_name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: $chatLead->name,
            'phone' => $phone,
            'email' => $lead->email ?: $chatLead->email,
            'source' => 'meta',
            'external_id' => $lead->leadgen_id ?: $chatLead->external_id,
            'vehicle_reference' => Arr::get($lead->raw_data, 'payload.retailerItemId', $chatLead->vehicle_reference),
            'vehicle_title' => $lead->vehicle_interest ?: Arr::get($lead->raw_data, 'payload.adName', $chatLead->vehicle_title),
            'budget_max' => $this->parseMoney($lead->budget) ?: $chatLead->budget_max,
            'wants_financing' => $this->truthyText($lead->financing, ['financiamento']),
            'has_trade_in' => $this->truthyText($lead->trade_in, ['sim', 'yes']),
            'priority' => $chatLead->priority ?: 'medium',
            'status' => $chatLead->status ?: 'open',
            'assigned_to' => $lead->assigned_user_id ?: $chatLead->assigned_to,
        ]);
        $chatLead->save();

        $conversation = ChatConversation::firstOrCreate(
            [
                'lead_id' => $chatLead->id,
                'channel_id' => $channel->id,
                'customer_phone' => $phone,
            ],
            [
                'assistant_id' => $assistant->id,
                'customer_identifier' => $phone,
                'status' => 'active',
                'human_takeover' => false,
                'last_message_at' => now(),
            ]
        );

        if ($queueGreeting) {
            $this->queueGreeting($conversation, $chatLead, $lead);
        }

        return $conversation->fresh(['lead', 'channel', 'messages']);
    }

    public function handleIncomingMessage(array $payload): array
    {
        $assistant = $this->assistant();
        $channelSlug = Str::slug((string) ($payload['channel'] ?? 'whatsapp')) ?: 'whatsapp';
        $channel = $this->channel($channelSlug, ucfirst($channelSlug));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? $payload['from'] ?? ''));
        $message = trim((string) ($payload['message'] ?? $payload['body'] ?? ''));

        $chatLead = $this->leadFromIncoming($channel, $phone, $payload);
        $conversation = $this->conversationFor($assistant, $channel, $chatLead, $phone, $payload['external_id'] ?? null);

        $conversation->messages()->create([
            'sender' => 'customer',
            'message' => $message,
            'external_id' => $payload['message_id'] ?? null,
            'delivery_status' => 'delivered',
            'metadata' => Arr::except($payload, ['message', 'body']),
            'sent_at' => now(),
        ]);

        $priority = $this->classifyPriority($message);
        $mustEscalate = $this->shouldEscalate($message);

        $chatLead->fill([
            'priority' => $priority,
            'status' => $mustEscalate ? 'waiting_human' : ($chatLead->status ?: 'open'),
            'summary' => $this->appendSummary($chatLead->summary, $message),
        ])->save();

        $conversation->fill([
            'status' => $mustEscalate ? 'waiting_human' : $conversation->status,
            'last_message_at' => now(),
        ])->save();

        if (! $this->canAutoReply($conversation)) {
            return [
                'ok' => true,
                'reply' => null,
                'conversation_id' => $conversation->id,
                'lead_id' => $chatLead->id,
                'status' => $conversation->status,
                'priority' => $priority,
                'human_takeover' => $conversation->human_takeover,
            ];
        }

        $reply = $this->generateReply($assistant, $conversation, $message, $mustEscalate);
        $assistantMessage = $conversation->messages()->create([
            'sender' => 'assistant',
            'message' => $reply,
            'delivery_status' => 'pending',
            'metadata' => [
                'source' => 'ai_auto_reply',
                'priority' => $priority,
                'escalated' => $mustEscalate,
            ],
        ]);

        return [
            'ok' => true,
            'reply' => $reply,
            'message_id' => $assistantMessage->id,
            'conversation_id' => $conversation->id,
            'lead_id' => $chatLead->id,
            'status' => $conversation->status,
            'priority' => $priority,
            'human_takeover' => $conversation->human_takeover,
        ];
    }

    public function markTakenOver(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['human_takeover' => true, 'status' => 'waiting_human']);
        $conversation->lead?->update(['status' => 'waiting_human']);

        return $conversation->fresh(['lead', 'channel', 'messages']);
    }

    public function releaseToAi(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['human_takeover' => false, 'status' => 'active']);
        $conversation->lead?->update(['status' => 'open']);

        return $conversation->fresh(['lead', 'channel', 'messages']);
    }

    public function close(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['human_takeover' => true, 'status' => 'closed']);
        $conversation->lead?->update(['status' => 'closed']);

        return $conversation->fresh(['lead', 'channel', 'messages']);
    }

    private function assistant(): AiAssistant
    {
        $slug = config('ai_assistant.default_assistant_slug', 'carsete');

        $assistant = AiAssistant::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->first();

        if ($assistant) {
            return $assistant;
        }

        return AiAssistant::query()->firstOrCreate(
            ['slug' => 'carsete'],
            [
                'name' => 'Assistente Comercial CarSete',
                'company_name' => config('ai_assistant.company_name', 'CarSete'),
                'commercial_phone' => config('ai_assistant.commercial_phone', '220 132 036'),
                'active' => true,
                'system_prompt' => 'És o assistente virtual comercial da empresa.',
                'default_language' => 'pt-PT',
            ]
        );
    }

    private function channel(string $slug, string $name): ChatChannel
    {
        return ChatChannel::firstOrCreate(['slug' => $slug], ['name' => $name, 'active' => true]);
    }

    private function leadFromIncoming(ChatChannel $channel, string $phone, array $payload): ChatLead
    {
        $lead = ChatLead::query()
            ->where('channel_id', $channel->id)
            ->where('phone', $phone)
            ->first();

        $lead = $lead ?: new ChatLead([
            'channel_id' => $channel->id,
            'phone' => $phone,
            'source' => $channel->slug,
            'priority' => 'low',
            'status' => 'open',
        ]);

        $lead->fill([
            'name' => $payload['name'] ?? $lead->name,
            'email' => $payload['email'] ?? $lead->email,
            'external_id' => $payload['leadgen_id'] ?? $payload['external_id'] ?? $lead->external_id,
            'vehicle_reference' => $payload['vehicle_reference'] ?? $lead->vehicle_reference,
            'vehicle_title' => $payload['vehicle_title'] ?? $payload['vehicle_interest'] ?? $lead->vehicle_title,
        ])->save();

        return $lead;
    }

    private function conversationFor(AiAssistant $assistant, ChatChannel $channel, ChatLead $lead, string $phone, ?string $externalId): ChatConversation
    {
        $conversation = ChatConversation::query()
            ->where('lead_id', $lead->id)
            ->where('channel_id', $channel->id)
            ->where('status', '!=', 'closed')
            ->latest()
            ->first();

        return $conversation ?: ChatConversation::create([
            'assistant_id' => $assistant->id,
            'lead_id' => $lead->id,
            'channel_id' => $channel->id,
            'external_id' => $externalId,
            'customer_identifier' => $phone,
            'customer_phone' => $phone,
            'status' => 'active',
            'human_takeover' => false,
            'last_message_at' => now(),
        ]);
    }

    private function queueGreeting(ChatConversation $conversation, ChatLead $chatLead, Lead $lead): void
    {
        $alreadyQueued = $conversation->messages()
            ->where('sender', 'assistant')
            ->where(function ($query) {
                $query->where('metadata->type', 'meta_greeting')
                    ->orWhere('delivery_status', 'pending');
            })
            ->exists();

        if ($alreadyQueued) {
            return;
        }

        $name = $chatLead->name ? ' ' . trim($chatLead->name) : '';
        $company = $conversation->assistant->company_name ?: config('ai_assistant.company_name', 'CarSete');
        $phone = $conversation->assistant->commercial_phone ?: config('ai_assistant.commercial_phone');

        $conversation->messages()->create([
            'sender' => 'assistant',
            'message' => "Olá{$name}, está a falar com o assistente virtual da empresa {$company}. Recebemos o seu pedido de informação e vamos ajudar. Se preferir falar diretamente com um comercial, pode contactar {$phone}.",
            'delivery_status' => 'pending',
            'metadata' => [
                'type' => 'meta_greeting',
                'lead_id' => $lead->id,
                'leadgen_id' => $lead->leadgen_id,
            ],
        ]);
    }

    private function canAutoReply(ChatConversation $conversation): bool
    {
        return (bool) config('ai_assistant.auto_reply_enabled', true)
            && $conversation->status === 'active'
            && ! $conversation->human_takeover;
    }

    private function generateReply(AiAssistant $assistant, ChatConversation $conversation, string $message, bool $mustEscalate): string
    {
        if ($mustEscalate) {
            return $this->handoffReply($assistant);
        }

        $apiKey = (string) config('ai_assistant.openai_api_key');
        if ($apiKey === '') {
            return $this->fallbackReply($assistant);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('ai_assistant.openai_model', 'gpt-4o-mini'),
                    'messages' => $this->messagesForOpenAi($assistant, $conversation, $message),
                    'temperature' => 0.4,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI assistant request failed.', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                return $this->fallbackReply($assistant);
            }

            return trim((string) Arr::get($response->json(), 'choices.0.message.content')) ?: $this->fallbackReply($assistant);
        } catch (\Throwable $exception) {
            Log::warning('OpenAI assistant exception.', ['error' => $exception->getMessage()]);

            return $this->fallbackReply($assistant);
        }
    }

    private function messagesForOpenAi(AiAssistant $assistant, ChatConversation $conversation, string $message): array
    {
        $messages = [['role' => 'system', 'content' => $this->systemPrompt($assistant)]];

        $history = $conversation->messages()
            ->latest()
            ->limit((int) config('ai_assistant.max_context_messages', 20))
            ->get()
            ->reverse();

        foreach ($history as $chatMessage) {
            $messages[] = [
                'role' => $chatMessage->sender === 'customer' ? 'user' : 'assistant',
                'content' => $chatMessage->message,
            ];
        }

        if ($history->isEmpty() || $history->last()?->message !== $message) {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        return $messages;
    }

    private function systemPrompt(AiAssistant $assistant): string
    {
        $training = $assistant->training_contents()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('content')
            ->implode("\n\n");

        $company = $assistant->company_name ?: config('ai_assistant.company_name', 'CarSete');
        $phone = $assistant->commercial_phone ?: config('ai_assistant.commercial_phone');

        return implode("\n\n", array_filter([
            "És o assistente virtual comercial da empresa {$company}. Deves ser transparente sobre seres um assistente virtual.",
            "Fala em português de Portugal. Sê curto, educado, útil e comercial. O telefone comercial é {$phone}.",
            "Não inventes dados de viaturas, preços, financiamento, disponibilidade, garantias ou retomas. Se não souberes, encaminha para humano.",
            "Escala para humano em pedidos de comercial, negociação, retoma, reserva, irritação, financiamento avançado ou quando o cliente não quiser falar com IA.",
            $assistant->system_prompt,
            $assistant->rules,
            $assistant->allowed_topics ? 'Temas permitidos: ' . $assistant->allowed_topics : null,
            $assistant->forbidden_topics ? 'Temas proibidos: ' . $assistant->forbidden_topics : null,
            $assistant->escalation_rules ? 'Regras de escalamento: ' . $assistant->escalation_rules : null,
            $training ? 'Conteúdos de treino:' . "\n" . $training : null,
        ]));
    }

    private function classifyPriority(string $message): string
    {
        $text = Str::lower($message);
        if (Str::contains($text, ['reserv', 'financiamento', 'retoma', 'proposta', 'sinal', 'comprar', 'urgente', 'hoje', 'esta semana'])) {
            return 'high';
        }

        if (Str::contains($text, ['preço', 'preco', 'valor', 'orçamento', 'orcamento', 'prestação', 'prestacao', 'interessado', 'viatura', 'modelo'])) {
            return 'medium';
        }

        return 'low';
    }

    private function shouldEscalate(string $message): bool
    {
        return Str::contains(Str::lower($message), [
            'comercial', 'humano', 'pessoa', 'liguem', 'ligar', 'não quero bot', 'nao quero bot',
            'não quero ia', 'nao quero ia', 'negociar', 'desconto', 'retoma', 'avaliação',
            'avaliacao', 'reservar', 'sinal', 'financiamento', 'crédito', 'credito', 'reclamação', 'reclamacao',
        ]);
    }

    private function handoffReply(AiAssistant $assistant): string
    {
        $phone = $assistant->commercial_phone ?: config('ai_assistant.commercial_phone');

        return "Vou passar a conversa para um comercial para ajudar melhor. Também pode contactar-nos diretamente pelo {$phone}.";
    }

    private function fallbackReply(AiAssistant $assistant): string
    {
        $company = $assistant->company_name ?: config('ai_assistant.company_name', 'CarSete');
        $phone = $assistant->commercial_phone ?: config('ai_assistant.commercial_phone');

        return "Olá, sou o assistente virtual da {$company}. Recebi a sua mensagem e vou ajudar. Pode dizer-me que viatura procura, orçamento aproximado e se pretende financiamento ou retoma? Para falar diretamente com um comercial: {$phone}.";
    }

    private function appendSummary(?string $summary, string $message): string
    {
        $line = now()->format('Y-m-d H:i') . ' - Cliente: ' . Str::limit($message, 180);

        return trim(($summary ? $summary . "\n" : '') . $line);
    }

    private function normalizePhone(string $phone): string
    {
        return trim(preg_replace('/\s+/', '', $phone));
    }

    private function parseMoney(?string $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        preg_match_all('/[\d]+(?:[.,]\d+)?/', (string) $value, $matches);
        if (empty($matches[0])) {
            return null;
        }

        $number = end($matches[0]);
        if (preg_match('/^\d{1,3},\d{3}$/', $number)) {
            $number = str_replace(',', '', $number);
        } elseif (preg_match('/^\d{1,3}\.\d{3}$/', $number)) {
            $number = str_replace('.', '', $number);
        } else {
            $number = str_replace(['.', ','], ['', '.'], $number);
        }

        return is_numeric($number) ? (float) $number : null;
    }

    private function truthyText(?string $value, array $needles): bool
    {
        if (blank($value)) {
            return false;
        }

        return Str::contains(Str::lower($value), $needles);
    }
}

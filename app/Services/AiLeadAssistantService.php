<?php

namespace App\Services;

use App\Models\AiAssistant;
use App\Models\ChatChannel;
use App\Models\ChatConversation;
use App\Models\ChatLead;
use App\Models\ChatMessage;
use App\Models\Lead;
use App\Notifications\NewLeadNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiLeadAssistantService
{
    public function syncFromMetaLead(Lead $lead, bool $queueGreeting = true): ?ChatConversation
    {
        if ($this->chatOnStandby()) {
            return null;
        }

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
        if ($this->chatOnStandby()) {
            return [
                'ok' => true,
                'reply' => null,
                'status' => 'standby',
                'message' => 'AI chat is temporarily on standby.',
            ];
        }

        $assistant = $this->assistant();
        $channelSlug = Str::slug((string) ($payload['channel'] ?? 'whatsapp')) ?: 'whatsapp';
        $channel = $this->channel($channelSlug, ucfirst($channelSlug));
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? $payload['from'] ?? ''));
        $message = trim((string) ($payload['message'] ?? $payload['body'] ?? ''));

        $chatLead = $this->leadFromIncoming($channel, $phone, $payload);
        $conversation = $this->conversationFor($assistant, $channel, $chatLead, $phone, $payload['external_id'] ?? null);
        $conversation = $this->releaseToAiAfterIdleTakeover($conversation);

        $conversation->messages()->create([
            'sender' => 'customer',
            'message' => $message,
            'external_id' => $payload['message_id'] ?? null,
            'delivery_status' => 'delivered',
            'metadata' => Arr::except($payload, ['message', 'body']),
            'sent_at' => now(),
        ]);

        $priority = $this->classifyPriority($message);

        $chatLead->fill([
            'priority' => $priority,
            'status' => $chatLead->status === 'waiting_human' ? 'waiting_human' : ($chatLead->status ?: 'open'),
            'summary' => $this->appendSummary($chatLead->summary, $message),
        ])->save();

        $conversation->fill([
            'status' => $conversation->human_takeover ? $conversation->status : 'active',
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

        $qualificationService = app(AiLeadQualificationService::class);
        $qualification = $qualificationService->qualificationFor($conversation->fresh(['lead', 'messages']));
        $missingFields = $qualificationService->missingFields($qualification);
        $completedLead = null;

        if ($missingFields === [] && ! $chatLead->lead_id) {
            $completedLead = $this->createLeadFromConversation($conversation->fresh(['lead', 'messages']), $qualification);
            $chatLead = $completedLead ? $completedLead->chatLead : $chatLead->fresh();
        }

        $reply = $completedLead
            ? 'Obrigado. Vou encaminhar o seu pedido para um comercial da Car 7, que dará seguimento consigo.'
            : $this->generateReply($assistant, $conversation, $message, $qualificationService->contextForPrompt($qualification));

        $assistantMessage = $conversation->messages()->create([
            'sender' => 'assistant',
            'message' => $reply,
            'delivery_status' => 'pending',
            'metadata' => [
                'source' => 'ai_auto_reply',
                'priority' => $priority,
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

    private function createLeadFromConversation(ChatConversation $conversation, array $qualification): ?Lead
    {
        if ($conversation->human_takeover) {
            return null;
        }

        return DB::transaction(function () use ($conversation, $qualification) {
            $conversation->loadMissing('lead', 'messages');
            $chatLead = $conversation->lead;

            if (! $chatLead || $chatLead->lead_id) {
                return $chatLead?->meta_lead;
            }

            $payload = app(AiLeadQualificationService::class)->buildLeadPayload($conversation, $qualification);
            $lead = Lead::firstOrCreate(['leadgen_id' => $payload['leadgen_id']], $payload);

            if ($lead->wasRecentlyCreated) {
                $assignedUser = app(LeadAssignmentService::class)->assign($lead);
                if ($assignedUser) {
                    try {
                        $assignedUser->notify(new NewLeadNotification($lead));
                    } catch (\Throwable $exception) {
                        Log::channel('meta_leads')->error('Falha ao notificar vendedor da lead IA.', [
                            'lead_id' => $lead->id,
                            'assigned_user_id' => $assignedUser->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }

                    app(LeadWhatsappNotificationService::class)->queueForLead($lead->fresh('assigned_user'), $assignedUser);
                }
            }

            $chatLead->update([
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_user_id,
                'status' => 'sent_to_sales',
            ]);

            $lead->setRelation('chatLead', $chatLead->fresh());

            return $lead;
        });
    }

    public function markTakenOver(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['human_takeover' => true, 'status' => 'waiting_human']);
        $conversation->lead?->update(['status' => 'waiting_human']);

        return $conversation->fresh(['lead', 'channel', 'messages']);
    }

    public function handleHumanOutgoingMessage(array $payload): ?ChatConversation
    {
        $channelSlug = Str::slug((string) ($payload['channel'] ?? 'whatsapp')) ?: 'whatsapp';
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? $payload['to'] ?? ''));

        if ($phone === '') {
            return null;
        }

        $conversation = ChatConversation::query()
            ->whereHas('channel', fn ($query) => $query->where('slug', $channelSlug))
            ->where(function ($query) use ($phone) {
                $query->where('customer_phone', $phone)
                    ->orWhere('customer_identifier', $phone)
                    ->orWhereHas('lead', fn ($leadQuery) => $leadQuery->where('phone', $phone));
            })
            ->where('status', '!=', 'closed')
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            return null;
        }

        $message = trim((string) ($payload['message'] ?? $payload['body'] ?? ''));

        if ($message !== '') {
            $conversation->messages()->create([
                'sender' => 'human',
                'message' => $message,
                'external_id' => $payload['message_id'] ?? null,
                'delivery_status' => 'sent',
                'metadata' => Arr::except($payload, ['message', 'body']),
                'sent_at' => now(),
            ]);
        }

        return $this->markTakenOver($conversation);
    }

    public function releaseToAi(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['human_takeover' => false, 'status' => 'active']);
        $conversation->lead?->update(['status' => 'open']);

        return $conversation->fresh(['lead', 'channel', 'messages']);
    }

    private function releaseToAiAfterIdleTakeover(ChatConversation $conversation): ChatConversation
    {
        if (! $conversation->human_takeover || $conversation->status === 'closed') {
            return $conversation;
        }

        $idleMinutes = (int) config('ai_assistant.human_takeover_idle_release_minutes', 5);
        if ($idleMinutes <= 0) {
            return $conversation;
        }

        $lastHumanTouch = $conversation->messages()
            ->where('sender', 'human')
            ->latest('created_at')
            ->value('created_at') ?: $conversation->updated_at;
        $lastHumanTouch = $lastHumanTouch ? Carbon::parse($lastHumanTouch) : null;

        if (! $lastHumanTouch || $lastHumanTouch->gt(now()->subMinutes($idleMinutes))) {
            return $conversation;
        }

        Log::info('AI assistant released after idle human takeover.', [
            'conversation_id' => $conversation->id,
            'idle_minutes' => $idleMinutes,
            'last_human_touch' => $lastHumanTouch->toDateTimeString(),
        ]);

        return $this->releaseToAi($conversation);
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
                'name' => 'Assistente Comercial Car 7',
                'company_name' => config('ai_assistant.company_name', 'Car 7'),
                'commercial_phone' => config('ai_assistant.commercial_phone', '912273402'),
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
        $company = $conversation->assistant->company_name ?: config('ai_assistant.company_name', 'Car 7');
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
        return ! $this->chatOnStandby()
            && (bool) config('ai_assistant.auto_reply_enabled', true)
            && $conversation->status === 'active'
            && ! $conversation->human_takeover;
    }

    private function chatOnStandby(): bool
    {
        return (bool) config('ai_assistant.chat_standby', false);
    }

    private function generateReply(AiAssistant $assistant, ChatConversation $conversation, string $message, ?string $qualificationContext = null): string
    {
        $apiKey = (string) config('ai_assistant.openai_api_key');
        if ($apiKey === '') {
            return $this->fallbackReply($assistant);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    ],
                ])
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('ai_assistant.openai_model', 'gpt-4o-mini'),
                    'messages' => $this->messagesForOpenAi($assistant, $conversation, $message, $qualificationContext),
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

    private function messagesForOpenAi(AiAssistant $assistant, ChatConversation $conversation, string $message, ?string $qualificationContext = null): array
    {
        $messages = [['role' => 'system', 'content' => $this->systemPrompt($assistant)]];

        if ($qualificationContext) {
            $messages[] = ['role' => 'system', 'content' => $qualificationContext];
        }

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

        $company = $assistant->company_name ?: config('ai_assistant.company_name', 'Car 7');
        $phone = $assistant->commercial_phone ?: config('ai_assistant.commercial_phone');

        return implode("\n\n", array_filter([
            "És o assistente virtual comercial da empresa {$company}. Deves ser transparente sobre seres um assistente virtual.",
            "Fala em português de Portugal. Sê curto, educado, útil e comercial. O telefone comercial é {$phone}.",
            "Não inventes dados de viaturas, preços, financiamento, disponibilidade, garantias ou retomas. Se não souberes, encaminha para humano.",
            "Continua a responder até um humano assumir a conversa. Quando o cliente pedir humano, comercial, negociação, retoma, reserva ou financiamento avançado, recolhe o essencial e diz que um comercial pode acompanhar, mas não pares a conversa por iniciativa própria.",
            "Antes de encaminhar uma lead, recolhe estes dados sem parecer um inquerito: nome, telefone quando ainda nao existir um numero real do WhatsApp, segmento ou viatura de interesse, orçamento aproximado, forma de compra, se tem retoma, prazo de compra e se pretende agendar visita. Faz a conversa fluir com uma pergunta curta de cada vez, reagindo ao que o cliente acabou de dizer. Evita listas e frases como \"para completar\", \"para finalizar\", \"campos em falta\" ou \"formulario\".",
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

    private function fallbackReply(AiAssistant $assistant): string
    {
        $company = $assistant->company_name ?: config('ai_assistant.company_name', 'Car 7');
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

<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatLead;
use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiLeadQualificationService
{
    public const REQUIRED_FIELDS = [
        'full_name' => 'nome',
        'phone' => 'telefone',
        'vehicle_interest' => 'segmento ou viatura de interesse',
        'budget' => 'orcamento aproximado',
        'financing' => 'forma de compra',
        'trade_in' => 'se tem retoma',
        'purchase_timeline' => 'prazo de compra',
        'wants_visit' => 'se pretende agendar visita',
    ];

    public function qualificationFor(ChatConversation $conversation): array
    {
        $conversation->loadMissing('lead', 'messages');
        $existing = $this->existingQualification($conversation->lead);
        $extracted = $this->extractWithOpenAi($conversation) ?: $this->extractFallback($conversation);
        $qualification = array_filter(array_merge($existing, $extracted), fn ($value) => filled($value));

        if (blank($qualification['phone'] ?? null)) {
            $qualification['phone'] = $conversation->customer_phone ?: $conversation->lead?->phone;
        }

        if (blank($qualification['full_name'] ?? null)) {
            $qualification['full_name'] = $conversation->lead?->name;
        }

        if (blank($qualification['vehicle_interest'] ?? null)) {
            $qualification['vehicle_interest'] = $conversation->lead?->vehicle_title ?: $conversation->lead?->vehicle_reference;
        }

        $this->storeQualification($conversation->lead, $qualification);

        return $qualification;
    }

    public function missingFields(array $qualification): array
    {
        return collect(self::REQUIRED_FIELDS)
            ->filter(fn ($label, $field) => blank($qualification[$field] ?? null))
            ->all();
    }

    public function isComplete(array $qualification): bool
    {
        return $this->missingFields($qualification) === [];
    }

    public function buildLeadPayload(ChatConversation $conversation, array $qualification): array
    {
        $conversation->loadMissing('lead', 'messages');
        $chatLead = $conversation->lead;

        return [
            'leadgen_id' => 'ai_whatsapp:' . $conversation->id,
            'page_id' => 'ai_whatsapp',
            'form_id' => 'ai_whatsapp',
            'ad_id' => null,
            'adgroup_id' => null,
            'full_name' => $qualification['full_name'] ?? null,
            'first_name' => null,
            'last_name' => null,
            'email' => $qualification['email'] ?? $chatLead?->email,
            'phone' => $qualification['phone'] ?? $conversation->customer_phone,
            'vehicle_interest' => $qualification['vehicle_interest'] ?? null,
            'budget' => $qualification['budget'] ?? null,
            'financing' => $qualification['financing'] ?? null,
            'trade_in' => $qualification['trade_in'] ?? null,
            'raw_data' => [
                'source' => 'ai_whatsapp',
                'chat_conversation_id' => $conversation->id,
                'chat_lead_id' => $chatLead?->id,
                'qualification' => $qualification,
                'purchase_timeline' => $qualification['purchase_timeline'] ?? null,
                'wants_visit' => $qualification['wants_visit'] ?? null,
                'messages' => $conversation->messages()
                    ->orderBy('id')
                    ->get(['sender', 'message', 'created_at'])
                    ->map(fn ($message) => [
                        'sender' => $message->sender,
                        'message' => $message->message,
                        'created_at' => optional($message->created_at)->toDateTimeString(),
                    ])
                    ->all(),
            ],
            'status' => Lead::STATUS_NEW,
        ];
    }

    public function contextForPrompt(array $qualification): string
    {
        $missing = $this->missingFields($qualification);
        $knownLines = collect(self::REQUIRED_FIELDS)
            ->map(fn ($label, $field) => $label . ': ' . (($qualification[$field] ?? null) ?: '-'))
            ->implode("\n");

        return implode("\n\n", array_filter([
            "Estado da recolha de lead:\n{$knownLines}",
            $missing
                ? $this->naturalFollowUpInstruction($missing)
                : 'Todos os campos estao recolhidos. Agradece e diz que um comercial vai acompanhar.',
        ]));
    }

    private function naturalFollowUpInstruction(array $missing): string
    {
        $priority = [
            'vehicle_interest',
            'budget',
            'financing',
            'trade_in',
            'purchase_timeline',
            'wants_visit',
            'full_name',
            'phone',
        ];

        $nextField = collect($priority)->first(fn ($field) => array_key_exists($field, $missing));
        $nextLabel = $nextField ? self::REQUIRED_FIELDS[$nextField] : reset($missing);

        return implode(' ', [
            'Objetivo interno: ainda e preciso recolher ' . implode(', ', array_values($missing)) . '.',
            'Na proxima resposta, nao enumeres esta lista e nao uses as palavras "formulario", "campo", "falta", "completar" ou "finalizar".',
            'Responde primeiro ao que o cliente disse e faz apenas uma pergunta curta e natural sobre: ' . $nextLabel . '.',
            'So juntes duas perguntas se forem inseparaveis no contexto; caso contrario, deixa a conversa respirar.',
        ]);
    }

    private function existingQualification(?ChatLead $chatLead): array
    {
        if (! $chatLead || blank($chatLead->ai_notes)) {
            return [];
        }

        $data = json_decode($chatLead->ai_notes, true);

        return is_array($data) ? (array) ($data['qualification'] ?? []) : [];
    }

    private function storeQualification(?ChatLead $chatLead, array $qualification): void
    {
        if (! $chatLead) {
            return;
        }

        $chatLead->update([
            'name' => $qualification['full_name'] ?? $chatLead->name,
            'phone' => $qualification['phone'] ?? $chatLead->phone,
            'vehicle_title' => $qualification['vehicle_interest'] ?? $chatLead->vehicle_title,
            'budget_max' => $this->parseMoney($qualification['budget'] ?? null) ?: $chatLead->budget_max,
            'wants_financing' => $this->truthyText($qualification['financing'] ?? null, ['financiamento', 'credito', 'crédito']),
            'has_trade_in' => $this->truthyText($qualification['trade_in'] ?? null, ['sim', 'yes', 'tenho']),
            'ai_notes' => json_encode(['qualification' => $qualification], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function extractWithOpenAi(ChatConversation $conversation): ?array
    {
        $apiKey = (string) config('ai_assistant.openai_api_key');
        if ($apiKey === '') {
            return null;
        }

        $history = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => strtoupper($message->sender) . ': ' . $message->message)
            ->implode("\n");

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('ai_assistant.openai_model', 'gpt-4o-mini'),
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Extrai dados de uma conversa de lead automovel em JSON. Usa null quando nao houver resposta clara. Chaves: full_name, phone, email, vehicle_interest, budget, financing, trade_in, purchase_timeline, wants_visit.',
                        ],
                        ['role' => 'user', 'content' => $history],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI qualification request failed.', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                return null;
            }

            $content = (string) Arr::get($response->json(), 'choices.0.message.content');
            $data = json_decode($content, true);

            return is_array($data) ? $this->cleanQualification($data) : null;
        } catch (\Throwable $exception) {
            Log::warning('OpenAI qualification exception.', ['error' => $exception->getMessage()]);

            return null;
        }
    }

    private function extractFallback(ChatConversation $conversation): array
    {
        $text = $conversation->messages()
            ->where('sender', 'customer')
            ->pluck('message')
            ->implode("\n");

        $data = [];
        if (preg_match('/\b(?:chamo-me|sou|nome e|nome é)\s+([A-ZÁÀÂÃÉÊÍÓÔÕÚÇ][\pL\s]{2,80})/iu', $text, $match)) {
            $data['full_name'] = trim($match[1]);
        }
        if (preg_match('/(\+?\d[\d\s]{8,16})/', $text, $match)) {
            $data['phone'] = trim($match[1]);
        }
        foreach (['suv', 'familiar', 'citadino', 'desportivo', 'comercial'] as $segment) {
            if (Str::contains(Str::lower($text), $segment)) {
                $data['vehicle_interest'] = $segment;
                break;
            }
        }
        if (preg_match('/(\d{1,3}(?:[ .]\d{3})*(?:,\d+)?\s*€?)/u', $text, $match)) {
            $data['budget'] = trim($match[1]);
        }
        if (Str::contains(Str::lower($text), ['financiamento', 'credito', 'crédito'])) {
            $data['financing'] = 'financiamento';
        } elseif (Str::contains(Str::lower($text), ['pronto pagamento', 'a pronto'])) {
            $data['financing'] = 'pronto pagamento';
        }
        if (Str::contains(Str::lower($text), ['retoma'])) {
            $data['trade_in'] = Str::contains(Str::lower($text), ['sem retoma', 'nao tenho retoma', 'não tenho retoma']) ? 'não' : 'sim';
        }
        if (Str::contains(Str::lower($text), ['hoje', 'semana', 'mes', 'mês', 'urgente'])) {
            $data['purchase_timeline'] = 'curto prazo';
        }
        if (Str::contains(Str::lower($text), ['visita', 'ver o carro', 'test drive'])) {
            $data['wants_visit'] = 'sim';
        }

        return $this->cleanQualification($data);
    }

    private function cleanQualification(array $data): array
    {
        $allowed = array_keys(self::REQUIRED_FIELDS + ['email' => 'email']);

        return collect($data)
            ->only($allowed)
            ->map(function ($value) {
                if (is_bool($value)) {
                    return $value ? 'sim' : 'não';
                }

                return is_array($value) ? implode(', ', array_filter($value)) : $value;
            })
            ->map(fn ($value) => blank($value) ? null : trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->all();
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
        $number = str_replace(['.', ','], ['', '.'], $number);

        return is_numeric($number) ? (float) $number : null;
    }

    private function truthyText(?string $value, array $needles): bool
    {
        return filled($value) && Str::contains(Str::lower($value), $needles);
    }
}

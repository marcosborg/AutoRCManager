<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsappCloudApi
{
    public function sendText(string $to, string $message): array
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message],
        ]);
    }

    public function sendTemplate(string $to, string $name, string $language, array $bodyParameters, ?string $buttonUrlParameter = null): array
    {
        $components = [[
            'type' => 'body',
            'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => (string) $value], $bodyParameters),
        ]];

        if ($buttonUrlParameter !== null && $buttonUrlParameter !== '') {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $buttonUrlParameter]],
            ];
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'template',
            'template' => [
                'name' => $name,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    private function send(array $payload): array
    {
        $phoneNumberId = (string) config('whatsapp.phone_number_id');
        if ($phoneNumberId === '' || (string) config('whatsapp.access_token') === '') {
            throw new RuntimeException('WhatsApp Cloud API credentials are not configured.');
        }

        $response = $this->request()->post($phoneNumberId . '/messages', $payload);
        if ($response->failed()) {
            throw new RuntimeException('WhatsApp Cloud API error (' . $response->status() . '): ' . $response->body());
        }

        return $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl('https://graph.facebook.com/' . config('whatsapp.graph_version'))
            ->withToken((string) config('whatsapp.access_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9) {
            $digits = '351' . $digits;
        }

        if (! preg_match('/^\d{8,15}$/', $digits)) {
            throw new RuntimeException('Invalid WhatsApp destination phone number.');
        }

        return $digits;
    }
}

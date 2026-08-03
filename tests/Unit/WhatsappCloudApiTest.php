<?php

namespace Tests\Unit;

use App\Services\WhatsappCloudApi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappCloudApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'whatsapp.graph_version' => 'v25.0',
            'whatsapp.phone_number_id' => '123456',
            'whatsapp.access_token' => 'cloud-token',
        ]);
    }

    public function test_it_sends_a_text_message(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $result = app(WhatsappCloudApi::class)->sendText('912345678', 'Olá');

        $this->assertSame('wamid.1', data_get($result, 'messages.0.id'));
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v25.0/123456/messages'
            && $request->hasHeader('Authorization', 'Bearer cloud-token')
            && $request['to'] === '351912345678'
            && $request['type'] === 'text');
    }

    public function test_it_sends_a_template_with_a_dynamic_url_button(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.2']]])]);

        app(WhatsappCloudApi::class)->sendTemplate('351912345678', 'template_name', 'pt_PT', ['Um', 'Dois'], 'token');

        Http::assertSent(fn ($request) => data_get($request->data(), 'template.name') === 'template_name'
            && data_get($request->data(), 'template.components.1.parameters.0.text') === 'token');
    }
}

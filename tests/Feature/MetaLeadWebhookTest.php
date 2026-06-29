<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadWhatsappNotification;
use App\Models\LeadSalesRotation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MetaLeadWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['title' => 'Stand']);
        DB::table('role_user')->where('role_id', $role->id)->delete();
        LeadSalesRotation::query()->delete();
    }

    public function test_webhook_verification_accepts_valid_token(): void
    {
        config(['services.meta.verify_token' => 'token-teste']);

        $this->getJson('/api/meta/webhook?hub.mode=subscribe&hub.verify_token=token-teste&hub.challenge=desafio')
            ->assertOk()
            ->assertSee('desafio');
    }

    public function test_webhook_verification_rejects_invalid_token(): void
    {
        config(['services.meta.verify_token' => 'token-teste']);

        $this->getJson('/api/meta/webhook?hub.mode=subscribe&hub.verify_token=errado&hub.challenge=desafio')
            ->assertForbidden();
    }

    public function test_webhook_creates_lead_and_assigns_round_robin_seller(): void
    {
        Notification::fake();
        config([
            'ai_assistant.lead_whatsapp_cc_phones' => ['912239578', '913333333'],
            'services.meta.form_id' => '829801293296262',
            'services.meta.access_token' => 'page-token',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $seller = $this->seller('Vendedor Meta');

        Http::fake([
            'graph.facebook.com/v25.0/lead-1*' => Http::response([
                'id' => 'lead-1',
                'created_time' => now()->toIso8601String(),
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Cliente Teste']],
                    ['name' => 'email', 'values' => ['cliente@example.com']],
                    ['name' => 'phone_number', 'values' => ['912345678']],
                    ['name' => 'vehicle_interest', 'values' => ['BMW Serie 1']],
                    ['name' => 'budget', 'values' => ['15000']],
                    ['name' => 'financing', 'values' => ['Sim']],
                    ['name' => 'trade_in', 'values' => ['Nao']],
                ],
            ]),
        ]);

        $payload = $this->webhookPayload('lead-1');

        $this->postJson('/api/meta/webhook', $payload)->assertOk();

        $lead = Lead::where('leadgen_id', 'lead-1')->firstOrFail();

        $this->assertSame('Cliente Teste', $lead->full_name);
        $this->assertSame('cliente@example.com', $lead->email);
        $this->assertSame('912345678', $lead->phone);
        $this->assertSame('BMW Serie 1', $lead->vehicle_interest);
        $this->assertSame('15000', $lead->budget);
        $this->assertSame($seller->id, $lead->assigned_user_id);
        $this->assertCount(1, $lead->assignment_histories);

        Notification::assertSentTo($seller, NewLeadNotification::class);

        $notifications = LeadWhatsappNotification::where('lead_id', $lead->id)->get();
        $this->assertCount(1, $notifications);
        $this->assertSame($seller->id, $notifications->first()->user_id);
        $this->assertSame('351912000001', $notifications->first()->phone);
        $this->assertSame(LeadWhatsappNotification::STATUS_PENDING, $notifications->first()->status);
    }

    public function test_webhook_is_idempotent_for_duplicate_leadgen_id(): void
    {
        Notification::fake();
        config([
            'services.meta.form_id' => '829801293296262',
            'services.meta.access_token' => 'page-token',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $this->seller('Vendedor Meta');

        Http::fake([
            'graph.facebook.com/v25.0/lead-duplicado*' => Http::response([
                'id' => 'lead-duplicado',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Cliente Duplicado']],
                ],
            ]),
        ]);

        $payload = $this->webhookPayload('lead-duplicado');

        $this->postJson('/api/meta/webhook', $payload)->assertOk();
        $this->postJson('/api/meta/webhook', $payload)->assertOk();

        $this->assertSame(1, Lead::where('leadgen_id', 'lead-duplicado')->count());
    }

    public function test_webhook_ignores_other_configured_form_ids(): void
    {
        config(['services.meta.form_id' => '829801293296262']);
        Http::fake();

        $this->postJson('/api/meta/webhook', $this->webhookPayload('lead-outro-form', 'outro-form'))
            ->assertOk();

        $this->assertDatabaseMissing('leads', ['leadgen_id' => 'lead-outro-form']);
        Http::assertNothingSent();
    }

    public function test_lead_can_remain_unassigned_when_no_stand_seller_exists(): void
    {
        config([
            'services.meta.form_id' => '829801293296262',
            'services.meta.access_token' => 'page-token',
            'services.meta.graph_version' => 'v25.0',
        ]);

        Role::firstOrCreate(['title' => 'Stand'])->users()->detach();

        Http::fake([
            'graph.facebook.com/v25.0/lead-sem-vendedor*' => Http::response([
                'id' => 'lead-sem-vendedor',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Cliente Sem Vendedor']],
                ],
            ]),
        ]);

        $this->postJson('/api/meta/webhook', $this->webhookPayload('lead-sem-vendedor'))->assertOk();

        $lead = Lead::where('leadgen_id', 'lead-sem-vendedor')->firstOrFail();

        $this->assertNull($lead->assigned_user_id);
    }

    public function test_webhook_assigns_whatsapp_leads_one_by_one_to_each_seller(): void
    {
        Notification::fake();
        config([
            'ai_assistant.lead_delivery_channel' => 'smtp',
            'services.meta.form_id' => '829801293296262',
            'services.meta.access_token' => 'page-token',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $fabio = $this->seller('Fabio', '912000001');
        $nuno = $this->seller('Nuno', '912000002');
        $sergio = $this->seller('Sergio', '912000003');

        Http::fake([
            'graph.facebook.com/v25.0/lead-round-*' => function ($request) {
                $leadgenId = basename(parse_url($request->url(), PHP_URL_PATH));

                return Http::response([
                    'id' => $leadgenId,
                    'field_data' => [
                        ['name' => 'full_name', 'values' => ['Cliente ' . $leadgenId]],
                        ['name' => 'phone_number', 'values' => ['912345678']],
                    ],
                ]);
            },
        ]);

        foreach (['lead-round-1', 'lead-round-2', 'lead-round-3'] as $leadgenId) {
            $this->postJson('/api/meta/webhook', $this->webhookPayload($leadgenId))->assertOk();
        }

        $this->assertSame($fabio->id, Lead::where('leadgen_id', 'lead-round-1')->firstOrFail()->assigned_user_id);
        $this->assertSame($nuno->id, Lead::where('leadgen_id', 'lead-round-2')->firstOrFail()->assigned_user_id);
        $this->assertSame($sergio->id, Lead::where('leadgen_id', 'lead-round-3')->firstOrFail()->assigned_user_id);

        $this->assertSame(3, LeadWhatsappNotification::whereIn('lead_id', Lead::whereIn('leadgen_id', [
            'lead-round-1',
            'lead-round-2',
            'lead-round-3',
        ])->pluck('id'))->count());
    }

    public function test_sellers_without_mobile_phone_are_not_eligible_for_whatsapp_rotation(): void
    {
        config([
            'services.meta.form_id' => '829801293296262',
            'services.meta.access_token' => 'page-token',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $this->seller('Sem Telemovel', null);

        Http::fake([
            'graph.facebook.com/v25.0/lead-sem-telemovel*' => Http::response([
                'id' => 'lead-sem-telemovel',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Cliente Sem Telemovel']],
                ],
            ]),
        ]);

        $this->postJson('/api/meta/webhook', $this->webhookPayload('lead-sem-telemovel'))->assertOk();

        $lead = Lead::where('leadgen_id', 'lead-sem-telemovel')->firstOrFail();

        $this->assertNull($lead->assigned_user_id);
    }

    private function webhookPayload(string $leadgenId, string $formId = '829801293296262'): array
    {
        return [
            'object' => 'page',
            'entry' => [[
                'changes' => [[
                    'field' => 'leadgen',
                    'value' => [
                        'leadgen_id' => $leadgenId,
                        'page_id' => 'page-1',
                        'form_id' => $formId,
                        'ad_id' => 'ad-1',
                        'adgroup_id' => 'adset-1',
                    ],
                ]],
            ]],
        ];
    }

    private function seller(string $name, ?string $mobilePhone = '912000001'): User
    {
        $role = Role::firstOrCreate(['title' => 'Stand']);
        $seller = User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'mobile_phone' => $mobilePhone,
        ]);

        $seller->roles()->syncWithoutDetaching([$role->id]);

        return $seller;
    }
}

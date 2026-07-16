<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadAccessToken;
use App\Models\LeadAssignmentHistory;
use App\Models\LeadContactEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeadPerformanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_open_and_contact_clicks_are_recorded_and_deduplicated_in_the_ranking(): void
    {
        [$seller, $lead, $assignment, $plainToken] = $this->trackedOpportunity('form');

        $this->get(route('lead-access.show', $plainToken))->assertOk();
        $this->get(route('lead-access.contact', [$plainToken, 'call']))
            ->assertRedirectContains('tel:+351912345678');
        $this->get(route('lead-access.contact', [$plainToken, 'call']))
            ->assertRedirectContains('tel:+351912345678');
        $this->get(route('lead-access.contact', [$plainToken, 'whatsapp']))
            ->assertRedirectContains('https://wa.me/351912345678');

        $this->assertSame(3, LeadContactEvent::where('assignment_history_id', $assignment->id)->count());

        $admin = $this->userWithRole('Admin', ['lead_performance_access', 'lead_access', 'lead_show']);
        $response = $this->actingAs($admin)->get(route('admin.leads.performance'));

        $response->assertOk()
            ->assertSee($seller->name)
            ->assertSee('100,0%')
            ->assertSee('Medição disponível desde');

        $detail = $this->actingAs($admin)->get(route('admin.leads.performance', [
            'seller_id' => $seller->id,
            'metric' => 'contacted',
        ]));
        $detail->assertOk()->assertSee($lead->full_name)->assertSee('Telefone, WhatsApp')->assertSee('>3<', false);
    }

    public function test_contact_requires_an_open_current_instrumented_assignment(): void
    {
        [$seller, $lead, $assignment, $plainToken, $token] = $this->trackedOpportunity('form');

        $this->get(route('lead-access.contact', [$plainToken, 'call']))->assertGone();

        $this->get(route('lead-access.show', $plainToken))->assertOk();
        $replacement = $this->userWithRole('Stand', []);
        $lead->update(['assigned_user_id' => $replacement->id]);
        $this->get(route('lead-access.contact', [$plainToken, 'call']))->assertGone();

        $this->assertDatabaseCount('lead_contact_events', 0);
        $this->assertSame($assignment->id, $token->assignment_history_id);
        $this->assertSame($seller->id, $token->user_id);
    }

    public function test_report_separates_sources_channels_and_assignment_opportunities(): void
    {
        $first = $this->trackedOpportunity('form');
        $second = $this->trackedOpportunity('whatsapp', $first[0]);

        $this->get(route('lead-access.show', $second[3]));
        $this->get(route('lead-access.contact', [$second[3], 'whatsapp']));

        $admin = $this->userWithRole('Adm', ['lead_performance_access', 'lead_access', 'lead_show']);
        $all = $this->actingAs($admin)->get(route('admin.leads.performance'));
        $all->assertOk()->assertSee('50,0%');

        $whatsapp = $this->actingAs($admin)->get(route('admin.leads.performance', ['source' => 'whatsapp']));
        $whatsapp->assertOk()->assertSee('100,0%');

        $call = $this->actingAs($admin)->get(route('admin.leads.performance', ['channel' => 'call']));
        $call->assertOk()->assertDontSee('100,0%');
    }

    public function test_old_uninstrumented_assignments_are_excluded_and_stand_is_forbidden(): void
    {
        $seller = $this->userWithRole('Stand', []);
        $lead = $this->lead($seller, 'form');
        $legacyHistory = LeadAssignmentHistory::create(['lead_id' => $lead->id, 'user_id' => $seller->id, 'reason' => 'legacy']);
        $legacyHistory->created_at = now()->subDay();
        $legacyHistory->save();
        $legacyToken = LeadAccessToken::create([
            'lead_id' => $lead->id,
            'user_id' => $seller->id,
            'token_hash' => hash('sha256', Str::random(72)),
            'expires_at' => now()->addDay(),
            'last_used_at' => now(),
        ]);
        $legacyToken->created_at = now()->subDay()->addMinute();
        $legacyToken->save();
        $admin = $this->userWithRole('Admin', ['lead_performance_access']);

        $this->actingAs($admin)->get(route('admin.leads.performance'))
            ->assertOk()
            ->assertSee('Sem oportunidades instrumentadas neste período.')
            ->assertSee('Histórico anterior à medição de contactos')
            ->assertSee($seller->name)
            ->assertSee('Não mensurável');
        $this->actingAs($seller)->get(route('admin.leads.performance'))->assertForbidden();
    }

    public function test_admin_can_export_the_filtered_report_as_pdf(): void
    {
        $this->trackedOpportunity('form');
        $admin = $this->userWithRole('Admin', ['lead_performance_access']);

        $response = $this->actingAs($admin)->get(route('admin.leads.performance.pdf', [
            'date_start' => now()->startOfYear()->format('Y-m-d'),
            'date_end' => now()->endOfYear()->format('Y-m-d'),
            'source' => 'form',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', strtolower((string) $response->headers->get('content-disposition')));
    }

    private function trackedOpportunity(string $source, ?User $seller = null, ?Lead $lead = null): array
    {
        $seller ??= $this->userWithRole('Stand', []);
        $lead ??= $this->lead($seller, $source);
        if ($source === 'whatsapp') {
            $lead->update(['form_id' => 'ai_whatsapp', 'raw_data' => ['source' => 'ai_whatsapp']]);
        }
        $assignment = LeadAssignmentHistory::create([
            'lead_id' => $lead->id,
            'user_id' => $seller->id,
            'reason' => 'round_robin',
        ]);
        $plainToken = Str::random(72);
        $token = LeadAccessToken::create([
            'lead_id' => $lead->id,
            'user_id' => $seller->id,
            'assignment_history_id' => $assignment->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
            'first_open_deadline_at' => now()->addHour(),
        ]);

        return [$seller, $lead, $assignment, $plainToken, $token];
    }

    private function lead(User $seller, string $source): Lead
    {
        return Lead::create([
            'leadgen_id' => ($source === 'whatsapp' ? 'ai_whatsapp:' : 'form:').Str::random(16),
            'page_id' => 'test',
            'form_id' => $source === 'whatsapp' ? 'ai_whatsapp' : 'make',
            'full_name' => 'Cliente '.Str::random(6),
            'phone' => '351912345678',
            'assigned_user_id' => $seller->id,
            'status' => Lead::STATUS_NEW,
            'raw_data' => $source === 'whatsapp' ? ['source' => 'ai_whatsapp'] : ['source' => 'make'],
        ]);
    }

    private function userWithRole(string $roleTitle, array $permissionTitles): User
    {
        $role = Role::firstOrCreate(['title' => $roleTitle]);
        foreach ($permissionTitles as $title) {
            $role->permissions()->syncWithoutDetaching([Permission::firstOrCreate(['title' => $title])->id]);
        }
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}

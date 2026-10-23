<?php
namespace Tests\Feature;

use App\Models\{User, Role, Permission, Vehicle, Suplier, GeneralState};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkshopCommercialPrivacyTest extends TestCase
{
    use DatabaseTransactions;

    private function userFor(string $title): User
    {
        $user = User::factory()->create(['name' => 'rita']);
        $role = Role::firstOrCreate(['title' => $title]);
        foreach (['vehicle_access','vehicle_edit','vehicle_show','financial_sensitive_access','aquisition_of_the_vehicle','suplier_access','client_access','sale_access','account_access','repair_access','part_order_access','external_service_access','workshop_state_edit'] as $ability) {
            $permission = Permission::firstOrCreate(['title'=>$ability]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user->roles()->sync([$role->id]);
        return $user;
    }

    public function test_both_workshop_roles_are_restricted_even_with_commercial_permissions(): void
    {
        $supplier = Suplier::create(['name'=>'PRIVATE PURCHASE SUPPLIER']);
        $state = GeneralState::firstOrCreate(['name'=>'Oficina']);
        $v = Vehicle::create(['license'=>'SAFE-ROLE-TEST','model'=>'Modelo oficina','general_state_id'=>$state->id,'suplier_id'=>$supplier->id,'purchase_price'=>12345,'pvp'=>23456,'sale_notes'=>'PRIVATE SALE NOTES','kilometers'=>10]);
        $file = $v->media()->create(['collection_name'=>'inicial','name'=>'Private initial','file_name'=>'private-initial.jpg','disk'=>'public','size'=>1,'manipulations'=>[],'custom_properties'=>[],'generated_conversions'=>[],'responsive_images'=>[]]);
        foreach (['Aux. oficina','Chefe oficina'] as $role) {
            $this->actingAs($this->userFor($role));
            $this->get(route('admin.home'))->assertOk()->assertDontSee('Volume anual')->assertDontSee('Volume deste mes')->assertDontSee('Viaturas vendidas')->assertSee('Encomendas de peças');
            foreach (['edit','show'] as $action) {
                $this->get(route('admin.vehicles.'.$action,$v))->assertOk()->assertSee('Modelo oficina')->assertDontSee('PRIVATE PURCHASE SUPPLIER')->assertDontSee('PRIVATE SALE NOTES')->assertDontSee('12345')->assertDontSee('23456')->assertDontSee('private-initial.jpg')->assertDontSee('Preparação e venda');
            }
            $this->get(route('admin.vehicles.index'))->assertOk()->assertDontSee('PRIVATE PURCHASE SUPPLIER')->assertDontSee('Financeiro');
            $this->getJson(route('admin.vehicles.index'), ['X-Requested-With'=>'XMLHttpRequest'])->assertOk()->assertJsonMissingPath('data.0.purchase_price')->assertJsonMissingPath('data.0.inicial')->assertJsonMissingPath('data.0.suplier');
            foreach (['admin.supliers.index','admin.clients.index','admin.sales.index','admin.dashboard'] as $route) $this->get(route($route))->assertForbidden();
            $this->get(route('admin.vehicles.timeline',$v))->assertForbidden();
            $this->get(route('admin.vehicles.documents.followup',[$v,'vehicle_ownership_title']))->assertForbidden();
            $token = $this->app['auth']->user()->createToken('privacy-test')->plainTextToken;
            $headers = ['Authorization' => 'Bearer '.$token];
            $this->getJson('/api/v1/vehicles/'.$v->id, $headers)->assertOk()->assertJsonMissingPath('data.purchase_price')->assertJsonMissingPath('data.suplier')->assertJsonMissingPath('data.inicial');
            $this->putJson('/api/v1/vehicles/'.$v->id, ['pvp'=>0, 'kilometers'=>15], $headers)->assertOk();
            $this->assertEquals(23456,$v->fresh()->pvp);
            $this->getJson('/api/v1/gestao/dashboard', $headers)->assertForbidden();
            $this->assertTrue(\Gate::allows('repair_access'));
            $this->assertTrue(\Gate::allows('part_order_access'));
            $this->get(route('admin.part-orders.index'))->assertOk()->assertSee('PRIVATE PURCHASE SUPPLIER');
            $this->assertFalse(\Gate::allows('financial_sensitive_access'));
            $this->assertSame([], $v->fresh()->inicial->all());
            $this->assertArrayNotHasKey('pvp', $v->fresh()->toArray());
            $this->put(route('admin.vehicles.update',$v), ['kilometers'=>20,'purchase_price'=>1,'pvp'=>1,'suplier_id'=>null,'inicial'=>[],'sale_notes'=>'HACK','is_invoiced'=>true])->assertRedirect();
            $this->assertEquals(20,$v->fresh()->kilometers);
            $this->assertEquals(12345,$v->fresh()->purchase_price);
            $this->assertEquals(23456,$v->fresh()->pvp);
            $this->assertEquals($supplier->id,$v->fresh()->suplier_id);
            $this->assertSame('PRIVATE SALE NOTES',$v->fresh()->sale_notes);
            $this->assertDatabaseHas('media',['id'=>$file->id]);
        }
    }

    public function test_other_roles_retain_their_finance_permissions(): void
    {
        $this->actingAs($this->userFor('Privacy test management'));
        $this->get(route('admin.home'))->assertOk()->assertSee('Volume anual');
        $this->assertTrue(\Gate::allows('financial_sensitive_access'));
        $this->assertTrue(\Gate::allows('suplier_access'));
    }
    public function test_token_only_requests_do_not_bypass_workshop_privacy(): void
    {
        $u = $this->userFor('Chefe oficina');
        $v = Vehicle::create(['license'=>'TOKEN-PRIVACY','pvp'=>98765]);
        $headers = ['Authorization'=>'Bearer '.$u->createToken('privacy-test')->plainTextToken];
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/gestao/dashboard', $headers)->assertForbidden();
        $this->getJson('/api/v1/vehicles/'.$v->id, $headers)->assertOk()->assertJsonMissingPath('data.pvp');
    }

}

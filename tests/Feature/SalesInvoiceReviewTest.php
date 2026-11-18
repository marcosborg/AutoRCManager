<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Client, GeneralState, Permission, Role, User, Vehicle, VehicleSuspendedSale};
use App\Services\SalesInvoiceReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalesInvoiceReviewTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $permissions = ['vehicle_access', 'sale_access', 'vehicle_documents_area_access', 'vehicle_edit'], ?string $title = null): User
    {
        $role = Role::create(['title' => $title ?? uniqid('Invoice review ')]);
        foreach ($permissions as $permission) $role->permissions()->attach(Permission::firstOrCreate(['title' => $permission])->id);
        $user = User::factory()->create(); $user->roles()->attach($role);
        return $user;
    }

    private function vehicle(string $category = 'VENDIDO STAND', array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge(['license' => uniqid('INV16'), 'model' => 'Invoice review',
            'general_state_id' => GeneralState::firstOrCreate(['name' => $category])->id, 'is_invoiced' => false], $attributes));
    }

    private function file(Vehicle $vehicle, array $properties = [], string $collection = 'documents')
    {
        return $vehicle->media()->create(['collection_name' => $collection, 'name' => 'Invoice test', 'file_name' => 'test.pdf',
            'disk' => 'public', 'size' => 1, 'manipulations' => [], 'custom_properties' => $properties,
            'generated_conversions' => [], 'responsive_images' => []]);
    }

    private function review(array $filters = [])
    {
        return $this->get(route('admin.sales.index', ['review' => 'invoice'] + $filters))->assertOk();
    }

    public function test_all_four_categories_are_included_but_stock_and_suspensions_are_separate(): void
    {
        $this->actingAs($this->user());
        foreach (SalesInvoiceReviewService::CATEGORIES as $name) {
            $v = $this->vehicle($name); $this->review(['search' => $v->license])->assertSee('data-vehicle-id="'.$v->id.'"', false);
        }
        $stock = $this->vehicle('Em stock disponível', ['sale_date' => '2026-11-01']);
        $suspended = $this->vehicle('VENDA SUSPENSA');
        $active = $this->vehicle();
        VehicleSuspendedSale::create(['vehicle_id' => $active->id, 'client_id' => Client::create(['name' => 'Suspended review client'])->id, 'status' => 'active', 'suspended_at' => now()]);
        foreach ([$stock, $suspended, $active] as $v) $this->review(['search' => $v->license])->assertDontSee('data-vehicle-id="'.$v->id.'"', false);
        foreach ([$suspended, $active] as $v) $this->review(['scope' => 'suspended', 'reason' => 'all', 'search' => $v->license])->assertSee('data-vehicle-id="'.$v->id.'"', false);
    }

    public function test_invoice_flag_and_file_are_independent_and_unknown_or_annulled_files_do_not_count(): void
    {
        $this->actingAs($this->user());
        foreach ([[false,false], [true,false], [false,true], [true,true]] as [$flag,$file]) {
            $v = $this->vehicle('VENDIDO STAND', ['is_invoiced' => $flag]);
            if ($file) $this->file($v, ['document_classification' => ['type' => 'sale_invoice']]);
            $rows = $this->review(['search' => $v->license])->viewData('vehicles');
            $this->assertSame($flag && $file ? 0 : 1, $rows->total());
            $all = $this->review(['search' => $v->license, 'reason' => 'all'])->viewData('vehicles');
            $this->assertSame($file ? 1 : 0, (int)$all->first()->sale_invoice_count);
            foreach (['unmarked' => !$flag, 'missing_file' => $flag && !$file, 'file_unmarked' => !$flag && $file] as $reason => $expected) {
                $this->assertSame($expected ? 1 : 0, $this->review(['search' => $v->license, 'reason' => $reason])->viewData('vehicles')->total());
            }
        }
        $v = $this->vehicle('VENDIDO SALVADOS', ['is_invoiced' => true]);
        $this->file($v);
        $this->file($v, ['document_classification' => ['type' => 'purchase_invoice']]);
        $this->file($v, ['document_classification' => ['type' => 'sale_invoice'], 'document_annulment' => ['reason' => 'Engano']]);
        $this->file($v, ['document_classification' => ['type' => 'sale_invoice']], 'photos');
        $this->assertSame(0, (int)$this->review(['search' => $v->license])->viewData('vehicles')->first()->sale_invoice_count);
    }

    public function test_filters_combine_and_reads_preserve_values_and_history(): void
    {
        $this->actingAs($this->user());
        $client = Client::create(['name' => uniqid('ClienteInvoice')]);
        $v = $this->vehicle('VENDIDO A COMÉRCIO', ['license' => 'XY-16-ZZ', 'client_id' => $client->id, 'sale_date' => '2026-11-10', 'pvp' => 12345]);
        $before = $v->fresh()->getAttributes(); $audit = AuditLog::count();
        $this->review(['search' => 'XY16ZZ', 'category' => 'comercio', 'from' => '2026-11-01', 'to' => '2026-11-30'])->assertSee($v->license);
        $this->review(['search' => $client->name])->assertSee($v->license);
        $this->assertSame(0, $this->review(['search' => $v->license, 'category' => 'stand'])->viewData('vehicles')->total());
        $this->assertSame(0, $this->review(['search' => $v->license, 'to' => '2026-11-09'])->viewData('vehicles')->total());
        $this->assertSame($before, $v->fresh()->getAttributes()); $this->assertSame($audit, AuditLog::count());
    }

    public function test_missing_dates_and_archived_states_remain_visible_and_deleted_vehicles_do_not(): void
    {
        $this->actingAs($this->user()); $v = $this->vehicle();
        $v->general_state->delete();
        $this->review(['search' => $v->license])->assertSee($v->license)->assertSee('Por confirmar');
        $this->assertSame(0, $this->review(['search' => $v->license, 'from' => '2026-01-01'])->viewData('vehicles')->total());
        \DB::table('vehicles')->where('id', $v->id)->update(['deleted_at' => now()]);
        $this->assertSame(0, $this->review(['search' => $v->license])->viewData('vehicles')->total());
    }

    public function test_permissions_and_invalid_filters_are_enforced(): void
    {
        foreach ([[], ['vehicle_access'], ['vehicle_access','sale_access']] as $abilities) {
            $this->actingAs($this->user($abilities))->get(route('admin.sales.index', ['review' => 'invoice']))->assertForbidden();
        }
        foreach (['Aux. oficina','Chefe oficina'] as $title) {
            $this->actingAs($this->user(['vehicle_access','sale_access','vehicle_documents_area_access','vehicle_edit'], $title))
                ->get(route('admin.sales.index', ['review' => 'invoice']))->assertForbidden();
        }
        $this->actingAs($this->user());
        foreach ([['scope' => 'bad'], ['category' => 'bad'], ['reason' => 'bad'], ['from' => '2026-11-20','to' => '2026-11-01']] as $filters) {
            $this->getJson(route('admin.sales.index', ['review' => 'invoice'] + $filters))->assertUnprocessable();
        }
    }

    public function test_consultation_without_edit_permission_has_no_edit_link_and_is_paginated(): void
    {
        $this->actingAs($this->user(['vehicle_access','sale_access','vehicle_documents_area_access']));
        $prefix = uniqid('PAG16');
        for ($i=0; $i<26; $i++) $v = $this->vehicle('VENDIDO STAND', ['license' => $prefix.$i]);
        $r = $this->review(['search' => $prefix]);
        $this->assertSame(26, $r->viewData('vehicles')->total());
        $this->assertCount(25, $r->viewData('vehicles')->items());
        $r->assertDontSee(route('admin.vehicles.edit', $v));
        $this->assertCount(1, $this->review(['search' => $prefix, 'page' => 2])->viewData('vehicles')->items());
    }
}

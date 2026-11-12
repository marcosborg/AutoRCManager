<?php

namespace Tests\Feature;

use App\Models\{Permission, Role, Suplier, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplierFullExportTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(bool $allowed = true): User
    {
        $role = Role::create(['title' => 'Supplier export '.uniqid()]);
        if ($allowed) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => 'suplier_access']));
        }
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function params(string $search): array
    {
        return [
            'export' => 'csv', 'start' => 10, 'length' => 5,
            'search' => ['value' => $search, 'regex' => 'false'],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ];
    }

    public function test_export_contains_all_filtered_rows_not_just_current_page(): void
    {
        $prefix = 'Export '.uniqid();
        $ids = [];
        for ($i = 0; $i < 31; $i++) {
            $ids[] = Suplier::create(['name' => $prefix.' '.$i])->id;
        }
        $deleted = Suplier::create(['name' => $prefix.' deleted']);
        $deleted->delete();
        Suplier::create(['name' => 'Unrelated supplier']);
        $response = $this->actingAs($this->actor())->get(route('admin.supliers.index', $this->params($prefix)));
        $response->assertOk()->assertDownload();
        $lines = explode("\n", trim(substr($response->streamedContent(), 3)));
        $this->assertCount(32, $lines);
        $this->assertSame(['ID', 'Nome'], str_getcsv(array_shift($lines), ';'));
        $this->assertSame(array_reverse($ids), array_map(fn ($line) => (int) str_getcsv($line, ';')[0], $lines));
    }

    public function test_column_search_and_formula_protection(): void
    {
        $prefix = 'Safe'.uniqid();
        Suplier::create(['name' => '=1+1 '.$prefix]);
        Suplier::create(['name' => 'Other '.uniqid()]);
        $params = $this->params('');
        $params['columns'][1]['search']['value'] = $prefix;
        $response = $this->actingAs($this->actor())->get(route('admin.supliers.index', $params));
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertCount(2, explode("\n", trim($csv)));
    }

    public function test_export_requires_permission_and_valid_format(): void
    {
        $this->actingAs($this->actor(false))->get(route('admin.supliers.index', ['export' => 'csv']))->assertForbidden();
        $this->actingAs($this->actor())->getJson(route('admin.supliers.index', ['export' => 'xlsx']))->assertUnprocessable();
    }
}

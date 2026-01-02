<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssignFinancePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissionTitles = [
            'vehicle_financial_entry_access',
            'vehicle_financial_entry_create',
            'vehicle_financial_entry_edit',
            'vehicle_financial_entry_show',
            'vehicle_financial_entry_delete',
            'client_ledger_entry_access',
            'client_ledger_entry_create',
            'client_ledger_entry_edit',
            'client_ledger_entry_show',
            'client_ledger_entry_delete',
        ];

        $permissionIds = [];
        foreach ($permissionTitles as $title) {
            $permissionIds[] = Permission::firstOrCreate(['title' => $title])->id;
        }

        $roles = Role::query()
            ->where('title', 'Gestao')
            ->orWhere('title', 'like', 'Aux. gest%')
            ->get();

        if ($roles->isEmpty()) {
            return;
        }

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}

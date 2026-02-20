<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_financial_entries')) {
            Schema::drop('vehicle_financial_entries');
        }

        if (Schema::hasTable('client_ledger_entries')) {
            Schema::drop('client_ledger_entries');
        }

        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->where(function ($query) {
                    $query->where('title', 'financial_sensitive_access')
                        ->orWhere('title', 'aquisition_of_the_vehicle')
                        ->orWhere('title', 'account_access')
                        ->orWhere('title', 'account_configuration_access')
                        ->orWhere('title', 'account_operation_access')
                        ->orWhere('title', 'account_operation_create')
                        ->orWhere('title', 'account_operation_edit')
                        ->orWhere('title', 'account_operation_show')
                        ->orWhere('title', 'account_operation_delete')
                        ->orWhere('title', 'account_department_access')
                        ->orWhere('title', 'account_department_create')
                        ->orWhere('title', 'account_department_edit')
                        ->orWhere('title', 'account_department_show')
                        ->orWhere('title', 'account_department_delete')
                        ->orWhere('title', 'account_category_access')
                        ->orWhere('title', 'account_category_create')
                        ->orWhere('title', 'account_category_edit')
                        ->orWhere('title', 'account_category_show')
                        ->orWhere('title', 'account_category_delete')
                        ->orWhere('title', 'account_item_access')
                        ->orWhere('title', 'account_item_create')
                        ->orWhere('title', 'account_item_edit')
                        ->orWhere('title', 'account_item_show')
                        ->orWhere('title', 'account_item_delete')
                        ->orWhere('title', 'client_ledger_entry_access')
                        ->orWhere('title', 'client_ledger_entry_create')
                        ->orWhere('title', 'client_ledger_entry_edit')
                        ->orWhere('title', 'client_ledger_entry_show')
                        ->orWhere('title', 'client_ledger_entry_delete')
                        ->orWhere('title', 'vehicle_financial_entry_access')
                        ->orWhere('title', 'vehicle_financial_entry_create')
                        ->orWhere('title', 'vehicle_financial_entry_edit')
                        ->orWhere('title', 'vehicle_financial_entry_show')
                        ->orWhere('title', 'vehicle_financial_entry_delete');
                })
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                if (Schema::hasTable('permission_role')) {
                    DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
                }

                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }
    }

    public function down(): void
    {
        // Irreversible cleanup by design.
    }
};

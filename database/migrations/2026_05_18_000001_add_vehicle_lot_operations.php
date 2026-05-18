<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_groups', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('vehicle_groups', 'type')) {
                $table->string('type', 20)->default('lote')->after('name');
            }
            if (! Schema::hasColumn('vehicle_groups', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->nullable()->after('wholesale_pvp');
            }
            if (! Schema::hasColumn('vehicle_groups', 'distribution_mode')) {
                $table->string('distribution_mode', 20)->default('proportional')->after('total_amount');
            }
            if (! Schema::hasColumn('vehicle_groups', 'status')) {
                $table->string('status', 30)->default('open')->after('distribution_mode');
            }
            if (! Schema::hasColumn('vehicle_groups', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('vehicle_groups', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('vehicle_groups', 'notes')) {
                $table->text('notes')->nullable()->after('approved_at');
            }
        });

        Schema::create('vehicle_lot_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_group_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->decimal('original_price', 15, 2)->nullable();
            $table->decimal('adjusted_price', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('invoiced_amount', 15, 2)->default(0);
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_group_id')->references('id')->on('vehicle_groups')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->unique(['vehicle_group_id', 'vehicle_id']);
            $table->index(['vehicle_id', 'status']);
        });

        Schema::create('lot_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_group_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->date('paid_at');
            $table->decimal('amount', 15, 2);
            $table->decimal('invoiced_amount', 15, 2)->default(0);
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->string('approval_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_group_id')->references('id')->on('vehicle_groups')->cascadeOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->restrictOnDelete();
            $table->index(['vehicle_group_id', 'approval_status']);
        });

        if (Schema::hasTable('permissions')) {
            $this->upsertPermissions();
        }

        $this->backfillExistingGroups();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $titles = [
                'vehicle_lot_access',
                'vehicle_lot_create',
                'vehicle_lot_edit',
                'vehicle_lot_show',
                'vehicle_lot_delete',
                'vehicle_lot_payment_create',
                'vehicle_lot_approve',
            ];
            $ids = DB::table('permissions')->whereIn('title', $titles)->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('permission_role')) {
                DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            }
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        Schema::dropIfExists('lot_payments');
        Schema::dropIfExists('vehicle_lot_items');

        Schema::table('vehicle_groups', function (Blueprint $table) {
            foreach (['customer_id', 'type', 'total_amount', 'distribution_mode', 'status', 'approved_by', 'approved_at', 'notes'] as $column) {
                if (Schema::hasColumn('vehicle_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function upsertPermissions(): void
    {
        $titles = [
            'vehicle_lot_access',
            'vehicle_lot_create',
            'vehicle_lot_edit',
            'vehicle_lot_show',
            'vehicle_lot_delete',
            'vehicle_lot_payment_create',
            'vehicle_lot_approve',
        ];

        $ids = [];
        foreach ($titles as $title) {
            $id = DB::table('permissions')->where('title', $title)->value('id');
            if (! $id) {
                $id = DB::table('permissions')->insertGetId([
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $ids[] = $id;
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('title', ['Admin', 'Adm', 'Gestao'])
            ->orWhere('title', 'like', 'Aux. gest%')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($ids as $permissionId) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    private function backfillExistingGroups(): void
    {
        $groups = DB::table('vehicle_groups')->get(['id', 'wholesale_pvp']);
        foreach ($groups as $group) {
            DB::table('vehicle_groups')
                ->where('id', $group->id)
                ->update([
                    'type' => 'lote',
                    'total_amount' => $group->wholesale_pvp,
                    'distribution_mode' => 'proportional',
                    'status' => 'open',
                ]);

            if (! Schema::hasTable('vehicle_group_vehicle')) {
                continue;
            }

            $vehicleIds = DB::table('vehicle_group_vehicle')
                ->where('vehicle_group_id', $group->id)
                ->pluck('vehicle_id');

            foreach ($vehicleIds as $vehicleId) {
                $exists = DB::table('vehicle_lot_items')
                    ->where('vehicle_group_id', $group->id)
                    ->where('vehicle_id', $vehicleId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $pvp = DB::table('vehicles')->where('id', $vehicleId)->value('pvp');
                DB::table('vehicle_lot_items')->insert([
                    'vehicle_group_id' => $group->id,
                    'vehicle_id' => $vehicleId,
                    'original_price' => $pvp,
                    'adjusted_price' => null,
                    'discount' => 0,
                    'allocated_amount' => 0,
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVehicleTradeInsTable extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_trade_ins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sold_vehicle_id');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('converted_by_id')->nullable();
            $table->unsignedBigInteger('created_vehicle_id')->nullable();
            $table->string('license');
            $table->string('normalized_license')->index();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('has_registration_title')->default(false);
            $table->boolean('has_purchase_sale_rgpd')->default(false);
            $table->boolean('has_seller_identification')->default(false);
            $table->boolean('has_ipo')->default(false);
            $table->boolean('has_two_keys')->default(false);
            $table->boolean('has_charging_cable_mode_2')->default(false);
            $table->boolean('has_charging_cable_mode_3')->default(false);
            $table->boolean('has_manuals')->default(false);
            $table->boolean('has_internal_invoice')->default(false);
            $table->boolean('has_finance_mod_2')->default(false);
            $table->boolean('has_promissory_note')->default(false);
            $table->boolean('has_reservation_extinction_authorization')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sold_vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('converted_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
        });

        foreach (['vehicle_trade_in_access', 'vehicle_trade_in_create', 'vehicle_trade_in_convert'] as $permission) {
            $permissionId = DB::table('permissions')->where('title', $permission)->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'title' => $permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $roleIds = DB::table('roles')
                ->whereIn('title', ['Admin', 'Gestão', 'Gestao', 'Stand'])
                ->pluck('id');

            if ($permission !== 'vehicle_trade_in_convert') {
                $vehicleEditId = DB::table('permissions')->where('title', 'vehicle_edit')->value('id');
                if ($vehicleEditId) {
                    $roleIds = $roleIds
                        ->merge(DB::table('permission_role')->where('permission_id', $vehicleEditId)->pluck('role_id'))
                        ->unique();
                }
            }

            foreach ($roleIds as $roleId) {
                if (! DB::table('permission_role')->where('permission_id', $permissionId)->where('role_id', $roleId)->exists()) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_trade_ins');
    }
}

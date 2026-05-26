<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePartOrderModule extends Migration
{
    public function up(): void
    {
        Schema::table('supliers', function (Blueprint $table) {
            if (! Schema::hasColumn('supliers', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
            if (! Schema::hasColumn('supliers', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (! Schema::hasColumn('supliers', 'mobile')) {
                $table->string('mobile')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('supliers', 'address')) {
                $table->text('address')->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('supliers', 'nif')) {
                $table->string('nif')->nullable()->after('address');
            }
            if (! Schema::hasColumn('supliers', 'average_delivery_days')) {
                $table->unsignedInteger('average_delivery_days')->nullable()->after('nif');
            }
            if (! Schema::hasColumn('supliers', 'active')) {
                $table->boolean('active')->default(true)->after('average_delivery_days');
            }
            if (! Schema::hasColumn('supliers', 'notes')) {
                $table->text('notes')->nullable()->after('active');
            }
        });

        Schema::create('part_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('repair_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->unsignedBigInteger('suplier_id')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('draft');
            $table->unsignedInteger('requested_delivery_days')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->timestamp('delay_alert_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('repair_id')->references('id')->on('repairs')->nullOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('requested_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('suplier_id')->references('id')->on('supliers')->nullOnDelete();
            $table->index(['status', 'expected_delivery_date']);
            $table->index(['vehicle_id', 'repair_id']);
        });

        Schema::create('part_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('part_order_id');
            $table->string('reference')->nullable();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price_estimated', 12, 2)->nullable();
            $table->decimal('unit_price_final', 12, 2)->nullable();
            $table->decimal('iva_percentage', 5, 2)->nullable();
            $table->decimal('total_estimated', 12, 2)->nullable();
            $table->decimal('total_final', 12, 2)->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_correct_part')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('part_order_id')->references('id')->on('part_orders')->cascadeOnDelete();
            $table->index(['part_order_id', 'status']);
        });

        Schema::create('part_quotes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('part_order_item_id');
            $table->unsignedBigInteger('suplier_id');
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->unsignedInteger('estimated_delivery_days')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('selected')->default(false);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('part_order_item_id')->references('id')->on('part_order_items')->cascadeOnDelete();
            $table->foreign('suplier_id')->references('id')->on('supliers')->cascadeOnDelete();
        });

        Schema::create('part_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('part_order_id');
            $table->unsignedBigInteger('suplier_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_condition')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('paid_by_id')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('part_order_id')->references('id')->on('part_orders')->cascadeOnDelete();
            $table->foreign('suplier_id')->references('id')->on('supliers')->nullOnDelete();
            $table->foreign('paid_by_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['payment_status', 'due_date']);
        });

        Schema::create('part_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('part_order_id');
            $table->timestamp('received_at')->nullable();
            $table->string('received_location')->nullable();
            $table->unsignedBigInteger('received_by_id')->nullable();
            $table->string('signature_name')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('part_order_id')->references('id')->on('part_orders')->cascadeOnDelete();
            $table->foreign('received_by_id')->references('id')->on('users')->nullOnDelete();
        });

        $permissions = [
            'part_order_access', 'part_order_create', 'part_order_edit', 'part_order_show', 'part_order_delete',
            'part_payment_access', 'part_payment_create', 'part_payment_edit', 'part_payment_show', 'part_payment_delete',
            'part_receipt_access', 'part_receipt_create', 'part_receipt_edit', 'part_receipt_show', 'part_receipt_delete',
        ];

        foreach ($permissions as $permission) {
            $permissionId = DB::table('permissions')->where('title', $permission)->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'title' => $permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $repairAccessId = DB::table('permissions')->where('title', 'repair_access')->value('id');
            $roleIds = DB::table('roles')
                ->whereIn('title', ['Admin', 'Adm'])
                ->pluck('id')
                ->merge($repairAccessId ? DB::table('permission_role')->where('permission_id', $repairAccessId)->pluck('role_id') : collect())
                ->unique();
            foreach ($roleIds as $roleId) {
                $exists = DB::table('permission_role')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $exists) {
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
        Schema::dropIfExists('part_receipts');
        Schema::dropIfExists('part_payments');
        Schema::dropIfExists('part_quotes');
        Schema::dropIfExists('part_order_items');
        Schema::dropIfExists('part_orders');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_cash_box_id')->constrained('cash_boxes')->restrictOnDelete();
            $table->foreignId('to_cash_box_id')->constrained('cash_boxes')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->dateTime('occurred_at');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->uuid('group_id')->unique();
            $table->timestamps();
        });

        Schema::table('account_operations', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('accounted_by')->constrained('users')->nullOnDelete();
            $table->foreignId('cash_transfer_id')->nullable()->after('transfer_group_id')->constrained('cash_transfers')->nullOnDelete();
        });

        Schema::table('cash_categories', function (Blueprint $table) {
            $table->dropUnique('cash_categories_name_unique');
            $table->foreignId('cash_box_id')->nullable()->after('id')->constrained('cash_boxes')->cascadeOnDelete();
            $table->unique(['cash_box_id', 'name']);
        });

        $workshopBoxId = DB::table('cash_boxes')->where('slug', 'caixa_oficina')->value('id');
        if ($workshopBoxId) {
            foreach (['Peças', 'Oficina Externa', 'Horas Extra Mecânicos', 'Consumíveis', 'Diversos'] as $name) {
                DB::table('cash_categories')->updateOrInsert(
                    ['cash_box_id' => $workshopBoxId, 'name' => $name],
                    ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $permissionRoles = [
            'workshop_cash_access' => ['Admin', 'Adm', 'Gestão', 'Gestao', 'Chefe oficina', 'Aux. oficina', 'Aux. Oficina'],
            'workshop_cash_transfer' => ['Admin', 'Adm', 'Gestão', 'Gestao'],
            'workshop_cash_expense' => ['Admin', 'Adm', 'Chefe oficina', 'Aux. oficina', 'Aux. Oficina'],
            'workshop_cash_category_manage' => ['Admin', 'Adm', 'Chefe oficina', 'Aux. oficina', 'Aux. Oficina'],
        ];

        foreach ($permissionRoles as $title => $roleTitles) {
            $permissionId = DB::table('permissions')->where('title', $title)->value('id')
                ?: DB::table('permissions')->insertGetId(['title' => $title, 'created_at' => now(), 'updated_at' => now()]);
            $roleIds = DB::table('roles')->whereIn('title', $roleTitles)->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('title', [
            'workshop_cash_access',
            'workshop_cash_transfer',
            'workshop_cash_expense',
            'workshop_cash_category_manage',
        ])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('cash_categories', function (Blueprint $table) {
            $table->dropUnique(['cash_box_id', 'name']);
            $table->dropConstrainedForeignId('cash_box_id');
            $table->unique('name');
        });
        Schema::table('account_operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_transfer_id');
            $table->dropConstrainedForeignId('created_by_id');
        });
        Schema::dropIfExists('cash_transfers');
    }
};

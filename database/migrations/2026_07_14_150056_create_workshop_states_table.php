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
        Schema::create('workshop_states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('workshop_state_id')
                ->nullable()
                ->after('general_state_id')
                ->constrained('workshop_states')
                ->nullOnDelete();
        });

        $states = [
            'Viaturas para reparar',
            'Vendida',
            'Entregue',
            'Reclamação do stand',
            'Revisão agendada',
            'Check-up',
            'Garantia',
            'A aguardar peças',
            'A aguardar programação',
        ];

        foreach ($states as $position => $name) {
            DB::table('workshop_states')->insert([
                'name' => $name,
                'position' => $position + 1,
                'is_active' => true,
                'is_default' => $position === 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $defaultStateId = DB::table('workshop_states')->where('is_default', true)->value('id');
        $workshopGeneralStateIds = DB::table('general_states')
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->pluck('id');

        if ($defaultStateId && $workshopGeneralStateIds->isNotEmpty()) {
            DB::table('vehicles')
                ->whereIn('general_state_id', $workshopGeneralStateIds)
                ->update(['workshop_state_id' => $defaultStateId]);
        }

        $permissionIds = collect([
            'workshop_state_access',
            'workshop_state_create',
            'workshop_state_edit',
            'workshop_state_delete',
        ])->map(function (string $title): int {
            $existingPermissionId = DB::table('permissions')->where('title', $title)->value('id');
            if ($existingPermissionId) {
                return (int) $existingPermissionId;
            }

            return DB::table('permissions')->insertGetId([
                'title' => $title,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $roleIds = DB::table('roles')->whereIn('title', ['Admin', 'Adm', 'Chefe oficina'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('title', [
                'workshop_state_access',
                'workshop_state_create',
                'workshop_state_edit',
                'workshop_state_delete',
            ])
            ->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workshop_state_id');
        });

        Schema::dropIfExists('workshop_states');
    }
};

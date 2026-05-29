<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cash_categories')) {
            Schema::create('cash_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cash_boxes')) {
            Schema::create('cash_boxes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $this->seedReferenceData();

        if (Schema::hasTable('account_operations')) {
            Schema::table('account_operations', function (Blueprint $table) {
                if (! Schema::hasColumn('account_operations', 'description')) {
                    $table->string('description')->nullable()->after('id');
                }

                if (! Schema::hasColumn('account_operations', 'movement_type')) {
                    $table->string('movement_type', 20)->nullable()->after('description');
                }

                if (! Schema::hasColumn('account_operations', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable()->after('account_item_id');
                }

                if (! Schema::hasColumn('account_operations', 'cash_category_id')) {
                    $table->unsignedBigInteger('cash_category_id')->nullable()->after('department_id');
                }

                if (! Schema::hasColumn('account_operations', 'cash_box_id')) {
                    $table->unsignedBigInteger('cash_box_id')->nullable()->after('payment_method_id');
                }

                if (! Schema::hasColumn('account_operations', 'notes')) {
                    $table->text('notes')->nullable()->after('date');
                }

                if (! Schema::hasColumn('account_operations', 'is_accounted')) {
                    $table->boolean('is_accounted')->default(false)->after('notes');
                }

                if (! Schema::hasColumn('account_operations', 'accounted_at')) {
                    $table->timestamp('accounted_at')->nullable()->after('is_accounted');
                }

                if (! Schema::hasColumn('account_operations', 'accounted_by')) {
                    $table->unsignedBigInteger('accounted_by')->nullable()->after('accounted_at');
                }

                if (! Schema::hasColumn('account_operations', 'transfer_group_id')) {
                    $table->uuid('transfer_group_id')->nullable()->after('accounted_by');
                }
            });

            Schema::table('account_operations', function (Blueprint $table) {
                $this->addForeignIfMissing($table, 'account_operations_department_id_foreign', 'department_id', 'departments');
                $this->addForeignIfMissing($table, 'account_operations_cash_category_id_foreign', 'cash_category_id', 'cash_categories');
                $this->addForeignIfMissing($table, 'account_operations_cash_box_id_foreign', 'cash_box_id', 'cash_boxes');
                $this->addForeignIfMissing($table, 'account_operations_accounted_by_foreign', 'accounted_by', 'users');
            });

            $this->backfillOperations();
        }

        $this->upsertPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('account_operations')) {
            Schema::table('account_operations', function (Blueprint $table) {
                foreach ([
                    'account_operations_department_id_foreign',
                    'account_operations_cash_category_id_foreign',
                    'account_operations_cash_box_id_foreign',
                    'account_operations_accounted_by_foreign',
                ] as $foreign) {
                    try {
                        $table->dropForeign($foreign);
                    } catch (Throwable $exception) {
                        // Foreign key may not exist on older installs.
                    }
                }

                foreach ([
                    'description',
                    'movement_type',
                    'department_id',
                    'cash_category_id',
                    'cash_box_id',
                    'notes',
                    'is_accounted',
                    'accounted_at',
                    'accounted_by',
                    'transfer_group_id',
                ] as $column) {
                    if (Schema::hasColumn('account_operations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('cash_boxes');
        Schema::dropIfExists('cash_categories');
        Schema::dropIfExists('departments');
    }

    private function seedReferenceData(): void
    {
        $departmentNames = [
            'Administração',
            'Stand',
            'Oficina',
            'Imobiliário',
            'Sucata',
            'Outros',
        ];

        if (Schema::hasTable('account_departments')) {
            $departmentNames = array_merge(
                $departmentNames,
                DB::table('account_departments')->whereNull('deleted_at')->pluck('name')->all()
            );
        }

        foreach (array_unique(array_filter($departmentNames)) as $name) {
            DB::table('departments')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $categoryNames = [
            'Venda',
            'Sinal',
            'Reparação',
            'Combustível',
            'Ferramentas',
            'Material',
            'Imobiliário',
            'Serviços',
            'Outros',
        ];

        if (Schema::hasTable('account_categories')) {
            $categoryNames = array_merge(
                $categoryNames,
                DB::table('account_categories')->whereNull('deleted_at')->pluck('name')->all()
            );
        }

        foreach (array_unique(array_filter($categoryNames)) as $name) {
            DB::table('cash_categories')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        foreach (['Caixa 1', 'Caixa 2', 'Banco'] as $name) {
            DB::table('cash_boxes')->updateOrInsert(
                ['slug' => Str::slug($name, '_')],
                ['name' => $name, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function backfillOperations(): void
    {
        DB::statement("
            UPDATE account_operations ao
            LEFT JOIN account_items ai ON ai.id = ao.account_item_id
            LEFT JOIN account_categories ac ON ac.id = ai.account_category_id
            LEFT JOIN account_departments ad ON ad.id = ac.account_department_id
            LEFT JOIN departments d ON d.name = ad.name
            LEFT JOIN cash_categories cc ON cc.name = ac.name
            SET
                ao.description = COALESCE(ao.description, ai.name),
                ao.movement_type = COALESCE(ao.movement_type, ai.type),
                ao.department_id = COALESCE(ao.department_id, d.id),
                ao.cash_category_id = COALESCE(ao.cash_category_id, cc.id)
        ");
    }

    private function upsertPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('title', 'cash_access')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'title' => 'cash_access',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('title', ['Admin', 'Gestão', 'Gestao', 'Adm'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    private function addForeignIfMissing(Blueprint $table, string $name, string $column, string $referencedTable): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'account_operations')
            ->where('CONSTRAINT_NAME', $name)
            ->exists();

        if (! $exists) {
            $table->foreign($column, $name)->references('id')->on($referencedTable)->nullOnDelete();
        }
    }
};

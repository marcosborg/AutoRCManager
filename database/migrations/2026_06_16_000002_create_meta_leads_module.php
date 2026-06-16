<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('leadgen_id')->unique();
            $table->string('page_id')->index();
            $table->string('form_id')->index();
            $table->string('ad_id')->nullable();
            $table->string('adgroup_id')->nullable();
            $table->string('full_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('vehicle_interest')->nullable();
            $table->string('budget')->nullable();
            $table->string('financing')->nullable();
            $table->string('trade_in')->nullable();
            $table->json('raw_data')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lead_sales_rotation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_user_id')->nullable();
            $table->timestamps();

            $table->foreign('last_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lead_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('assigned_by_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        foreach (['lead_access', 'lead_show', 'lead_edit', 'lead_delete'] as $permissionTitle) {
            $permissionId = DB::table('permissions')->where('title', $permissionTitle)->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'title' => $permissionTitle,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $roleTitles = $permissionTitle === 'lead_delete' ? ['Admin', 'Adm'] : ['Admin', 'Adm', 'Stand'];
            $roleIds = DB::table('roles')
                ->whereIn('title', $roleTitles)
                ->pluck('id');

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
        $permissionIds = DB::table('permissions')
            ->whereIn('title', ['lead_access', 'lead_show', 'lead_edit', 'lead_delete'])
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('lead_assignment_histories');
        Schema::dropIfExists('lead_sales_rotation');
        Schema::dropIfExists('leads');
    }
};

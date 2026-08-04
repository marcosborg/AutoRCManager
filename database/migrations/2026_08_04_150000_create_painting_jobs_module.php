<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('painting_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('legacy_repair_id')->nullable()->unique()->constrained('repairs')->nullOnDelete();
            $table->foreignId('painter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open')->index();
            $table->string('client_contact')->nullable();
            $table->string('brand_model')->nullable();
            $table->string('license')->nullable()->index();
            $table->date('entry_date')->index();
            $table->date('exit_date')->nullable()->index();
            $table->text('optics')->nullable();
            $table->text('black_parts')->nullable();
            $table->text('wheels')->nullable();
            $table->text('other_work')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('painting_job_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('painting_job_id')->constrained('painting_jobs')->cascadeOnDelete();
            $table->string('zone');
            $table->string('intensity')->nullable();
            $table->timestamps();
            $table->unique(['painting_job_id', 'zone']);
        });

        Schema::create('painting_job_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('painting_job_id')->constrained('painting_jobs')->cascadeOnDelete();
            $table->string('material_type');
            $table->string('reference')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->date('used_date')->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $permissions = [
            'painting_job_access', 'painting_job_show', 'painting_job_create',
            'painting_job_edit', 'painting_job_complete', 'painting_job_reopen',
        ];
        $permissionIds = collect($permissions)->mapWithKeys(function (string $title): array {
            $id = DB::table('permissions')->where('title', $title)->value('id')
                ?: DB::table('permissions')->insertGetId(['title' => $title, 'created_at' => now(), 'updated_at' => now()]);

            return [$title => $id];
        });

        $painterRoleId = DB::table('roles')->where('title', 'Pintor')->value('id')
            ?: DB::table('roles')->insertGetId(['title' => 'Pintor', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['painting_job_access', 'painting_job_show', 'painting_job_edit', 'painting_job_complete'] as $permission) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $painterRoleId, 'permission_id' => $permissionIds[$permission]]);
        }

        $managerRoleIds = DB::table('roles')->whereIn('title', ['Admin', 'Adm', 'Chefe oficina'])->pluck('id');
        foreach ($managerRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        $this->migrateLegacyPaintingRepairs();
        $this->archiveLegacyPaintingInterventionType();
        $this->seedLegacyDetails();
    }

    private function migrateLegacyPaintingRepairs(): void
    {
        DB::table('repairs')
            ->whereIn('work_type', ['paint', 'painting'])
            ->orderBy('id')
            ->get()
            ->each(function ($repair): void {
                $vehicle = DB::table('vehicles')->where('id', $repair->vehicle_id)->first();
                if (! $vehicle) {
                    return;
                }
                $brand = $vehicle->brand_id ? DB::table('brands')->where('id', $vehicle->brand_id)->value('name') : null;
                $client = $vehicle->client_id ? DB::table('clients')->where('id', $vehicle->client_id)->first() : null;
                $clientContact = $client ? trim(implode(' / ', array_filter([$client->name ?? null, $client->phone ?? null, $client->email ?? null]))) : null;
                $brandModel = trim(implode(' ', array_filter([$brand, $vehicle->model ?? null])));
                $completedAt = $repair->repair_finished_at ?? null;

                DB::table('painting_jobs')->updateOrInsert(
                    ['legacy_repair_id' => $repair->id],
                    [
                        'vehicle_id' => $vehicle->id,
                        'status' => $completedAt ? 'completed' : 'open',
                        'client_contact' => $clientContact ?: null,
                        'brand_model' => $brandModel ?: null,
                        'license' => $vehicle->license ?: ($vehicle->foreign_license ?? null),
                        'entry_date' => substr((string) ($repair->timestamp ?: $repair->created_at ?: now()), 0, 10),
                        'exit_date' => $completedAt ? substr((string) $completedAt, 0, 10) : null,
                        'notes' => trim(implode("\n", array_filter([$repair->obs_1 ?? null, $repair->obs_2 ?? null, $repair->work_performed ?? null]))),
                        'completed_at' => $completedAt,
                        'created_at' => $repair->created_at ?: now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    private function archiveLegacyPaintingInterventionType(): void
    {
        if (! Schema::hasTable('workshop_intervention_types')) {
            return;
        }
        $typeIds = DB::table('workshop_intervention_types')->whereRaw('LOWER(name) = ?', ['pintura'])->pluck('id');
        if ($typeIds->isEmpty()) {
            return;
        }
        DB::table('workshop_interventions')->whereIn('type_id', $typeIds)->orderBy('id')->get()->each(function ($item): void {
            $job = DB::table('painting_jobs')->where('legacy_repair_id', $item->repair_id)->first();
            if (! $job) {
                $repair = DB::table('repairs')->where('id', $item->repair_id)->first();
                if (! $repair) {
                    return;
                }
                $this->migrateLegacyPaintingRepairsForWorkshopRepair($repair, $item);
                $job = DB::table('painting_jobs')->where('legacy_repair_id', $item->repair_id)->first();
            }
            if ($job) {
                $legacyNote = trim('Planeamento legado: '.implode(' — ', array_filter([$item->title, $item->description])));
                DB::table('painting_jobs')->where('id', $job->id)->update([
                    'notes' => trim(implode("\n", array_filter([$job->notes, $legacyNote]))),
                    'updated_at' => now(),
                ]);
            }
        });
        // Preserve the historical planning records. Inactivating the type removes it
        // from new planning without deleting tasks, work logs or their audit trail.
        DB::table('workshop_intervention_types')->whereIn('id', $typeIds)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    private function migrateLegacyPaintingRepairsForWorkshopRepair(object $repair, object $item): void
    {
        $vehicle = DB::table('vehicles')->where('id', $repair->vehicle_id)->first();
        if (! $vehicle) {
            return;
        }
        $brand = $vehicle->brand_id ? DB::table('brands')->where('id', $vehicle->brand_id)->value('name') : null;
        $client = $vehicle->client_id ? DB::table('clients')->where('id', $vehicle->client_id)->first() : null;
        DB::table('painting_jobs')->insert([
            'vehicle_id' => $vehicle->id,
            'legacy_repair_id' => $repair->id,
            'status' => $item->status === 'completed' ? 'completed' : 'open',
            'client_contact' => $client ? trim(implode(' / ', array_filter([$client->name ?? null, $client->phone ?? null, $client->email ?? null]))) : null,
            'brand_model' => trim(implode(' ', array_filter([$brand, $vehicle->model ?? null]))),
            'license' => $vehicle->license ?: ($vehicle->foreign_license ?? null),
            'entry_date' => $item->planned_start_date ?: now()->toDateString(),
            'exit_date' => $item->status === 'completed' ? ($item->planned_end_date ?: now()->toDateString()) : null,
            'completed_at' => $item->completed_at,
            'created_at' => $item->created_at ?: now(),
            'updated_at' => now(),
        ]);
    }

    private function seedLegacyDetails(): void
    {
        $zones = ['hood', 'front_left_fender', 'front_right_fender', 'front_bumper', 'front_left_door', 'front_right_door', 'rear_left_door', 'rear_right_door', 'rear_left_panel', 'rear_right_panel', 'trunk_lid', 'rear_bumper', 'roof', 'right_sill', 'left_sill'];
        $materials = ['Tinta', 'Massa', 'Aparelho', 'Lixa', 'Lixa de tiras', 'Esfregão', 'Fita', 'Papel', 'Verniz'];
        DB::table('painting_jobs')->pluck('id')->each(function (int $jobId) use ($zones, $materials): void {
            foreach ($zones as $zone) {
                DB::table('painting_job_damages')->insertOrIgnore(['painting_job_id' => $jobId, 'zone' => $zone, 'created_at' => now(), 'updated_at' => now()]);
            }
            if (! DB::table('painting_job_materials')->where('painting_job_id', $jobId)->exists()) {
                foreach ($materials as $position => $material) {
                    DB::table('painting_job_materials')->insert(['painting_job_id' => $jobId, 'material_type' => $material, 'position' => $position, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('painting_job_materials');
        Schema::dropIfExists('painting_job_damages');
        Schema::dropIfExists('painting_jobs');
    }
};

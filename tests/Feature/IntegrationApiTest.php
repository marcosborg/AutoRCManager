<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\PaintingJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('general_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('position')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('painting_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('legacy_repair_id')->nullable();
            $table->unsignedBigInteger('painter_id')->nullable();
            $table->string('status');
            $table->string('license')->nullable();
            $table->date('entry_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_catalog_requires_authentication_and_permission(): void
    {
        $this->getJson('/api/v1/integration/catalog/general-states')->assertUnauthorized();

        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/v1/integration/catalog/general-states')->assertForbidden();
    }

    public function test_catalog_is_paginated_and_whitelisted(): void
    {
        $user = $this->makeUser('general_state_access');
        $user->roles->first()->permissions()->attach(Permission::firstOrCreate(['title' => 'general_state_show']));
        $state = GeneralState::create(['name' => 'Integration test '.uniqid(), 'position' => 999]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/integration/catalog/general-states?per_page=1')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total'])
            ->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/integration/catalog/general-states/'.$state->id)
            ->assertOk()
            ->assertJsonPath('data.name', $state->name)
            ->assertJsonMissingPath('data.emails');
        $this->getJson('/api/v1/integration/catalog/invalid')->assertNotFound();
    }

    public function test_trade_in_reader_cannot_request_pending_records(): void
    {
        Sanctum::actingAs($this->makeUser('vehicle_trade_in_access'));

        $this->getJson('/api/v1/integration/catalog/trade-ins?status=pending')->assertForbidden();
    }

    public function test_painter_catalog_only_exposes_assigned_painting_jobs(): void
    {
        $painter = $this->makeUser('painting_job_access');
        $painter->roles->first()->permissions()->attach(Permission::firstOrCreate(['title' => 'painting_job_show']));
        $otherPainter = $this->makeUser();
        $assigned = PaintingJob::create(['painter_id' => $painter->id, 'status' => PaintingJob::STATUS_OPEN]);
        $unassigned = PaintingJob::create(['painter_id' => $otherPainter->id, 'status' => PaintingJob::STATUS_OPEN]);
        Sanctum::actingAs($painter);

        $this->getJson('/api/v1/integration/catalog/painting-jobs')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $assigned->id);
        $this->getJson('/api/v1/integration/catalog/painting-jobs/'.$assigned->id)->assertOk();
        $this->getJson('/api/v1/integration/catalog/painting-jobs/'.$unassigned->id)->assertNotFound();

        $manager = $this->makeUser('painting_job_access');
        $manager->roles->first()->permissions()->attach([
            Permission::firstOrCreate(['title' => 'painting_job_show'])->id,
            Permission::firstOrCreate(['title' => 'painting_job_create'])->id,
        ]);
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/integration/catalog/painting-jobs')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/v1/integration/catalog/painting-jobs/'.$unassigned->id)->assertOk();
    }

    public function test_consignment_write_requires_the_matching_permission(): void
    {
        Sanctum::actingAs($this->makeUser('vehicle_consignment_access'));

        $this->postJson('/api/v1/integration/consignments', [])->assertForbidden();
    }

    public function test_integration_token_requires_password_and_can_be_revoked(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/integration/tokens', [
            'name' => 'External app', 'password' => 'wrong',
        ])->assertUnprocessable();

        $response = $this->postJson('/api/v1/integration/tokens', [
            'name' => 'External app', 'password' => 'test-password', 'expires_in_days' => 30,
        ])->assertCreated()->assertJsonStructure(['id', 'token', 'expires_at']);

        $id = $response->json('id');
        $this->getJson('/api/v1/integration/tokens')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->deleteJson('/api/v1/integration/tokens/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $id]);
    }

    public function test_user_cannot_revoke_another_users_integration_token(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $token = $owner->createToken('integration:owner')->accessToken;
        Sanctum::actingAs($other);

        $this->deleteJson('/api/v1/integration/tokens/'.$token->id)->assertNotFound();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_backoffice_mirror_rejects_non_admin_integration_token(): void
    {
        $ordinary = $this->makeUser();
        $ordinaryToken = $ordinary->createToken('integration:ordinary')->plainTextToken;
        $this->deleteJson('/api/v1/backoffice/role-preview', [], [
            'Authorization' => 'Bearer '.$ordinaryToken,
        ])->assertForbidden();
    }

    public function test_backoffice_mirror_accepts_real_admin_integration_token(): void
    {
        $admin = $this->makeUser();
        $adminRole = Role::firstOrCreate(['title' => 'Admin']);
        $admin->roles()->attach($adminRole);
        $adminToken = $admin->createToken('integration:external')->plainTextToken;
        $this->deleteJson('/api/v1/backoffice/role-preview', [], [
            'Authorization' => 'Bearer '.$adminToken,
        ])->assertOk()->assertJsonPath('message', 'Role temporario removido.');
    }

    public function test_profile_password_requires_the_current_password(): void
    {
        $user = $this->makeUser('profile_password_edit');
        $token = $user->createToken('integration:profile')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'wrong',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ], $headers)->assertUnprocessable();

        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'test-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ], $headers)->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    private function makeUser(?string $permission = null): User
    {
        $user = User::create([
            'name' => 'Integration API Test',
            'email' => uniqid('integration-', true).'@example.test',
            'password' => 'test-password',
        ]);

        if ($permission) {
            $role = Role::create(['title' => 'Integration test '.uniqid()]);
            $permissionModel = Permission::firstOrCreate(['title' => $permission]);
            $role->permissions()->attach($permissionModel);
            $user->roles()->attach($role);
        }

        return $user;
    }
}

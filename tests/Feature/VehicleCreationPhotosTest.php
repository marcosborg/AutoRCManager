<?php

namespace Tests\Feature;

use App\Models\GeneralState;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class VehicleCreationPhotosTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.vehicle-creation-photos' => ['driver' => 'local']]);
        Storage::fake('vehicle-creation-photos');
        config(['media-library.disk_name' => 'vehicle-creation-photos']);
    }

    public function test_creation_stores_each_group_and_preserves_cover_order(): void
    {
        $payload = $this->payload();
        $response = $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload + [
            'initial_photo_files' => [UploadedFile::fake()->image('compra.jpg')],
            'vehicle_photo_files' => [UploadedFile::fake()->image('capa.png'), UploadedFile::fake()->image('lateral.jpg')],
        ]);

        $vehicle = Vehicle::where('license', $payload['license'])->firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.vehicles.edit', $vehicle));
        $this->assertSame(['compra.jpg'], $vehicle->getMedia('inicial')->pluck('file_name')->all());
        $this->assertSame(['capa.png', 'lateral.jpg'], $vehicle->getMedia('photos')->pluck('file_name')->all());
        foreach ($vehicle->media as $media) {
            Storage::disk('vehicle-creation-photos')->assertExists($media->getPathRelativeToRoot());
        }
    }

    public function test_photographs_are_optional(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload)->assertSessionHasNoErrors();
        $vehicle = Vehicle::where('license', $payload['license'])->firstOrFail();
        $this->assertCount(0, $vehicle->media);
    }

    public function test_upload_requires_vehicle_creation_permission(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->user(false))->post(route('admin.vehicles.store'), $payload + [
            'initial_photo_files' => [UploadedFile::fake()->image('compra.jpg')],
        ])->assertForbidden();
        $this->assertDatabaseMissing('vehicles', ['license' => $payload['license']]);
        $this->assertSame([], Storage::disk('vehicle-creation-photos')->allFiles());
    }

    public function test_invalid_images_and_oversized_files_are_rejected_before_creation(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload + [
            'initial_photo_files' => [UploadedFile::fake()->create('script.jpg', 1, 'text/plain')],
            'vehicle_photo_files' => [UploadedFile::fake()->image('large.jpg')->size(2049)],
        ])->assertSessionHasErrors(['initial_photo_files.0', 'vehicle_photo_files.0']);
        $this->assertDatabaseMissing('vehicles', ['license' => $payload['license']]);
        $this->assertSame([], Storage::disk('vehicle-creation-photos')->allFiles());
    }

    public function test_combined_upload_size_is_limited_across_both_groups(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload + [
            'initial_photo_files' => [UploadedFile::fake()->image('a.jpg')->size(2048), UploadedFile::fake()->image('b.jpg')->size(2048)],
            'vehicle_photo_files' => [UploadedFile::fake()->image('c.jpg')->size(2048), UploadedFile::fake()->image('d.jpg')->size(1)],
        ])->assertSessionHasErrors('initial_photo_files');
        $this->assertDatabaseMissing('vehicles', ['license' => $payload['license']]);
    }

    public function test_too_many_photos_are_rejected(): void
    {
        $payload = $this->payload();
        $files = array_map(fn ($i) => UploadedFile::fake()->image("photo-{$i}.jpg"), range(1, 11));
        $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload + [
            'initial_photo_files' => $files,
        ])->assertSessionHasErrors('initial_photo_files');
        $this->assertDatabaseMissing('vehicles', ['license' => $payload['license']]);
    }

    public function test_invalid_vehicle_data_does_not_store_any_photos(): void
    {
        $this->actingAs($this->user())->post(route('admin.vehicles.store'), [
            'initial_photo_files' => [UploadedFile::fake()->image('compra.jpg')],
        ])->assertSessionHasErrors('general_state_id');
        $this->assertSame([], Storage::disk('vehicle-creation-photos')->allFiles());
    }

    public function test_partial_failure_preserves_vehicle_and_other_photos_and_reports_only_failed_file(): void
    {
        $payload = $this->payload();
        $mediaCount = Media::count();
        $shouldFail = true;
        Event::listen(MediaHasBeenAdded::class, function ($event) use (&$shouldFail) {
            if ($shouldFail && $event->media->file_name === 'failed.jpg') {
                throw new \RuntimeException('Simulated photo storage failure');
            }
        });

        $response = $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload + [
            'initial_photo_files' => [UploadedFile::fake()->image('first.jpg'), UploadedFile::fake()->image('failed.jpg')],
            'vehicle_photo_files' => [UploadedFile::fake()->image('cover.jpg'), UploadedFile::fake()->image('last.jpg')],
        ]);
        $vehicle = Vehicle::where('license', $payload['license'])->firstOrFail();
        $response->assertRedirect(route('admin.vehicles.edit', $vehicle))
            ->assertSessionHas('failed_vehicle_photos', [['name' => 'failed.jpg', 'group' => 'Fotografias da aquisição']]);
        $this->assertSame(1, Vehicle::where('license', $payload['license'])->count());
        $this->assertSame(['first.jpg'], $vehicle->getMedia('inicial')->pluck('file_name')->all());
        $this->assertSame(['cover.jpg', 'last.jpg'], $vehicle->getMedia('photos')->pluck('file_name')->all());
        $this->assertSame($mediaCount + 3, Media::count());
        $this->assertFalse(collect(Storage::disk('vehicle-creation-photos')->allFiles())->contains(fn ($path) => str_contains($path, 'failed')));
        foreach ($vehicle->media as $media) {
            Storage::disk('vehicle-creation-photos')->assertExists($media->getPathRelativeToRoot());
        }
        $shouldFail = false;
        $failed = app(\App\Services\VehicleCreationPhotoService::class)->store($vehicle, [
            'inicial' => [UploadedFile::fake()->image('failed.jpg')],
        ]);
        $this->assertSame([], $failed);
        $this->assertSame(1, Vehicle::where('license', $payload['license'])->count());
        $this->assertSame($mediaCount + 4, Media::count());
        $this->assertSame(['first.jpg', 'failed.jpg'], $vehicle->fresh()->getMedia('inicial')->pluck('file_name')->all());
    }

    public function test_all_photo_failures_still_preserve_the_created_vehicle(): void
    {
        $payload = $this->payload();
        Event::listen(MediaHasBeenAdded::class, function () {
            throw new \RuntimeException('Simulated photo storage failure');
        });
        $response = $this->actingAs($this->user())->post(route('admin.vehicles.store'), $payload + [
            'vehicle_photo_files' => [UploadedFile::fake()->image('failed.jpg')],
        ]);
        $vehicle = Vehicle::where('license', $payload['license'])->firstOrFail();
        $response->assertRedirect(route('admin.vehicles.edit', $vehicle))
            ->assertSessionHas('failed_vehicle_photos', [['name' => 'failed.jpg', 'group' => 'Fotografias atuais']]);
        $this->assertCount(0, $vehicle->media);
        $this->assertSame([], Storage::disk('vehicle-creation-photos')->allFiles());
    }

    private function payload(): array
    {
        return [
            'license' => uniqid('PHOTO-'),
            'general_state_id' => GeneralState::firstOrCreate(['name' => 'PHOTO CREATION TEST'])->id,
        ];
    }

    private function user(bool $canCreate = true): User
    {
        $user = User::create(['name' => 'Photo creation test', 'email' => uniqid('photo-').'@example.test', 'password' => 'password']);
        $role = Role::create(['title' => uniqid('Photo creation role ')]);
        if ($canCreate) {
            $role->permissions()->attach(Permission::firstOrCreate(['title' => 'vehicle_create']));
        }
        $user->roles()->attach($role);

        return $user;
    }
}

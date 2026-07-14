<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleLicensePlateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vehicle_formats_and_finds_a_national_plate_with_or_without_hyphens(): void
    {
        $vehicle = Vehicle::create([
            'license' => 'ZZ99ZZ',
            'model' => 'Teste matrícula',
        ]);

        $this->assertSame('ZZ-99-ZZ', $vehicle->license);
        $this->assertTrue(Vehicle::searchByLicense('ZZ99ZZ')->whereKey($vehicle)->exists());
        $this->assertTrue(Vehicle::searchByLicense('ZZ-99-ZZ')->whereKey($vehicle)->exists());
    }

    public function test_application_locale_stays_portuguese_when_an_old_language_query_is_used(): void
    {
        $this->get('/?change_language=en');

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_sales_displays_and_filters_the_international_plate_in_its_own_column(): void
    {
        $user = Role::where('title', 'Admin')->firstOrFail()->users()->firstOrFail();
        $vehicle = Vehicle::create([
            'license' => 'AB12CD',
            'foreign_license' => 'INT 9087',
            'model' => 'Teste matrícula internacional',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.sales.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => [
                    [
                        'data' => 'foreign_license',
                        'name' => 'foreign_license',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => [
                            'value' => 'INT9087',
                            'regex' => 'false',
                        ],
                    ],
                ],
                'search' => [
                    'value' => '',
                    'regex' => 'false',
                ],
            ]));

        $response->assertOk();
        $this->assertSame([$vehicle->id], collect($response->json('data'))->pluck('id')->all());
        $response->assertJsonPath('data.0.license', 'AB-12-CD');
        $response->assertJsonPath('data.0.foreign_license', 'INT 9087');
    }
}

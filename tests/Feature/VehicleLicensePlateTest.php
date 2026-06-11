<?php

namespace Tests\Feature;

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
}

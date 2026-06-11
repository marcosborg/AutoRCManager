<?php

namespace Tests\Unit;

use App\Support\LicensePlate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LicensePlateTest extends TestCase
{
    #[DataProvider('nationalPlateProvider')]
    public function test_it_formats_national_license_plates(string $input, string $expected): void
    {
        $this->assertSame($expected, LicensePlate::formatNational($input));
    }

    public static function nationalPlateProvider(): array
    {
        return [
            'old numeric format' => ['1234ab', '12-34-AB'],
            'numeric letter numeric format' => ['12ab34', '12-AB-34'],
            'letter numeric format' => ['ab1234', 'AB-12-34'],
            'current format' => ['ab12cd', 'AB-12-CD'],
            'already formatted' => ['AB-12-CD', 'AB-12-CD'],
        ];
    }

    public function test_it_normalizes_plates_for_searches_with_or_without_hyphens(): void
    {
        $this->assertSame(
            LicensePlate::normalize('AB-12-CD'),
            LicensePlate::normalize('ab12cd')
        );
    }

    public function test_it_does_not_force_an_invalid_length_into_a_national_format(): void
    {
        $this->assertSame('GM559KX', LicensePlate::formatNational('gm559kx'));
    }
}

<?php

namespace Database\Seeders;

use App\Models\OperationalUnit;
use Illuminate\Database\Seeder;

class OperationalUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Salvados', 'code' => 'SAL', 'is_internal' => true],
            ['name' => 'Oficina', 'code' => 'OFI', 'is_internal' => true],
            ['name' => 'Stand', 'code' => 'STD', 'is_internal' => true],
            ['name' => 'Rent-a-Car', 'code' => 'RAC', 'is_internal' => true],
        ];

        foreach ($units as $unit) {
            OperationalUnit::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }
}

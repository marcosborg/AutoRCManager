<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'id'    => 1,
                'title' => 'user_management_access',
            ],
            [
                'id'    => 2,
                'title' => 'permission_create',
            ],
            [
                'id'    => 3,
                'title' => 'permission_edit',
            ],
            [
                'id'    => 4,
                'title' => 'permission_show',
            ],
            [
                'id'    => 5,
                'title' => 'permission_delete',
            ],
            [
                'id'    => 6,
                'title' => 'permission_access',
            ],
            [
                'id'    => 7,
                'title' => 'role_create',
            ],
            [
                'id'    => 8,
                'title' => 'role_edit',
            ],
            [
                'id'    => 9,
                'title' => 'role_show',
            ],
            [
                'id'    => 10,
                'title' => 'role_delete',
            ],
            [
                'id'    => 11,
                'title' => 'role_access',
            ],
            [
                'id'    => 12,
                'title' => 'user_create',
            ],
            [
                'id'    => 13,
                'title' => 'user_edit',
            ],
            [
                'id'    => 14,
                'title' => 'user_show',
            ],
            [
                'id'    => 15,
                'title' => 'user_delete',
            ],
            [
                'id'    => 16,
                'title' => 'user_access',
            ],
            [
                'id'    => 17,
                'title' => 'country_create',
            ],
            [
                'id'    => 18,
                'title' => 'country_edit',
            ],
            [
                'id'    => 19,
                'title' => 'country_show',
            ],
            [
                'id'    => 20,
                'title' => 'country_delete',
            ],
            [
                'id'    => 21,
                'title' => 'country_access',
            ],
            [
                'id'    => 22,
                'title' => 'setting_access',
            ],
            [
                'id'    => 23,
                'title' => 'client_create',
            ],
            [
                'id'    => 24,
                'title' => 'client_edit',
            ],
            [
                'id'    => 25,
                'title' => 'client_show',
            ],
            [
                'id'    => 26,
                'title' => 'client_delete',
            ],
            [
                'id'    => 27,
                'title' => 'client_access',
            ],
            [
                'id'    => 28,
                'title' => 'brand_create',
            ],
            [
                'id'    => 29,
                'title' => 'brand_edit',
            ],
            [
                'id'    => 30,
                'title' => 'brand_show',
            ],
            [
                'id'    => 31,
                'title' => 'brand_delete',
            ],
            [
                'id'    => 32,
                'title' => 'brand_access',
            ],
            [
                'id'    => 33,
                'title' => 'vehicle_create',
            ],
            [
                'id'    => 34,
                'title' => 'vehicle_edit',
            ],
            [
                'id'    => 35,
                'title' => 'vehicle_show',
            ],
            [
                'id'    => 36,
                'title' => 'vehicle_delete',
            ],
            [
                'id'    => 37,
                'title' => 'vehicle_access',
            ],
            [
                'id'    => 38,
                'title' => 'suplier_create',
            ],
            [
                'id'    => 39,
                'title' => 'suplier_edit',
            ],
            [
                'id'    => 40,
                'title' => 'suplier_show',
            ],
            [
                'id'    => 41,
                'title' => 'suplier_delete',
            ],
            [
                'id'    => 42,
                'title' => 'suplier_access',
            ],
            [
                'id'    => 43,
                'title' => 'payment_status_create',
            ],
            [
                'id'    => 44,
                'title' => 'payment_status_edit',
            ],
            [
                'id'    => 45,
                'title' => 'payment_status_show',
            ],
            [
                'id'    => 46,
                'title' => 'payment_status_delete',
            ],
            [
                'id'    => 47,
                'title' => 'payment_status_access',
            ],
            [
                'id'    => 48,
                'title' => 'carrier_create',
            ],
            [
                'id'    => 49,
                'title' => 'carrier_edit',
            ],
            [
                'id'    => 50,
                'title' => 'carrier_show',
            ],
            [
                'id'    => 51,
                'title' => 'carrier_delete',
            ],
            [
                'id'    => 52,
                'title' => 'carrier_access',
            ],
            [
                'id'    => 53,
                'title' => 'pickup_state_create',
            ],
            [
                'id'    => 54,
                'title' => 'pickup_state_edit',
            ],
            [
                'id'    => 55,
                'title' => 'pickup_state_show',
            ],
            [
                'id'    => 56,
                'title' => 'pickup_state_delete',
            ],
            [
                'id'    => 57,
                'title' => 'pickup_state_access',
            ],
            [
                'id'    => 58,
                'title' => 'acquisition_create',
            ],
            [
                'id'    => 59,
                'title' => 'acquisition_edit',
            ],
            [
                'id'    => 60,
                'title' => 'acquisition_show',
            ],
            [
                'id'    => 61,
                'title' => 'acquisition_delete',
            ],
            [
                'id'    => 62,
                'title' => 'acquisition_access',
            ],
            [
                'id'    => 63,
                'title' => 'expedition_create',
            ],
            [
                'id'    => 64,
                'title' => 'expedition_edit',
            ],
            [
                'id'    => 65,
                'title' => 'expedition_show',
            ],
            [
                'id'    => 66,
                'title' => 'expedition_delete',
            ],
            [
                'id'    => 67,
                'title' => 'expedition_access',
            ],
            [
                'id'    => 68,
                'title' => 'sale_create',
            ],
            [
                'id'    => 69,
                'title' => 'sale_edit',
            ],
            [
                'id'    => 70,
                'title' => 'sale_show',
            ],
            [
                'id'    => 71,
                'title' => 'sale_delete',
            ],
            [
                'id'    => 72,
                'title' => 'sale_access',
            ],
            [
                'id'    => 73,
                'title' => 'profile_password_edit',
            ],
        ];

        Permission::insert($permissions);
    }
}

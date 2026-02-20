<?php

namespace App\Http\Requests;

use App\Domain\Repairs\RepairRules;
use App\Models\Repair;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreRepairRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('repair_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if (RepairRules::hasOpenRepairs((int) $value)) {
                        $fail('Ja existe uma intervencao aberta para esta viatura.');
                    }
                },
            ],
            'kilometers' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'front_windshield_text' => [
                'string',
                'nullable',
            ],
            'front_lights_text' => [
                'string',
                'nullable',
            ],
            'rear_lights_text' => [
                'string',
                'nullable',
            ],
            'horn_functionality_text' => [
                'string',
                'nullable',
            ],
            'wiper_blades_water_level_text' => [
                'string',
                'nullable',
            ],
            'brake_clutch_oil_level_text' => [
                'string',
                'nullable',
            ],
            'electrical_systems_text' => [
                'string',
                'nullable',
            ],
            'engine_coolant_level_text' => [
                'string',
                'nullable',
            ],
            'engine_oil_level_text' => [
                'string',
                'nullable',
            ],
            'filters_air_cabin_oil_fuel_text' => [
                'string',
                'nullable',
            ],
            'check_leaks_engine_gearbox_steering_text' => [
                'string',
                'nullable',
            ],
            'brake_pads_disks_text' => [
                'string',
                'nullable',
            ],
            'shock_absorbers_text' => [
                'string',
                'nullable',
            ],
            'tire_condition_text' => [
                'string',
                'nullable',
            ],
            'battery_text' => [
                'string',
                'nullable',
            ],
            'spare_tire_vest_triangle_tools_text' => [
                'string',
                'nullable',
            ],
            'check_clearance_text' => [
                'string',
                'nullable',
            ],
            'check_shields_text' => [
                'string',
                'nullable',
            ],
            'paint_condition_text' => [
                'string',
                'nullable',
            ],
            'dents_text' => [
                'string',
                'nullable',
            ],
            'diverse_strips_text' => [
                'string',
                'nullable',
            ],
            'diverse_plastics_check_scratches_text' => [
                'string',
                'nullable',
            ],
            'wheels_text' => [
                'string',
                'nullable',
            ],
            'bolts_paint_text' => [
                'string',
                'nullable',
            ],
            'seat_belts_text' => [
                'string',
                'nullable',
            ],
            'radio_text' => [
                'string',
                'nullable',
            ],
            'air_conditioning_text' => [
                'string',
                'nullable',
            ],
            'front_rear_window_functionality_text' => [
                'string',
                'nullable',
            ],
            'seats_upholstery_text' => [
                'string',
                'nullable',
            ],
            'sun_visors_text' => [
                'string',
                'nullable',
            ],
            'carpets_text' => [
                'string',
                'nullable',
            ],
            'trunk_shelf_text' => [
                'string',
                'nullable',
            ],
            'buttons_text' => [
                'string',
                'nullable',
            ],
            'door_panels_text' => [
                'string',
                'nullable',
            ],
            'locks_text' => [
                'string',
                'nullable',
            ],
            'interior_covers_headlights_taillights_text' => [
                'string',
                'nullable',
            ],
            'open_close_doors_remote_control_all_functions_text' => [
                'string',
                'nullable',
            ],
            'turn_on_ac_check_glass_text' => [
                'string',
                'nullable',
            ],
            'check_engine_lift_hood_text' => [
                'string',
                'nullable',
            ],
            'connect_vehicle_to_scanner_check_errors_text' => [
                'string',
                'nullable',
            ],
            'check_chassis_confirm_with_registration_text' => [
                'string',
                'nullable',
            ],
            'manufacturer_plate_text' => [
                'string',
                'nullable',
            ],
            'check_chassis_stickers_text' => [
                'string',
                'nullable',
            ],
            'check_gearbox_oil_text' => [
                'string',
                'nullable',
            ],
            'work_performed' => [
                'string',
                'nullable',
            ],
            'materials_used' => [
                'string',
                'nullable',
            ],
            'expected_completion_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'timestamp' => [
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
                'nullable',
            ],
            'name' => [
                'string',
                'nullable',
            ],
        ];
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\Auditable;

class Repair extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory, Auditable;

    public $table = 'repairs';

    protected $appends = [
        'checkin',
        'checkout',
    ];

    protected $dates = [
        'timestamp',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'vehicle_id',
        'obs_1',
        'kilometers',
        'front_windshield',
        'front_windshield_text',
        'front_lights',
        'front_lights_text',
        'rear_lights',
        'rear_lights_text',
        'horn_functionality',
        'horn_functionality_text',
        'wiper_blades_water_level',
        'wiper_blades_water_level_text',
        'brake_clutch_oil_level',
        'brake_clutch_oil_level_text',
        'electrical_systems',
        'electrical_systems_text',
        'engine_coolant_level',
        'engine_coolant_level_text',
        'engine_oil_level',
        'engine_oil_level_text',
        'filters_air_cabin_oil_fuel',
        'filters_air_cabin_oil_fuel_text',
        'check_leaks_engine_gearbox_steering',
        'check_leaks_engine_gearbox_steering_text',
        'brake_pads_disks',
        'brake_pads_disks_text',
        'shock_absorbers',
        'shock_absorbers_text',
        'tire_condition',
        'tire_condition_text',
        'battery',
        'battery_text',
        'spare_tire_vest_triangle_tools',
        'spare_tire_vest_triangle_tools_text',
        'check_clearance',
        'check_clearance_text',
        'check_shields',
        'check_shields_text',
        'paint_condition',
        'paint_condition_text',
        'dents',
        'dents_text',
        'diverse_strips',
        'diverse_strips_text',
        'diverse_plastics_check_scratches',
        'diverse_plastics_check_scratches_text',
        'wheels',
        'wheels_text',
        'bolts_paint',
        'bolts_paint_text',
        'seat_belts',
        'seat_belts_text',
        'radio',
        'radio_text',
        'air_conditioning',
        'air_conditioning_text',
        'front_rear_window_functionality',
        'front_rear_window_functionality_text',
        'seats_upholstery',
        'seats_upholstery_text',
        'sun_visors',
        'sun_visors_text',
        'carpets',
        'carpets_text',
        'trunk_shelf',
        'trunk_shelf_text',
        'buttons',
        'buttons_text',
        'door_panels',
        'door_panels_text',
        'locks',
        'locks_text',
        'interior_covers_headlights_taillights',
        'interior_covers_headlights_taillights_text',
        'open_close_doors_remote_control_all_functions',
        'open_close_doors_remote_control_all_functions_text',
        'turn_on_ac_check_glass',
        'turn_on_ac_check_glass_text',
        'check_engine_lift_hood',
        'check_engine_lift_hood_text',
        'connect_vehicle_to_scanner_check_errors',
        'connect_vehicle_to_scanner_check_errors_text',
        'check_chassis_confirm_with_registration',
        'check_chassis_confirm_with_registration_text',
        'manufacturer_plate',
        'manufacturer_plate_text',
        'check_chassis_stickers',
        'check_chassis_stickers_text',
        'check_gearbox_oil',
        'check_gearbox_oil_text',
        'obs_2',
        'work_performed',
        'materials_used',
        'expected_completion_date',
        'timestamp',
        'name',
        'repair_state_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getChecklistPercentageAttribute()
    {
        $fields = [
            'front_windshield',
            'front_lights',
            'rear_lights',
            'horn_functionality',
            'wiper_blades_water_level',
            'brake_clutch_oil_level',
            'electrical_systems',
            'engine_coolant_level',
            'engine_oil_level',
            'filters_air_cabin_oil_fuel',
            'check_leaks_engine_gearbox_steering',
            'brake_pads_disks',
            'shock_absorbers',
            'tire_condition',
            'battery',
            'spare_tire_vest_triangle_tools',
            'check_clearance',
            'check_shields',
            'paint_condition',
            'dents',
            'diverse_strips',
            'diverse_plastics_check_scratches',
            'wheels',
            'bolts_paint',
            'seat_belts',
            'radio',
            'air_conditioning',
            'front_rear_window_functionality',
            'seats_upholstery',
            'sun_visors',
            'carpets',
            'trunk_shelf',
            'buttons',
            'door_panels',
            'locks',
            'interior_covers_headlights_taillights',
            'open_close_doors_remote_control_all_functions',
            'turn_on_ac_check_glass',
            'check_engine_lift_hood',
            'connect_vehicle_to_scanner_check_errors',
            'check_chassis_confirm_with_registration',
            'manufacturer_plate',
            'check_chassis_stickers',
            'check_gearbox_oil',
        ];

        $total = count($fields);
        $checked = collect($fields)->filter(fn($field) => $this->$field)->count();

        return round(($checked / $total) * 100);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function getCheckinAttribute()
    {
        $files = $this->getMedia('checkin');
        $files->each(function ($item) {
            $item->url       = $item->getUrl();
            $item->thumbnail = $item->getUrl('thumb');
            $item->preview   = $item->getUrl('preview');
        });

        return $files;
    }

    public function getCheckoutAttribute()
    {
        $files = $this->getMedia('checkout');
        $files->each(function ($item) {
            $item->url       = $item->getUrl();
            $item->thumbnail = $item->getUrl('thumb');
            $item->preview   = $item->getUrl('preview');
        });

        return $files;
    }

    public function getTimestampAttribute($value)
    {
        return $value ? Carbon::createFromFormat('Y-m-d H:i:s', $value)->format(config('panel.date_format') . ' ' . config('panel.time_format')) : null;
    }

    public function getExpectedCompletionDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setTimestampAttribute($value)
    {
        $this->attributes['timestamp'] = $value ? Carbon::createFromFormat(config('panel.date_format') . ' ' . config('panel.time_format'), $value)->format('Y-m-d H:i:s') : null;
    }

    public function setExpectedCompletionDateAttribute($value)
    {
        $this->attributes['expected_completion_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function repair_state()
    {
        return $this->belongsTo(RepairState::class, 'repair_state_id');
    }

    public function timelogs()
    {
        return $this->hasMany(Timelog::class, 'repair_id');
    }
}

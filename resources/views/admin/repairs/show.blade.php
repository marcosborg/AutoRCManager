@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.repair.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.repairs.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $repair->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.vehicle') }}
                                    </th>
                                    <td>
                                        {{ $repair->vehicle->license ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.obs_1') }}
                                    </th>
                                    <td>
                                        {{ $repair->obs_1 }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.user') }}
                                    </th>
                                    <td>
                                        {{ $repair->user->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.kilometers') }}
                                    </th>
                                    <td>
                                        {{ $repair->kilometers }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.front_windshield') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->front_windshield ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.front_windshield_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->front_windshield_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.front_lights') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->front_lights ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.front_lights_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->front_lights_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.rear_lights') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->rear_lights ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.rear_lights_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->rear_lights_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.horn_functionality') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->horn_functionality ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.horn_functionality_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->horn_functionality_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.wiper_blades_water_level') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->wiper_blades_water_level ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.wiper_blades_water_level_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->wiper_blades_water_level_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.brake_clutch_oil_level') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->brake_clutch_oil_level ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.brake_clutch_oil_level_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->brake_clutch_oil_level_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.electrical_systems') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->electrical_systems ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.electrical_systems_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->electrical_systems_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.engine_coolant_level') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->engine_coolant_level ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.engine_coolant_level_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->engine_coolant_level_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.engine_oil_level') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->engine_oil_level ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.engine_oil_level_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->engine_oil_level_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->filters_air_cabin_oil_fuel ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->filters_air_cabin_oil_fuel_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_leaks_engine_gearbox_steering ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_leaks_engine_gearbox_steering_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.brake_pads_disks') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->brake_pads_disks ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.brake_pads_disks_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->brake_pads_disks_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.shock_absorbers') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->shock_absorbers ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.shock_absorbers_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->shock_absorbers_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.tire_condition') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->tire_condition ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.tire_condition_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->tire_condition_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.battery') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->battery ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.battery_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->battery_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->spare_tire_vest_triangle_tools ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->spare_tire_vest_triangle_tools_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_clearance') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_clearance ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_clearance_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_clearance_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_shields') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_shields ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_shields_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_shields_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.paint_condition') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->paint_condition ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.paint_condition_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->paint_condition_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.dents') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->dents ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.dents_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->dents_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.diverse_strips') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->diverse_strips ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.diverse_strips_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->diverse_strips_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.diverse_plastics_check_scratches') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->diverse_plastics_check_scratches ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.diverse_plastics_check_scratches_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->diverse_plastics_check_scratches_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.wheels') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->wheels ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.wheels_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->wheels_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.bolts_paint') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->bolts_paint ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.bolts_paint_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->bolts_paint_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.seat_belts') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->seat_belts ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.seat_belts_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->seat_belts_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.radio') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->radio ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.radio_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->radio_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.air_conditioning') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->air_conditioning ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.air_conditioning_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->air_conditioning_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.front_rear_window_functionality') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->front_rear_window_functionality ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.front_rear_window_functionality_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->front_rear_window_functionality_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.seats_upholstery') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->seats_upholstery ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.seats_upholstery_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->seats_upholstery_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.sun_visors') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->sun_visors ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.sun_visors_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->sun_visors_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.carpets') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->carpets ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.carpets_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->carpets_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.trunk_shelf') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->trunk_shelf ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.trunk_shelf_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->trunk_shelf_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.buttons') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->buttons ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.buttons_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->buttons_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.door_panels') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->door_panels ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.door_panels_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->door_panels_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.locks') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->locks ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.locks_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->locks_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.interior_covers_headlights_taillights') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->interior_covers_headlights_taillights ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.interior_covers_headlights_taillights_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->interior_covers_headlights_taillights_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->open_close_doors_remote_control_all_functions ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->open_close_doors_remote_control_all_functions_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.turn_on_ac_check_glass') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->turn_on_ac_check_glass ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.turn_on_ac_check_glass_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->turn_on_ac_check_glass_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_engine_lift_hood') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_engine_lift_hood ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_engine_lift_hood_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_engine_lift_hood_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->connect_vehicle_to_scanner_check_errors ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->connect_vehicle_to_scanner_check_errors_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_chassis_confirm_with_registration') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_chassis_confirm_with_registration ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_chassis_confirm_with_registration_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_chassis_confirm_with_registration_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.manufacturer_plate') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->manufacturer_plate ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.manufacturer_plate_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->manufacturer_plate_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_chassis_stickers') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_chassis_stickers ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_chassis_stickers_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_chassis_stickers_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_gearbox_oil') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $repair->check_gearbox_oil ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.check_gearbox_oil_text') }}
                                    </th>
                                    <td>
                                        {{ $repair->check_gearbox_oil_text }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.obs_2') }}
                                    </th>
                                    <td>
                                        {{ $repair->obs_2 }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.timestamp') }}
                                    </th>
                                    <td>
                                        {{ $repair->timestamp }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.expected_completion_date') }}
                                    </th>
                                    <td>
                                        {{ $repair->expected_completion_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $repair->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.repair.fields.repair_state') }}
                                    </th>
                                    <td>
                                        {{ $repair->repair_state->name ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.repairs.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

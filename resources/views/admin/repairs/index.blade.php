@extends('layouts.admin')
@section('content')
<div class="content">
    @can('repair_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.repairs.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.repair.title_singular') }}
                </a>
                <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                    {{ trans('global.app_csvImport') }}
                </button>
                @include('csvImport.modal', ['model' => 'Repair', 'route' => 'admin.repairs.parseCsvImport'])
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.repair.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Repair">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.vehicle') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.obs_1') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.user') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.kilometers') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.front_windshield') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.front_windshield_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.front_lights') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.front_lights_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.rear_lights') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.rear_lights_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.horn_functionality') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.horn_functionality_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.wiper_blades_water_level') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.wiper_blades_water_level_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.brake_clutch_oil_level') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.brake_clutch_oil_level_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.electrical_systems') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.electrical_systems_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.engine_coolant_level') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.engine_coolant_level_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.engine_oil_level') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.engine_oil_level_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.brake_pads_disks') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.brake_pads_disks_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.shock_absorbers') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.shock_absorbers_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.tire_condition') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.tire_condition_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.battery') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.battery_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_clearance') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_clearance_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_shields') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_shields_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.paint_condition') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.paint_condition_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.dents') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.dents_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.diverse_strips') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.diverse_strips_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.diverse_plastics_check_scratches') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.diverse_plastics_check_scratches_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.wheels') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.wheels_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.bolts_paint') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.bolts_paint_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.seat_belts') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.seat_belts_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.radio') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.radio_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.air_conditioning') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.air_conditioning_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.front_rear_window_functionality') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.front_rear_window_functionality_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.seats_upholstery') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.seats_upholstery_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.sun_visors') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.sun_visors_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.carpets') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.carpets_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.trunk_shelf') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.trunk_shelf_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.buttons') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.buttons_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.door_panels') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.door_panels_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.locks') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.locks_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.interior_covers_headlights_taillights') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.interior_covers_headlights_taillights_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.turn_on_ac_check_glass') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.turn_on_ac_check_glass_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_engine_lift_hood') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_engine_lift_hood_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_chassis_confirm_with_registration') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_chassis_confirm_with_registration_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.manufacturer_plate') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.manufacturer_plate_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_chassis_stickers') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_chassis_stickers_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_gearbox_oil') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.check_gearbox_oil_text') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.obs_2') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.timestamp') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.name') }}
                                </th>
                                <th>
                                    {{ trans('cruds.repair.fields.repair_state') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                            <tr>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($vehicles as $key => $item)
                                            <option value="{{ $item->license }}">{{ $item->license }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($users as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($repair_states as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                </td>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('repair_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.repairs.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).data(), function (entry) {
          return entry.id
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: "{{ route('admin.repairs.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'vehicle_license', name: 'vehicle.license' },
{ data: 'obs_1', name: 'obs_1' },
{ data: 'user_name', name: 'user.name' },
{ data: 'kilometers', name: 'kilometers' },
{ data: 'front_windshield', name: 'front_windshield' },
{ data: 'front_windshield_text', name: 'front_windshield_text' },
{ data: 'front_lights', name: 'front_lights' },
{ data: 'front_lights_text', name: 'front_lights_text' },
{ data: 'rear_lights', name: 'rear_lights' },
{ data: 'rear_lights_text', name: 'rear_lights_text' },
{ data: 'horn_functionality', name: 'horn_functionality' },
{ data: 'horn_functionality_text', name: 'horn_functionality_text' },
{ data: 'wiper_blades_water_level', name: 'wiper_blades_water_level' },
{ data: 'wiper_blades_water_level_text', name: 'wiper_blades_water_level_text' },
{ data: 'brake_clutch_oil_level', name: 'brake_clutch_oil_level' },
{ data: 'brake_clutch_oil_level_text', name: 'brake_clutch_oil_level_text' },
{ data: 'electrical_systems', name: 'electrical_systems' },
{ data: 'electrical_systems_text', name: 'electrical_systems_text' },
{ data: 'engine_coolant_level', name: 'engine_coolant_level' },
{ data: 'engine_coolant_level_text', name: 'engine_coolant_level_text' },
{ data: 'engine_oil_level', name: 'engine_oil_level' },
{ data: 'engine_oil_level_text', name: 'engine_oil_level_text' },
{ data: 'filters_air_cabin_oil_fuel', name: 'filters_air_cabin_oil_fuel' },
{ data: 'filters_air_cabin_oil_fuel_text', name: 'filters_air_cabin_oil_fuel_text' },
{ data: 'check_leaks_engine_gearbox_steering', name: 'check_leaks_engine_gearbox_steering' },
{ data: 'check_leaks_engine_gearbox_steering_text', name: 'check_leaks_engine_gearbox_steering_text' },
{ data: 'brake_pads_disks', name: 'brake_pads_disks' },
{ data: 'brake_pads_disks_text', name: 'brake_pads_disks_text' },
{ data: 'shock_absorbers', name: 'shock_absorbers' },
{ data: 'shock_absorbers_text', name: 'shock_absorbers_text' },
{ data: 'tire_condition', name: 'tire_condition' },
{ data: 'tire_condition_text', name: 'tire_condition_text' },
{ data: 'battery', name: 'battery' },
{ data: 'battery_text', name: 'battery_text' },
{ data: 'spare_tire_vest_triangle_tools', name: 'spare_tire_vest_triangle_tools' },
{ data: 'spare_tire_vest_triangle_tools_text', name: 'spare_tire_vest_triangle_tools_text' },
{ data: 'check_clearance', name: 'check_clearance' },
{ data: 'check_clearance_text', name: 'check_clearance_text' },
{ data: 'check_shields', name: 'check_shields' },
{ data: 'check_shields_text', name: 'check_shields_text' },
{ data: 'paint_condition', name: 'paint_condition' },
{ data: 'paint_condition_text', name: 'paint_condition_text' },
{ data: 'dents', name: 'dents' },
{ data: 'dents_text', name: 'dents_text' },
{ data: 'diverse_strips', name: 'diverse_strips' },
{ data: 'diverse_strips_text', name: 'diverse_strips_text' },
{ data: 'diverse_plastics_check_scratches', name: 'diverse_plastics_check_scratches' },
{ data: 'diverse_plastics_check_scratches_text', name: 'diverse_plastics_check_scratches_text' },
{ data: 'wheels', name: 'wheels' },
{ data: 'wheels_text', name: 'wheels_text' },
{ data: 'bolts_paint', name: 'bolts_paint' },
{ data: 'bolts_paint_text', name: 'bolts_paint_text' },
{ data: 'seat_belts', name: 'seat_belts' },
{ data: 'seat_belts_text', name: 'seat_belts_text' },
{ data: 'radio', name: 'radio' },
{ data: 'radio_text', name: 'radio_text' },
{ data: 'air_conditioning', name: 'air_conditioning' },
{ data: 'air_conditioning_text', name: 'air_conditioning_text' },
{ data: 'front_rear_window_functionality', name: 'front_rear_window_functionality' },
{ data: 'front_rear_window_functionality_text', name: 'front_rear_window_functionality_text' },
{ data: 'seats_upholstery', name: 'seats_upholstery' },
{ data: 'seats_upholstery_text', name: 'seats_upholstery_text' },
{ data: 'sun_visors', name: 'sun_visors' },
{ data: 'sun_visors_text', name: 'sun_visors_text' },
{ data: 'carpets', name: 'carpets' },
{ data: 'carpets_text', name: 'carpets_text' },
{ data: 'trunk_shelf', name: 'trunk_shelf' },
{ data: 'trunk_shelf_text', name: 'trunk_shelf_text' },
{ data: 'buttons', name: 'buttons' },
{ data: 'buttons_text', name: 'buttons_text' },
{ data: 'door_panels', name: 'door_panels' },
{ data: 'door_panels_text', name: 'door_panels_text' },
{ data: 'locks', name: 'locks' },
{ data: 'locks_text', name: 'locks_text' },
{ data: 'interior_covers_headlights_taillights', name: 'interior_covers_headlights_taillights' },
{ data: 'interior_covers_headlights_taillights_text', name: 'interior_covers_headlights_taillights_text' },
{ data: 'open_close_doors_remote_control_all_functions', name: 'open_close_doors_remote_control_all_functions' },
{ data: 'open_close_doors_remote_control_all_functions_text', name: 'open_close_doors_remote_control_all_functions_text' },
{ data: 'turn_on_ac_check_glass', name: 'turn_on_ac_check_glass' },
{ data: 'turn_on_ac_check_glass_text', name: 'turn_on_ac_check_glass_text' },
{ data: 'check_engine_lift_hood', name: 'check_engine_lift_hood' },
{ data: 'check_engine_lift_hood_text', name: 'check_engine_lift_hood_text' },
{ data: 'connect_vehicle_to_scanner_check_errors', name: 'connect_vehicle_to_scanner_check_errors' },
{ data: 'connect_vehicle_to_scanner_check_errors_text', name: 'connect_vehicle_to_scanner_check_errors_text' },
{ data: 'check_chassis_confirm_with_registration', name: 'check_chassis_confirm_with_registration' },
{ data: 'check_chassis_confirm_with_registration_text', name: 'check_chassis_confirm_with_registration_text' },
{ data: 'manufacturer_plate', name: 'manufacturer_plate' },
{ data: 'manufacturer_plate_text', name: 'manufacturer_plate_text' },
{ data: 'check_chassis_stickers', name: 'check_chassis_stickers' },
{ data: 'check_chassis_stickers_text', name: 'check_chassis_stickers_text' },
{ data: 'check_gearbox_oil', name: 'check_gearbox_oil' },
{ data: 'check_gearbox_oil_text', name: 'check_gearbox_oil_text' },
{ data: 'obs_2', name: 'obs_2' },
{ data: 'timestamp', name: 'timestamp' },
{ data: 'name', name: 'name' },
{ data: 'repair_state_name', name: 'repair_state.name' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-Repair').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
let visibleColumnsIndexes = null;
$('.datatable thead').on('input', '.search', function () {
      let strict = $(this).attr('strict') || false
      let value = strict && this.value ? "^" + this.value + "$" : this.value

      let index = $(this).parent().index()
      if (visibleColumnsIndexes !== null) {
        index = visibleColumnsIndexes[index]
      }

      table
        .column(index)
        .search(value, strict)
        .draw()
  });
table.on('column-visibility.dt', function(e, settings, column, state) {
      visibleColumnsIndexes = []
      table.columns(":visible").every(function(colIdx) {
          visibleColumnsIndexes.push(colIdx);
      });
  })
});

</script>
@endsection
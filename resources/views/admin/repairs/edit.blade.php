@extends('layouts.admin')
@section('content')
@php($vehicle = $repair->vehicle)
<div class="content">
<div id="progress-container" style="position: sticky; top: 0; z-index: 999; background: #f8f9fa; padding: 10px;">
    <div class="progress" style="height: 25px;">
        <div id="progress-bar" class="progress-bar bg-success progress-bar-striped progress-bar-animated"
             role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            0%
        </div>
    </div>
</div>



    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Viatura</div>
                <div class="panel-body">
                    <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('general_state') ? 'has-error' : '' }}">
                                    <label class="required" for="general_state_id">{{ trans('cruds.vehicle.fields.general_state') }}</label>
                                    <select class="form-control select2" name="general_state_id" id="general_state_id" disabled>
                                        @foreach($general_states as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('general_state_id') ? old('general_state_id') : optional(optional($vehicle)->general_state)->id) == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('general_state'))
                                    <span class="help-block" role="alert">{{ $errors->first('general_state') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.general_state_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                                    <label for="license">{{ trans('cruds.vehicle.fields.license') }}</label>
                                    <input class="form-control" type="text" name="license" id="license" value="{{ old('license', optional($vehicle)->license) }}" disabled>
                                    @if($errors->has('license'))
                                        <span class="help-block" role="alert">{{ $errors->first('license') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.license_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('foreign_license') ? 'has-error' : '' }}">
                                    <label for="foreign_license">{{ trans('cruds.vehicle.fields.foreign_license') }}</label>
                                    <input disabled class="form-control" type="text" name="foreign_license" id="foreign_license" value="{{ old('foreign_license', optional($vehicle)->foreign_license) }}">
                                    @if($errors->has('foreign_license'))
                                        <span class="help-block" role="alert">{{ $errors->first('foreign_license') }}</span>
                                    @endif
                                <span class="help-block">{{ trans('cruds.vehicle.fields.foreign_license_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('brand') ? 'has-error' : '' }}">
                                    <label for="brand_id">{{ trans('cruds.vehicle.fields.brand') }}</label>
                                    <select class="form-control select2" name="brand_id" id="brand_id" disabled>
                                        @foreach($brands as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('brand_id') ? old('brand_id') : optional(optional($vehicle)->brand)->id) == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('brand'))
                                        <span class="help-block" role="alert">{{ $errors->first('brand') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.brand_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('model') ? 'has-error' : '' }}">
                                    <label for="model">{{ trans('cruds.vehicle.fields.model') }}</label>
                                    <input disabled class="form-control" type="text" name="model" id="model" value="{{ old('model', optional($vehicle)->model) }}">
                                    @if($errors->has('model'))
                                        <span class="help-block" role="alert">{{ $errors->first('model') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.model_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('version') ? 'has-error' : '' }}">
                                    <label for="version">{{ trans('cruds.vehicle.fields.version') }}</label>
                                    <input disabled class="form-control" type="text" name="version" id="version" value="{{ old('version', optional($vehicle)->version) }}">
                                    @if($errors->has('version'))
                                        <span class="help-block" role="alert">{{ $errors->first('version') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.version_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('transmission') ? 'has-error' : '' }}">
                                    <label>{{ trans('cruds.vehicle.fields.transmission') }}</label>
                                    <select disabled class="form-control" name="transmission" id="transmission">
                                        <option value disabled {{ old('transmission', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                                        @foreach(App\Models\Vehicle::TRANSMISSION_SELECT as $key => $label)
                                            <option value="{{ $key }}" {{ old('transmission', optional($vehicle)->transmission) === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('transmission'))
                                        <span class="help-block" role="alert">{{ $errors->first('transmission') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.transmission_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('engine_displacement') ? 'has-error' : '' }}">
                                    <label for="engine_displacement">{{ trans('cruds.vehicle.fields.engine_displacement') }}</label>
                                    <input disabled class="form-control" type="text" name="engine_displacement" id="engine_displacement" value="{{ old('engine_displacement', optional($vehicle)->engine_displacement) }}">
                                    @if($errors->has('engine_displacement'))
                                        <span class="help-block" role="alert">{{ $errors->first('engine_displacement') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.engine_displacement_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('year') ? 'has-error' : '' }}">
                                    <label for="year">{{ trans('cruds.vehicle.fields.year') }}</label>
                                    <input disabled class="form-control" type="number" name="year" id="year" value="{{ old('year', optional($vehicle)->year) }}" step="1">
                                    @if($errors->has('year'))
                                        <span class="help-block" role="alert">{{ $errors->first('year') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.year_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('month') ? 'has-error' : '' }}">
                                    <label for="month">{{ trans('cruds.vehicle.fields.month') }}</label>
                                    <input disabled class="form-control" type="text" name="month" id="month" value="{{ old('month', optional($vehicle)->month) }}">
                                    @if($errors->has('month'))
                                        <span class="help-block" role="alert">{{ $errors->first('month') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.month_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('license_date') ? 'has-error' : '' }}">
                                    <label for="license_date">{{ trans('cruds.vehicle.fields.license_date') }}</label>
                                    <input disabled class="form-control date" type="text" name="license_date" id="license_date" value="{{ old('license_date', optional($vehicle)->license_date) }}">
                                    @if($errors->has('license_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('license_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.license_date_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('color') ? 'has-error' : '' }}">
                                    <label for="color">{{ trans('cruds.vehicle.fields.color') }}</label>
                                    <input disabled class="form-control" type="text" name="color" id="color" value="{{ old('color', optional($vehicle)->color) }}">
                                    @if($errors->has('color'))
                                        <span class="help-block" role="alert">{{ $errors->first('color') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.color_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('fuel') ? 'has-error' : '' }}">
                                    <label for="fuel">{{ trans('cruds.vehicle.fields.fuel') }}</label>
                                    <input disabled class="form-control" type="text" name="fuel" id="fuel" value="{{ old('fuel', optional($vehicle)->fuel) }}">
                                    @if($errors->has('fuel'))
                                        <span class="help-block" role="alert">{{ $errors->first('fuel') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.fuel_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                                    <label for="kilometers">{{ trans('cruds.vehicle.fields.kilometers') }}</label>
                                    <input disabled class="form-control" type="number" name="kilometers" id="kilometers" value="{{ old('kilometers', optional($vehicle)->kilometers) }}" step="1">
                                    @if($errors->has('kilometers'))
                                        <span class="help-block" role="alert">{{ $errors->first('kilometers') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.kilometers_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('inspec_b') ? 'has-error' : '' }}">
                                    <label for="inspec_b">{{ trans('cruds.vehicle.fields.inspec_b') }}</label>
                                    <input disabled class="form-control" type="text" name="inspec_b" id="inspec_b" value="{{ old('inspec_b', optional($vehicle)->inspec_b) }}">
                                    @if($errors->has('inspec_b'))
                                        <span class="help-block" role="alert">{{ $errors->first('inspec_b') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.inspec_b_helper') }}</span>
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    Historico de intervencoes da viatura
                    @can('repair_create')
                        <span class="pull-right">
                            @if($canCreateNewIntervention)
                                <form method="POST" action="{{ route('admin.repairs.newIntervention', $repair->id) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-xs btn-success" type="submit">
                                        Nova intervencao
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-xs btn-default" type="button" disabled title="Feche a intervencao aberta para criar outra.">
                                    Nova intervencao
                                </button>
                            @endif
                        </span>
                    @endcan
                </div>
                <div class="panel-body">
                    @if($vehicleRepairs->isEmpty())
                        <p class="text-muted">Sem intervencoes registadas para esta viatura.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Abertura</th>
                                        <th>Estado</th>
                                        <th>Previsao</th>
                                        <th>Checklist</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vehicleRepairs as $item)
                                        <tr class="{{ $item->id === $repair->id ? 'info' : '' }}">
                                            <td>#{{ $item->id }}</td>
                                            <td>{{ optional($item->created_at)->format('Y-m-d H:i') }}</td>
                                            <td>{{ $item->repair_state->name ?? 'Aberta' }}</td>
                                            <td>{{ $item->expected_completion_date ?: '-' }}</td>
                                            <td>{{ $item->checklist_percentage }}%</td>
                                            <td>
                                                @if($item->id === $repair->id)
                                                    <span class="label label-info">Atual</span>
                                                @else
                                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.repairs.edit', $item->id) }}">
                                                        Abrir
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    Entrada em oficina
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.repairs.update", [$repair->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-5">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group {{ $errors->has('vehicle') ? 'has-error' : '' }}">
                                                    <label class="required" for="vehicle_id">{{ trans('cruds.repair.fields.vehicle') }}</label>
                                                    <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                                        @foreach($vehicles as $id => $entry)
                                                        <option value="{{ $id }}" {{ (old('vehicle_id') ? old('vehicle_id') : $repair->vehicle_id) == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($errors->has('vehicle'))
                                                    <span class="help-block" role="alert">{{ $errors->first('vehicle') }}</span>
                                                    @endif
                                                    <span class="help-block">{{ trans('cruds.repair.fields.vehicle_helper') }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                                                    <label for="kilometers">{{ trans('cruds.repair.fields.kilometers') }}</label>
                                                    <input class="form-control" type="number" name="kilometers" id="kilometers" value="{{ old('kilometers', $repair->kilometers) }}" step="1">
                                                    @if($errors->has('kilometers'))
                                                    <span class="help-block" role="alert">{{ $errors->first('kilometers') }}</span>
                                                    @endif
                                                    <span class="help-block">{{ trans('cruds.repair.fields.kilometers_helper') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group {{ $errors->has('obs_1') ? 'has-error' : '' }}">
                                            <label for="obs_1">{{ trans('cruds.repair.fields.obs_1') }}</label>
                                            <textarea class="form-control" name="obs_1" id="obs_1">{{ old('obs_1', $repair->obs_1) }}</textarea>
                                            @if($errors->has('obs_1'))
                                            <span class="help-block" role="alert">{{ $errors->first('obs_1') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.obs_1_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('checkin') ? 'has-error' : '' }}">
                                            <label for="checkin">{{ trans('cruds.repair.fields.checkin') }}</label>
                                            <div class="needsclick dropzone" id="checkin-dropzone">
                                            </div>                                                    @if($errors->has('checkin'))
                                            <span class="help-block" role="alert">{{ $errors->first('checkin') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.checkin_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @can('repair_timelogs')
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        Registos de Tempo de IntervenÃ§Ã£o
                                    </div>
                                    <div class="panel-body">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Utilizador</th>
                                                    <th>InÃ­cio</th>
                                                    <th>Fim</th>
                                                    <th>Tempo (arredondado)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(($timelogs ?? collect()) as $log)
                                                    <tr>
                                                        <td>{{ $log->user?->name ?? 'Desconhecido' }}</td>
                                                        <td>{{ $log->start_time }}</td>
                                                        <td>{{ $log->end_time }}</td>
                                                        <td>{{ $log->rounded_minutes }} min</td>
                                                    </tr>
                                                @endforeach
                                                <tr>
                                                    <td colspan="3"><strong>Total</strong></td>
                                                    <td><strong>{{ $totalMinutes ?? 0 }} min</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endcan
                        </div>


                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('front_windshield') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="front_windshield" value="0">
                                                <input type="checkbox" name="front_windshield" id="front_windshield" value="1" {{ $repair->front_windshield || old('front_windshield', 0) === 1 ? 'checked' : '' }}>
                                                <label for="front_windshield" style="font-weight: 400">{{ trans('cruds.repair.fields.front_windshield') }}</label>
                                            </div>
                                            @if($errors->has('front_windshield'))
                                            <span class="help-block" role="alert">{{ $errors->first('front_windshield') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.front_windshield_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('front_windshield_text') ? 'has-error' : '' }}">
                                            <label for="front_windshield_text">{{ trans('cruds.repair.fields.front_windshield_text') }}</label>
                                            <input class="form-control" type="text" name="front_windshield_text" id="front_windshield_text" value="{{ old('front_windshield_text', $repair->front_windshield_text) }}">
                                            @if($errors->has('front_windshield_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('front_windshield_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.front_windshield_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('front_lights') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="front_lights" value="0">
                                                <input type="checkbox" name="front_lights" id="front_lights" value="1" {{ $repair->front_lights || old('front_lights', 0) === 1 ? 'checked' : '' }}>
                                                <label for="front_lights" style="font-weight: 400">{{ trans('cruds.repair.fields.front_lights') }}</label>
                                            </div>
                                            @if($errors->has('front_lights'))
                                            <span class="help-block" role="alert">{{ $errors->first('front_lights') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.front_lights_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('front_lights_text') ? 'has-error' : '' }}">
                                            <label for="front_lights_text">{{ trans('cruds.repair.fields.front_lights_text') }}</label>
                                            <input class="form-control" type="text" name="front_lights_text" id="front_lights_text" value="{{ old('front_lights_text', $repair->front_lights_text) }}">
                                            @if($errors->has('front_lights_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('front_lights_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.front_lights_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('rear_lights') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="rear_lights" value="0">
                                                <input type="checkbox" name="rear_lights" id="rear_lights" value="1" {{ $repair->rear_lights || old('rear_lights', 0) === 1 ? 'checked' : '' }}>
                                                <label for="rear_lights" style="font-weight: 400">{{ trans('cruds.repair.fields.rear_lights') }}</label>
                                            </div>
                                            @if($errors->has('rear_lights'))
                                            <span class="help-block" role="alert">{{ $errors->first('rear_lights') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.rear_lights_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('rear_lights_text') ? 'has-error' : '' }}">
                                            <label for="rear_lights_text">{{ trans('cruds.repair.fields.rear_lights_text') }}</label>
                                            <input class="form-control" type="text" name="rear_lights_text" id="rear_lights_text" value="{{ old('rear_lights_text', $repair->rear_lights_text) }}">
                                            @if($errors->has('rear_lights_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('rear_lights_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.rear_lights_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('horn_functionality') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="horn_functionality" value="0">
                                                <input type="checkbox" name="horn_functionality" id="horn_functionality" value="1" {{ $repair->horn_functionality || old('horn_functionality', 0) === 1 ? 'checked' : '' }}>
                                                <label for="horn_functionality" style="font-weight: 400">{{ trans('cruds.repair.fields.horn_functionality') }}</label>
                                            </div>
                                            @if($errors->has('horn_functionality'))
                                            <span class="help-block" role="alert">{{ $errors->first('horn_functionality') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.horn_functionality_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('horn_functionality_text') ? 'has-error' : '' }}">
                                            <label for="horn_functionality_text">{{ trans('cruds.repair.fields.horn_functionality_text') }}</label>
                                            <input class="form-control" type="text" name="horn_functionality_text" id="horn_functionality_text" value="{{ old('horn_functionality_text', $repair->horn_functionality_text) }}">
                                            @if($errors->has('horn_functionality_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('horn_functionality_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.horn_functionality_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('wiper_blades_water_level') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="wiper_blades_water_level" value="0">
                                                <input type="checkbox" name="wiper_blades_water_level" id="wiper_blades_water_level" value="1" {{ $repair->wiper_blades_water_level || old('wiper_blades_water_level', 0) === 1 ? 'checked' : '' }}>
                                                <label for="wiper_blades_water_level" style="font-weight: 400">{{ trans('cruds.repair.fields.wiper_blades_water_level') }}</label>
                                            </div>
                                            @if($errors->has('wiper_blades_water_level'))
                                            <span class="help-block" role="alert">{{ $errors->first('wiper_blades_water_level') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.wiper_blades_water_level_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('wiper_blades_water_level_text') ? 'has-error' : '' }}">
                                            <label for="wiper_blades_water_level_text">{{ trans('cruds.repair.fields.wiper_blades_water_level_text') }}</label>
                                            <input class="form-control" type="text" name="wiper_blades_water_level_text" id="wiper_blades_water_level_text" value="{{ old('wiper_blades_water_level_text', $repair->wiper_blades_water_level_text) }}">
                                            @if($errors->has('wiper_blades_water_level_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('wiper_blades_water_level_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.wiper_blades_water_level_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('brake_clutch_oil_level') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="brake_clutch_oil_level" value="0">
                                                <input type="checkbox" name="brake_clutch_oil_level" id="brake_clutch_oil_level" value="1" {{ $repair->brake_clutch_oil_level || old('brake_clutch_oil_level', 0) === 1 ? 'checked' : '' }}>
                                                <label for="brake_clutch_oil_level" style="font-weight: 400">{{ trans('cruds.repair.fields.brake_clutch_oil_level') }}</label>
                                            </div>
                                            @if($errors->has('brake_clutch_oil_level'))
                                            <span class="help-block" role="alert">{{ $errors->first('brake_clutch_oil_level') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.brake_clutch_oil_level_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('brake_clutch_oil_level_text') ? 'has-error' : '' }}">
                                            <label for="brake_clutch_oil_level_text">{{ trans('cruds.repair.fields.brake_clutch_oil_level_text') }}</label>
                                            <input class="form-control" type="text" name="brake_clutch_oil_level_text" id="brake_clutch_oil_level_text" value="{{ old('brake_clutch_oil_level_text', $repair->brake_clutch_oil_level_text) }}">
                                            @if($errors->has('brake_clutch_oil_level_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('brake_clutch_oil_level_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.brake_clutch_oil_level_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('electrical_systems') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="electrical_systems" value="0">
                                                <input type="checkbox" name="electrical_systems" id="electrical_systems" value="1" {{ $repair->electrical_systems || old('electrical_systems', 0) === 1 ? 'checked' : '' }}>
                                                <label for="electrical_systems" style="font-weight: 400">{{ trans('cruds.repair.fields.electrical_systems') }}</label>
                                            </div>
                                            @if($errors->has('electrical_systems'))
                                            <span class="help-block" role="alert">{{ $errors->first('electrical_systems') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.electrical_systems_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('electrical_systems_text') ? 'has-error' : '' }}">
                                            <label for="electrical_systems_text">{{ trans('cruds.repair.fields.electrical_systems_text') }}</label>
                                            <input class="form-control" type="text" name="electrical_systems_text" id="electrical_systems_text" value="{{ old('electrical_systems_text', $repair->electrical_systems_text) }}">
                                            @if($errors->has('electrical_systems_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('electrical_systems_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.electrical_systems_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('engine_coolant_level') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="engine_coolant_level" value="0">
                                                <input type="checkbox" name="engine_coolant_level" id="engine_coolant_level" value="1" {{ $repair->engine_coolant_level || old('engine_coolant_level', 0) === 1 ? 'checked' : '' }}>
                                                <label for="engine_coolant_level" style="font-weight: 400">{{ trans('cruds.repair.fields.engine_coolant_level') }}</label>
                                            </div>
                                            @if($errors->has('engine_coolant_level'))
                                            <span class="help-block" role="alert">{{ $errors->first('engine_coolant_level') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.engine_coolant_level_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('engine_coolant_level_text') ? 'has-error' : '' }}">
                                            <label for="engine_coolant_level_text">{{ trans('cruds.repair.fields.engine_coolant_level_text') }}</label>
                                            <input class="form-control" type="text" name="engine_coolant_level_text" id="engine_coolant_level_text" value="{{ old('engine_coolant_level_text', $repair->engine_coolant_level_text) }}">
                                            @if($errors->has('engine_coolant_level_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('engine_coolant_level_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.engine_coolant_level_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('engine_oil_level') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="engine_oil_level" value="0">
                                                <input type="checkbox" name="engine_oil_level" id="engine_oil_level" value="1" {{ $repair->engine_oil_level || old('engine_oil_level', 0) === 1 ? 'checked' : '' }}>
                                                <label for="engine_oil_level" style="font-weight: 400">{{ trans('cruds.repair.fields.engine_oil_level') }}</label>
                                            </div>
                                            @if($errors->has('engine_oil_level'))
                                            <span class="help-block" role="alert">{{ $errors->first('engine_oil_level') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.engine_oil_level_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('engine_oil_level_text') ? 'has-error' : '' }}">
                                            <label for="engine_oil_level_text">{{ trans('cruds.repair.fields.engine_oil_level_text') }}</label>
                                            <input class="form-control" type="text" name="engine_oil_level_text" id="engine_oil_level_text" value="{{ old('engine_oil_level_text', $repair->engine_oil_level_text) }}">
                                            @if($errors->has('engine_oil_level_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('engine_oil_level_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.engine_oil_level_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('filters_air_cabin_oil_fuel') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="filters_air_cabin_oil_fuel" value="0">
                                                <input type="checkbox" name="filters_air_cabin_oil_fuel" id="filters_air_cabin_oil_fuel" value="1" {{ $repair->filters_air_cabin_oil_fuel || old('filters_air_cabin_oil_fuel', 0) === 1 ? 'checked' : '' }}>
                                                <label for="filters_air_cabin_oil_fuel" style="font-weight: 400">{{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel') }}</label>
                                            </div>
                                            @if($errors->has('filters_air_cabin_oil_fuel'))
                                            <span class="help-block" role="alert">{{ $errors->first('filters_air_cabin_oil_fuel') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('filters_air_cabin_oil_fuel_text') ? 'has-error' : '' }}">
                                            <label for="filters_air_cabin_oil_fuel_text">{{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel_text') }}</label>
                                            <input class="form-control" type="text" name="filters_air_cabin_oil_fuel_text" id="filters_air_cabin_oil_fuel_text" value="{{ old('filters_air_cabin_oil_fuel_text', $repair->filters_air_cabin_oil_fuel_text) }}">
                                            @if($errors->has('filters_air_cabin_oil_fuel_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('filters_air_cabin_oil_fuel_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.filters_air_cabin_oil_fuel_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_leaks_engine_gearbox_steering') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_leaks_engine_gearbox_steering" value="0">
                                                <input type="checkbox" name="check_leaks_engine_gearbox_steering" id="check_leaks_engine_gearbox_steering" value="1" {{ $repair->check_leaks_engine_gearbox_steering || old('check_leaks_engine_gearbox_steering', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_leaks_engine_gearbox_steering" style="font-weight: 400">{{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering') }}</label>
                                            </div>
                                            @if($errors->has('check_leaks_engine_gearbox_steering'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_leaks_engine_gearbox_steering') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_leaks_engine_gearbox_steering_text') ? 'has-error' : '' }}">
                                            <label for="check_leaks_engine_gearbox_steering_text">{{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering_text') }}</label>
                                            <input class="form-control" type="text" name="check_leaks_engine_gearbox_steering_text" id="check_leaks_engine_gearbox_steering_text" value="{{ old('check_leaks_engine_gearbox_steering_text', $repair->check_leaks_engine_gearbox_steering_text) }}">
                                            @if($errors->has('check_leaks_engine_gearbox_steering_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_leaks_engine_gearbox_steering_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_leaks_engine_gearbox_steering_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('brake_pads_disks') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="brake_pads_disks" value="0">
                                                <input type="checkbox" name="brake_pads_disks" id="brake_pads_disks" value="1" {{ $repair->brake_pads_disks || old('brake_pads_disks', 0) === 1 ? 'checked' : '' }}>
                                                <label for="brake_pads_disks" style="font-weight: 400">{{ trans('cruds.repair.fields.brake_pads_disks') }}</label>
                                            </div>
                                            @if($errors->has('brake_pads_disks'))
                                            <span class="help-block" role="alert">{{ $errors->first('brake_pads_disks') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.brake_pads_disks_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('brake_pads_disks_text') ? 'has-error' : '' }}">
                                            <label for="brake_pads_disks_text">{{ trans('cruds.repair.fields.brake_pads_disks_text') }}</label>
                                            <input class="form-control" type="text" name="brake_pads_disks_text" id="brake_pads_disks_text" value="{{ old('brake_pads_disks_text', $repair->brake_pads_disks_text) }}">
                                            @if($errors->has('brake_pads_disks_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('brake_pads_disks_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.brake_pads_disks_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('shock_absorbers') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="shock_absorbers" value="0">
                                                <input type="checkbox" name="shock_absorbers" id="shock_absorbers" value="1" {{ $repair->shock_absorbers || old('shock_absorbers', 0) === 1 ? 'checked' : '' }}>
                                                <label for="shock_absorbers" style="font-weight: 400">{{ trans('cruds.repair.fields.shock_absorbers') }}</label>
                                            </div>
                                            @if($errors->has('shock_absorbers'))
                                            <span class="help-block" role="alert">{{ $errors->first('shock_absorbers') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.shock_absorbers_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('shock_absorbers_text') ? 'has-error' : '' }}">
                                            <label for="shock_absorbers_text">{{ trans('cruds.repair.fields.shock_absorbers_text') }}</label>
                                            <input class="form-control" type="text" name="shock_absorbers_text" id="shock_absorbers_text" value="{{ old('shock_absorbers_text', $repair->shock_absorbers_text) }}">
                                            @if($errors->has('shock_absorbers_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('shock_absorbers_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.shock_absorbers_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('tire_condition') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="tire_condition" value="0">
                                                <input type="checkbox" name="tire_condition" id="tire_condition" value="1" {{ $repair->tire_condition || old('tire_condition', 0) === 1 ? 'checked' : '' }}>
                                                <label for="tire_condition" style="font-weight: 400">{{ trans('cruds.repair.fields.tire_condition') }}</label>
                                            </div>
                                            @if($errors->has('tire_condition'))
                                            <span class="help-block" role="alert">{{ $errors->first('tire_condition') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.tire_condition_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('tire_condition_text') ? 'has-error' : '' }}">
                                            <label for="tire_condition_text">{{ trans('cruds.repair.fields.tire_condition_text') }}</label>
                                            <input class="form-control" type="text" name="tire_condition_text" id="tire_condition_text" value="{{ old('tire_condition_text', $repair->tire_condition_text) }}">
                                            @if($errors->has('tire_condition_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('tire_condition_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.tire_condition_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('battery') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="battery" value="0">
                                                <input type="checkbox" name="battery" id="battery" value="1" {{ $repair->battery || old('battery', 0) === 1 ? 'checked' : '' }}>
                                                <label for="battery" style="font-weight: 400">{{ trans('cruds.repair.fields.battery') }}</label>
                                            </div>
                                            @if($errors->has('battery'))
                                            <span class="help-block" role="alert">{{ $errors->first('battery') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.battery_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('battery_text') ? 'has-error' : '' }}">
                                            <label for="battery_text">{{ trans('cruds.repair.fields.battery_text') }}</label>
                                            <input class="form-control" type="text" name="battery_text" id="battery_text" value="{{ old('battery_text', $repair->battery_text) }}">
                                            @if($errors->has('battery_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('battery_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.battery_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('spare_tire_vest_triangle_tools') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="spare_tire_vest_triangle_tools" value="0">
                                                <input type="checkbox" name="spare_tire_vest_triangle_tools" id="spare_tire_vest_triangle_tools" value="1" {{ $repair->spare_tire_vest_triangle_tools || old('spare_tire_vest_triangle_tools', 0) === 1 ? 'checked' : '' }}>
                                                <label for="spare_tire_vest_triangle_tools" style="font-weight: 400">{{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools') }}</label>
                                            </div>
                                            @if($errors->has('spare_tire_vest_triangle_tools'))
                                            <span class="help-block" role="alert">{{ $errors->first('spare_tire_vest_triangle_tools') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('spare_tire_vest_triangle_tools_text') ? 'has-error' : '' }}">
                                            <label for="spare_tire_vest_triangle_tools_text">{{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools_text') }}</label>
                                            <input class="form-control" type="text" name="spare_tire_vest_triangle_tools_text" id="spare_tire_vest_triangle_tools_text" value="{{ old('spare_tire_vest_triangle_tools_text', $repair->spare_tire_vest_triangle_tools_text) }}">
                                            @if($errors->has('spare_tire_vest_triangle_tools_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('spare_tire_vest_triangle_tools_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.spare_tire_vest_triangle_tools_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_clearance') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_clearance" value="0">
                                                <input type="checkbox" name="check_clearance" id="check_clearance" value="1" {{ $repair->check_clearance || old('check_clearance', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_clearance" style="font-weight: 400">{{ trans('cruds.repair.fields.check_clearance') }}</label>
                                            </div>
                                            @if($errors->has('check_clearance'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_clearance') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_clearance_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_clearance_text') ? 'has-error' : '' }}">
                                            <label for="check_clearance_text">{{ trans('cruds.repair.fields.check_clearance_text') }}</label>
                                            <input class="form-control" type="text" name="check_clearance_text" id="check_clearance_text" value="{{ old('check_clearance_text', $repair->check_clearance_text) }}">
                                            @if($errors->has('check_clearance_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_clearance_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_clearance_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_shields') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_shields" value="0">
                                                <input type="checkbox" name="check_shields" id="check_shields" value="1" {{ $repair->check_shields || old('check_shields', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_shields" style="font-weight: 400">{{ trans('cruds.repair.fields.check_shields') }}</label>
                                            </div>
                                            @if($errors->has('check_shields'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_shields') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_shields_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_shields_text') ? 'has-error' : '' }}">
                                            <label for="check_shields_text">{{ trans('cruds.repair.fields.check_shields_text') }}</label>
                                            <input class="form-control" type="text" name="check_shields_text" id="check_shields_text" value="{{ old('check_shields_text', $repair->check_shields_text) }}">
                                            @if($errors->has('check_shields_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_shields_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_shields_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('paint_condition') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="paint_condition" value="0">
                                                <input type="checkbox" name="paint_condition" id="paint_condition" value="1" {{ $repair->paint_condition || old('paint_condition', 0) === 1 ? 'checked' : '' }}>
                                                <label for="paint_condition" style="font-weight: 400">{{ trans('cruds.repair.fields.paint_condition') }}</label>
                                            </div>
                                            @if($errors->has('paint_condition'))
                                            <span class="help-block" role="alert">{{ $errors->first('paint_condition') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.paint_condition_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('paint_condition_text') ? 'has-error' : '' }}">
                                            <label for="paint_condition_text">{{ trans('cruds.repair.fields.paint_condition_text') }}</label>
                                            <input class="form-control" type="text" name="paint_condition_text" id="paint_condition_text" value="{{ old('paint_condition_text', $repair->paint_condition_text) }}">
                                            @if($errors->has('paint_condition_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('paint_condition_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.paint_condition_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('dents') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="dents" value="0">
                                                <input type="checkbox" name="dents" id="dents" value="1" {{ $repair->dents || old('dents', 0) === 1 ? 'checked' : '' }}>
                                                <label for="dents" style="font-weight: 400">{{ trans('cruds.repair.fields.dents') }}</label>
                                            </div>
                                            @if($errors->has('dents'))
                                            <span class="help-block" role="alert">{{ $errors->first('dents') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.dents_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('dents_text') ? 'has-error' : '' }}">
                                            <label for="dents_text">{{ trans('cruds.repair.fields.dents_text') }}</label>
                                            <input class="form-control" type="text" name="dents_text" id="dents_text" value="{{ old('dents_text', $repair->dents_text) }}">
                                            @if($errors->has('dents_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('dents_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.dents_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('diverse_strips') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="diverse_strips" value="0">
                                                <input type="checkbox" name="diverse_strips" id="diverse_strips" value="1" {{ $repair->diverse_strips || old('diverse_strips', 0) === 1 ? 'checked' : '' }}>
                                                <label for="diverse_strips" style="font-weight: 400">{{ trans('cruds.repair.fields.diverse_strips') }}</label>
                                            </div>
                                            @if($errors->has('diverse_strips'))
                                            <span class="help-block" role="alert">{{ $errors->first('diverse_strips') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.diverse_strips_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('diverse_strips_text') ? 'has-error' : '' }}">
                                            <label for="diverse_strips_text">{{ trans('cruds.repair.fields.diverse_strips_text') }}</label>
                                            <input class="form-control" type="text" name="diverse_strips_text" id="diverse_strips_text" value="{{ old('diverse_strips_text', $repair->diverse_strips_text) }}">
                                            @if($errors->has('diverse_strips_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('diverse_strips_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.diverse_strips_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('diverse_plastics_check_scratches') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="diverse_plastics_check_scratches" value="0">
                                                <input type="checkbox" name="diverse_plastics_check_scratches" id="diverse_plastics_check_scratches" value="1" {{ $repair->diverse_plastics_check_scratches || old('diverse_plastics_check_scratches', 0) === 1 ? 'checked' : '' }}>
                                                <label for="diverse_plastics_check_scratches" style="font-weight: 400">{{ trans('cruds.repair.fields.diverse_plastics_check_scratches') }}</label>
                                            </div>
                                            @if($errors->has('diverse_plastics_check_scratches'))
                                            <span class="help-block" role="alert">{{ $errors->first('diverse_plastics_check_scratches') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.diverse_plastics_check_scratches_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('diverse_plastics_check_scratches_text') ? 'has-error' : '' }}">
                                            <label for="diverse_plastics_check_scratches_text">{{ trans('cruds.repair.fields.diverse_plastics_check_scratches_text') }}</label>
                                            <input class="form-control" type="text" name="diverse_plastics_check_scratches_text" id="diverse_plastics_check_scratches_text" value="{{ old('diverse_plastics_check_scratches_text', $repair->diverse_plastics_check_scratches_text) }}">
                                            @if($errors->has('diverse_plastics_check_scratches_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('diverse_plastics_check_scratches_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.diverse_plastics_check_scratches_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('wheels') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="wheels" value="0">
                                                <input type="checkbox" name="wheels" id="wheels" value="1" {{ $repair->wheels || old('wheels', 0) === 1 ? 'checked' : '' }}>
                                                <label for="wheels" style="font-weight: 400">{{ trans('cruds.repair.fields.wheels') }}</label>
                                            </div>
                                            @if($errors->has('wheels'))
                                            <span class="help-block" role="alert">{{ $errors->first('wheels') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.wheels_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('wheels_text') ? 'has-error' : '' }}">
                                            <label for="wheels_text">{{ trans('cruds.repair.fields.wheels_text') }}</label>
                                            <input class="form-control" type="text" name="wheels_text" id="wheels_text" value="{{ old('wheels_text', $repair->wheels_text) }}">
                                            @if($errors->has('wheels_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('wheels_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.wheels_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('bolts_paint') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="bolts_paint" value="0">
                                                <input type="checkbox" name="bolts_paint" id="bolts_paint" value="1" {{ $repair->bolts_paint || old('bolts_paint', 0) === 1 ? 'checked' : '' }}>
                                                <label for="bolts_paint" style="font-weight: 400">{{ trans('cruds.repair.fields.bolts_paint') }}</label>
                                            </div>
                                            @if($errors->has('bolts_paint'))
                                            <span class="help-block" role="alert">{{ $errors->first('bolts_paint') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.bolts_paint_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('bolts_paint_text') ? 'has-error' : '' }}">
                                            <label for="bolts_paint_text">{{ trans('cruds.repair.fields.bolts_paint_text') }}</label>
                                            <input class="form-control" type="text" name="bolts_paint_text" id="bolts_paint_text" value="{{ old('bolts_paint_text', $repair->bolts_paint_text) }}">
                                            @if($errors->has('bolts_paint_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('bolts_paint_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.bolts_paint_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('seat_belts') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="seat_belts" value="0">
                                                <input type="checkbox" name="seat_belts" id="seat_belts" value="1" {{ $repair->seat_belts || old('seat_belts', 0) === 1 ? 'checked' : '' }}>
                                                <label for="seat_belts" style="font-weight: 400">{{ trans('cruds.repair.fields.seat_belts') }}</label>
                                            </div>
                                            @if($errors->has('seat_belts'))
                                            <span class="help-block" role="alert">{{ $errors->first('seat_belts') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.seat_belts_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('seat_belts_text') ? 'has-error' : '' }}">
                                            <label for="seat_belts_text">{{ trans('cruds.repair.fields.seat_belts_text') }}</label>
                                            <input class="form-control" type="text" name="seat_belts_text" id="seat_belts_text" value="{{ old('seat_belts_text', $repair->seat_belts_text) }}">
                                            @if($errors->has('seat_belts_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('seat_belts_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.seat_belts_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('radio') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="radio" value="0">
                                                <input type="checkbox" name="radio" id="radio" value="1" {{ $repair->radio || old('radio', 0) === 1 ? 'checked' : '' }}>
                                                <label for="radio" style="font-weight: 400">{{ trans('cruds.repair.fields.radio') }}</label>
                                            </div>
                                            @if($errors->has('radio'))
                                            <span class="help-block" role="alert">{{ $errors->first('radio') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.radio_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('radio_text') ? 'has-error' : '' }}">
                                            <label for="radio_text">{{ trans('cruds.repair.fields.radio_text') }}</label>
                                            <input class="form-control" type="text" name="radio_text" id="radio_text" value="{{ old('radio_text', $repair->radio_text) }}">
                                            @if($errors->has('radio_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('radio_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.radio_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('air_conditioning') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="air_conditioning" value="0">
                                                <input type="checkbox" name="air_conditioning" id="air_conditioning" value="1" {{ $repair->air_conditioning || old('air_conditioning', 0) === 1 ? 'checked' : '' }}>
                                                <label for="air_conditioning" style="font-weight: 400">{{ trans('cruds.repair.fields.air_conditioning') }}</label>
                                            </div>
                                            @if($errors->has('air_conditioning'))
                                            <span class="help-block" role="alert">{{ $errors->first('air_conditioning') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.air_conditioning_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('air_conditioning_text') ? 'has-error' : '' }}">
                                            <label for="air_conditioning_text">{{ trans('cruds.repair.fields.air_conditioning_text') }}</label>
                                            <input class="form-control" type="text" name="air_conditioning_text" id="air_conditioning_text" value="{{ old('air_conditioning_text', $repair->air_conditioning_text) }}">
                                            @if($errors->has('air_conditioning_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('air_conditioning_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.air_conditioning_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('front_rear_window_functionality') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="front_rear_window_functionality" value="0">
                                                <input type="checkbox" name="front_rear_window_functionality" id="front_rear_window_functionality" value="1" {{ $repair->front_rear_window_functionality || old('front_rear_window_functionality', 0) === 1 ? 'checked' : '' }}>
                                                <label for="front_rear_window_functionality" style="font-weight: 400">{{ trans('cruds.repair.fields.front_rear_window_functionality') }}</label>
                                            </div>
                                            @if($errors->has('front_rear_window_functionality'))
                                            <span class="help-block" role="alert">{{ $errors->first('front_rear_window_functionality') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.front_rear_window_functionality_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('front_rear_window_functionality_text') ? 'has-error' : '' }}">
                                            <label for="front_rear_window_functionality_text">{{ trans('cruds.repair.fields.front_rear_window_functionality_text') }}</label>
                                            <input class="form-control" type="text" name="front_rear_window_functionality_text" id="front_rear_window_functionality_text" value="{{ old('front_rear_window_functionality_text', $repair->front_rear_window_functionality_text) }}">
                                            @if($errors->has('front_rear_window_functionality_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('front_rear_window_functionality_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.front_rear_window_functionality_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('seats_upholstery') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="seats_upholstery" value="0">
                                                <input type="checkbox" name="seats_upholstery" id="seats_upholstery" value="1" {{ $repair->seats_upholstery || old('seats_upholstery', 0) === 1 ? 'checked' : '' }}>
                                                <label for="seats_upholstery" style="font-weight: 400">{{ trans('cruds.repair.fields.seats_upholstery') }}</label>
                                            </div>
                                            @if($errors->has('seats_upholstery'))
                                            <span class="help-block" role="alert">{{ $errors->first('seats_upholstery') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.seats_upholstery_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('seats_upholstery_text') ? 'has-error' : '' }}">
                                            <label for="seats_upholstery_text">{{ trans('cruds.repair.fields.seats_upholstery_text') }}</label>
                                            <input class="form-control" type="text" name="seats_upholstery_text" id="seats_upholstery_text" value="{{ old('seats_upholstery_text', $repair->seats_upholstery_text) }}">
                                            @if($errors->has('seats_upholstery_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('seats_upholstery_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.seats_upholstery_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('sun_visors') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="sun_visors" value="0">
                                                <input type="checkbox" name="sun_visors" id="sun_visors" value="1" {{ $repair->sun_visors || old('sun_visors', 0) === 1 ? 'checked' : '' }}>
                                                <label for="sun_visors" style="font-weight: 400">{{ trans('cruds.repair.fields.sun_visors') }}</label>
                                            </div>
                                            @if($errors->has('sun_visors'))
                                            <span class="help-block" role="alert">{{ $errors->first('sun_visors') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.sun_visors_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('sun_visors_text') ? 'has-error' : '' }}">
                                            <label for="sun_visors_text">{{ trans('cruds.repair.fields.sun_visors_text') }}</label>
                                            <input class="form-control" type="text" name="sun_visors_text" id="sun_visors_text" value="{{ old('sun_visors_text', $repair->sun_visors_text) }}">
                                            @if($errors->has('sun_visors_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('sun_visors_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.sun_visors_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('carpets') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="carpets" value="0">
                                                <input type="checkbox" name="carpets" id="carpets" value="1" {{ $repair->carpets || old('carpets', 0) === 1 ? 'checked' : '' }}>
                                                <label for="carpets" style="font-weight: 400">{{ trans('cruds.repair.fields.carpets') }}</label>
                                            </div>
                                            @if($errors->has('carpets'))
                                            <span class="help-block" role="alert">{{ $errors->first('carpets') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.carpets_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('carpets_text') ? 'has-error' : '' }}">
                                            <label for="carpets_text">{{ trans('cruds.repair.fields.carpets_text') }}</label>
                                            <input class="form-control" type="text" name="carpets_text" id="carpets_text" value="{{ old('carpets_text', $repair->carpets_text) }}">
                                            @if($errors->has('carpets_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('carpets_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.carpets_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('trunk_shelf') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="trunk_shelf" value="0">
                                                <input type="checkbox" name="trunk_shelf" id="trunk_shelf" value="1" {{ $repair->trunk_shelf || old('trunk_shelf', 0) === 1 ? 'checked' : '' }}>
                                                <label for="trunk_shelf" style="font-weight: 400">{{ trans('cruds.repair.fields.trunk_shelf') }}</label>
                                            </div>
                                            @if($errors->has('trunk_shelf'))
                                            <span class="help-block" role="alert">{{ $errors->first('trunk_shelf') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.trunk_shelf_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('trunk_shelf_text') ? 'has-error' : '' }}">
                                            <label for="trunk_shelf_text">{{ trans('cruds.repair.fields.trunk_shelf_text') }}</label>
                                            <input class="form-control" type="text" name="trunk_shelf_text" id="trunk_shelf_text" value="{{ old('trunk_shelf_text', $repair->trunk_shelf_text) }}">
                                            @if($errors->has('trunk_shelf_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('trunk_shelf_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.trunk_shelf_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('buttons') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="buttons" value="0">
                                                <input type="checkbox" name="buttons" id="buttons" value="1" {{ $repair->buttons || old('buttons', 0) === 1 ? 'checked' : '' }}>
                                                <label for="buttons" style="font-weight: 400">{{ trans('cruds.repair.fields.buttons') }}</label>
                                            </div>
                                            @if($errors->has('buttons'))
                                            <span class="help-block" role="alert">{{ $errors->first('buttons') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.buttons_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('buttons_text') ? 'has-error' : '' }}">
                                            <label for="buttons_text">{{ trans('cruds.repair.fields.buttons_text') }}</label>
                                            <input class="form-control" type="text" name="buttons_text" id="buttons_text" value="{{ old('buttons_text', $repair->buttons_text) }}">
                                            @if($errors->has('buttons_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('buttons_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.buttons_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('door_panels') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="door_panels" value="0">
                                                <input type="checkbox" name="door_panels" id="door_panels" value="1" {{ $repair->door_panels || old('door_panels', 0) === 1 ? 'checked' : '' }}>
                                                <label for="door_panels" style="font-weight: 400">{{ trans('cruds.repair.fields.door_panels') }}</label>
                                            </div>
                                            @if($errors->has('door_panels'))
                                            <span class="help-block" role="alert">{{ $errors->first('door_panels') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.door_panels_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('door_panels_text') ? 'has-error' : '' }}">
                                            <label for="door_panels_text">{{ trans('cruds.repair.fields.door_panels_text') }}</label>
                                            <input class="form-control" type="text" name="door_panels_text" id="door_panels_text" value="{{ old('door_panels_text', $repair->door_panels_text) }}">
                                            @if($errors->has('door_panels_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('door_panels_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.door_panels_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('locks') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="locks" value="0">
                                                <input type="checkbox" name="locks" id="locks" value="1" {{ $repair->locks || old('locks', 0) === 1 ? 'checked' : '' }}>
                                                <label for="locks" style="font-weight: 400">{{ trans('cruds.repair.fields.locks') }}</label>
                                            </div>
                                            @if($errors->has('locks'))
                                            <span class="help-block" role="alert">{{ $errors->first('locks') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.locks_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('locks_text') ? 'has-error' : '' }}">
                                            <label for="locks_text">{{ trans('cruds.repair.fields.locks_text') }}</label>
                                            <input class="form-control" type="text" name="locks_text" id="locks_text" value="{{ old('locks_text', $repair->locks_text) }}">
                                            @if($errors->has('locks_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('locks_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.locks_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('interior_covers_headlights_taillights') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="interior_covers_headlights_taillights" value="0">
                                                <input type="checkbox" name="interior_covers_headlights_taillights" id="interior_covers_headlights_taillights" value="1" {{ $repair->interior_covers_headlights_taillights || old('interior_covers_headlights_taillights', 0) === 1 ? 'checked' : '' }}>
                                                <label for="interior_covers_headlights_taillights" style="font-weight: 400">{{ trans('cruds.repair.fields.interior_covers_headlights_taillights') }}</label>
                                            </div>
                                            @if($errors->has('interior_covers_headlights_taillights'))
                                            <span class="help-block" role="alert">{{ $errors->first('interior_covers_headlights_taillights') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.interior_covers_headlights_taillights_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('interior_covers_headlights_taillights_text') ? 'has-error' : '' }}">
                                            <label for="interior_covers_headlights_taillights_text">{{ trans('cruds.repair.fields.interior_covers_headlights_taillights_text') }}</label>
                                            <input class="form-control" type="text" name="interior_covers_headlights_taillights_text" id="interior_covers_headlights_taillights_text" value="{{ old('interior_covers_headlights_taillights_text', $repair->interior_covers_headlights_taillights_text) }}">
                                            @if($errors->has('interior_covers_headlights_taillights_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('interior_covers_headlights_taillights_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.interior_covers_headlights_taillights_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('open_close_doors_remote_control_all_functions') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="open_close_doors_remote_control_all_functions" value="0">
                                                <input type="checkbox" name="open_close_doors_remote_control_all_functions" id="open_close_doors_remote_control_all_functions" value="1" {{ $repair->open_close_doors_remote_control_all_functions || old('open_close_doors_remote_control_all_functions', 0) === 1 ? 'checked' : '' }}>
                                                <label for="open_close_doors_remote_control_all_functions" style="font-weight: 400">{{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions') }}</label>
                                            </div>
                                            @if($errors->has('open_close_doors_remote_control_all_functions'))
                                            <span class="help-block" role="alert">{{ $errors->first('open_close_doors_remote_control_all_functions') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('open_close_doors_remote_control_all_functions_text') ? 'has-error' : '' }}">
                                            <label for="open_close_doors_remote_control_all_functions_text">{{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions_text') }}</label>
                                            <input class="form-control" type="text" name="open_close_doors_remote_control_all_functions_text" id="open_close_doors_remote_control_all_functions_text" value="{{ old('open_close_doors_remote_control_all_functions_text', $repair->open_close_doors_remote_control_all_functions_text) }}">
                                            @if($errors->has('open_close_doors_remote_control_all_functions_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('open_close_doors_remote_control_all_functions_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.open_close_doors_remote_control_all_functions_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('turn_on_ac_check_glass') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="turn_on_ac_check_glass" value="0">
                                                <input type="checkbox" name="turn_on_ac_check_glass" id="turn_on_ac_check_glass" value="1" {{ $repair->turn_on_ac_check_glass || old('turn_on_ac_check_glass', 0) === 1 ? 'checked' : '' }}>
                                                <label for="turn_on_ac_check_glass" style="font-weight: 400">{{ trans('cruds.repair.fields.turn_on_ac_check_glass') }}</label>
                                            </div>
                                            @if($errors->has('turn_on_ac_check_glass'))
                                            <span class="help-block" role="alert">{{ $errors->first('turn_on_ac_check_glass') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.turn_on_ac_check_glass_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('turn_on_ac_check_glass_text') ? 'has-error' : '' }}">
                                            <label for="turn_on_ac_check_glass_text">{{ trans('cruds.repair.fields.turn_on_ac_check_glass_text') }}</label>
                                            <input class="form-control" type="text" name="turn_on_ac_check_glass_text" id="turn_on_ac_check_glass_text" value="{{ old('turn_on_ac_check_glass_text', $repair->turn_on_ac_check_glass_text) }}">
                                            @if($errors->has('turn_on_ac_check_glass_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('turn_on_ac_check_glass_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.turn_on_ac_check_glass_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_engine_lift_hood') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_engine_lift_hood" value="0">
                                                <input type="checkbox" name="check_engine_lift_hood" id="check_engine_lift_hood" value="1" {{ $repair->check_engine_lift_hood || old('check_engine_lift_hood', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_engine_lift_hood" style="font-weight: 400">{{ trans('cruds.repair.fields.check_engine_lift_hood') }}</label>
                                            </div>
                                            @if($errors->has('check_engine_lift_hood'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_engine_lift_hood') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_engine_lift_hood_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_engine_lift_hood_text') ? 'has-error' : '' }}">
                                            <label for="check_engine_lift_hood_text">{{ trans('cruds.repair.fields.check_engine_lift_hood_text') }}</label>
                                            <input class="form-control" type="text" name="check_engine_lift_hood_text" id="check_engine_lift_hood_text" value="{{ old('check_engine_lift_hood_text', $repair->check_engine_lift_hood_text) }}">
                                            @if($errors->has('check_engine_lift_hood_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_engine_lift_hood_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_engine_lift_hood_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('connect_vehicle_to_scanner_check_errors') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="connect_vehicle_to_scanner_check_errors" value="0">
                                                <input type="checkbox" name="connect_vehicle_to_scanner_check_errors" id="connect_vehicle_to_scanner_check_errors" value="1" {{ $repair->connect_vehicle_to_scanner_check_errors || old('connect_vehicle_to_scanner_check_errors', 0) === 1 ? 'checked' : '' }}>
                                                <label for="connect_vehicle_to_scanner_check_errors" style="font-weight: 400">{{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors') }}</label>
                                            </div>
                                            @if($errors->has('connect_vehicle_to_scanner_check_errors'))
                                            <span class="help-block" role="alert">{{ $errors->first('connect_vehicle_to_scanner_check_errors') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('connect_vehicle_to_scanner_check_errors_text') ? 'has-error' : '' }}">
                                            <label for="connect_vehicle_to_scanner_check_errors_text">{{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors_text') }}</label>
                                            <input class="form-control" type="text" name="connect_vehicle_to_scanner_check_errors_text" id="connect_vehicle_to_scanner_check_errors_text" value="{{ old('connect_vehicle_to_scanner_check_errors_text', $repair->connect_vehicle_to_scanner_check_errors_text) }}">
                                            @if($errors->has('connect_vehicle_to_scanner_check_errors_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('connect_vehicle_to_scanner_check_errors_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.connect_vehicle_to_scanner_check_errors_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_chassis_confirm_with_registration') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_chassis_confirm_with_registration" value="0">
                                                <input type="checkbox" name="check_chassis_confirm_with_registration" id="check_chassis_confirm_with_registration" value="1" {{ $repair->check_chassis_confirm_with_registration || old('check_chassis_confirm_with_registration', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_chassis_confirm_with_registration" style="font-weight: 400">{{ trans('cruds.repair.fields.check_chassis_confirm_with_registration') }}</label>
                                            </div>
                                            @if($errors->has('check_chassis_confirm_with_registration'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_chassis_confirm_with_registration') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_chassis_confirm_with_registration_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_chassis_confirm_with_registration_text') ? 'has-error' : '' }}">
                                            <label for="check_chassis_confirm_with_registration_text">{{ trans('cruds.repair.fields.check_chassis_confirm_with_registration_text') }}</label>
                                            <input class="form-control" type="text" name="check_chassis_confirm_with_registration_text" id="check_chassis_confirm_with_registration_text" value="{{ old('check_chassis_confirm_with_registration_text', $repair->check_chassis_confirm_with_registration_text) }}">
                                            @if($errors->has('check_chassis_confirm_with_registration_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_chassis_confirm_with_registration_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_chassis_confirm_with_registration_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('manufacturer_plate') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="manufacturer_plate" value="0">
                                                <input type="checkbox" name="manufacturer_plate" id="manufacturer_plate" value="1" {{ $repair->manufacturer_plate || old('manufacturer_plate', 0) === 1 ? 'checked' : '' }}>
                                                <label for="manufacturer_plate" style="font-weight: 400">{{ trans('cruds.repair.fields.manufacturer_plate') }}</label>
                                            </div>
                                            @if($errors->has('manufacturer_plate'))
                                            <span class="help-block" role="alert">{{ $errors->first('manufacturer_plate') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.manufacturer_plate_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('manufacturer_plate_text') ? 'has-error' : '' }}">
                                            <label for="manufacturer_plate_text">{{ trans('cruds.repair.fields.manufacturer_plate_text') }}</label>
                                            <input class="form-control" type="text" name="manufacturer_plate_text" id="manufacturer_plate_text" value="{{ old('manufacturer_plate_text', $repair->manufacturer_plate_text) }}">
                                            @if($errors->has('manufacturer_plate_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('manufacturer_plate_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.manufacturer_plate_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_chassis_stickers') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_chassis_stickers" value="0">
                                                <input type="checkbox" name="check_chassis_stickers" id="check_chassis_stickers" value="1" {{ $repair->check_chassis_stickers || old('check_chassis_stickers', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_chassis_stickers" style="font-weight: 400">{{ trans('cruds.repair.fields.check_chassis_stickers') }}</label>
                                            </div>
                                            @if($errors->has('check_chassis_stickers'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_chassis_stickers') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_chassis_stickers_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_chassis_stickers_text') ? 'has-error' : '' }}">
                                            <label for="check_chassis_stickers_text">{{ trans('cruds.repair.fields.check_chassis_stickers_text') }}</label>
                                            <input class="form-control" type="text" name="check_chassis_stickers_text" id="check_chassis_stickers_text" value="{{ old('check_chassis_stickers_text', $repair->check_chassis_stickers_text) }}">
                                            @if($errors->has('check_chassis_stickers_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_chassis_stickers_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_chassis_stickers_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="form-group {{ $errors->has('check_gearbox_oil') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="check_gearbox_oil" value="0">
                                                <input type="checkbox" name="check_gearbox_oil" id="check_gearbox_oil" value="1" {{ $repair->check_gearbox_oil || old('check_gearbox_oil', 0) === 1 ? 'checked' : '' }}>
                                                <label for="check_gearbox_oil" style="font-weight: 400">{{ trans('cruds.repair.fields.check_gearbox_oil') }}</label>
                                            </div>
                                            @if($errors->has('check_gearbox_oil'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_gearbox_oil') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_gearbox_oil_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('check_gearbox_oil_text') ? 'has-error' : '' }}">
                                            <label for="check_gearbox_oil_text">{{ trans('cruds.repair.fields.check_gearbox_oil_text') }}</label>
                                            <input class="form-control" type="text" name="check_gearbox_oil_text" id="check_gearbox_oil_text" value="{{ old('check_gearbox_oil_text', $repair->check_gearbox_oil_text) }}">
                                            @if($errors->has('check_gearbox_oil_text'))
                                            <span class="help-block" role="alert">{{ $errors->first('check_gearbox_oil_text') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.check_gearbox_oil_text_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group {{ $errors->has('obs_2') ? 'has-error' : '' }}">
                                            <label for="obs_2">{{ trans('cruds.repair.fields.obs_2') }}</label>
                                            <textarea class="form-control" name="obs_2" id="obs_2">{{ old('obs_2', $repair->obs_2) }}</textarea>
                                            @if($errors->has('obs_2'))
                                            <span class="help-block" role="alert">{{ $errors->first('obs_2') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.obs_2_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('checkout') ? 'has-error' : '' }}">
                                            <label for="checkout">{{ trans('cruds.repair.fields.checkout') }}</label>
                                            <div class="needsclick dropzone" id="checkout-dropzone">
                                            </div>
                                            @if($errors->has('checkout'))
                                            <span class="help-block" role="alert">{{ $errors->first('checkout') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.checkout_helper') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group {{ $errors->has('expected_completion_date') ? 'has-error' : '' }}">
                                            <label for="expected_completion_date">{{ trans('cruds.repair.fields.expected_completion_date') }}</label>
                                            <input class="form-control date" type="text" name="expected_completion_date" id="expected_completion_date" value="{{ old('expected_completion_date', $repair->expected_completion_date) }}">
                                            @if($errors->has('expected_completion_date'))
                                            <span class="help-block" role="alert">{{ $errors->first('expected_completion_date') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.expected_completion_date_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('timestamp') ? 'has-error' : '' }}">
                                            <label for="timestamp">{{ trans('cruds.repair.fields.timestamp') }}</label>
                                            <input class="form-control datetime" type="text" name="timestamp" id="timestamp" value="{{ old('timestamp', $repair->timestamp) }}">
                                            @if($errors->has('timestamp'))
                                            <span class="help-block" role="alert">{{ $errors->first('timestamp') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.timestamp_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                                            <label for="name">{{ trans('cruds.repair.fields.name') }}</label>
                                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $repair->name) }}">
                                            @if($errors->has('name'))
                                            <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.name_helper') }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('repair_state') ? 'has-error' : '' }}">
                                            <label for="repair_state_id">{{ trans('cruds.repair.fields.repair_state') }}</label>
                                            <select class="form-control select2" name="repair_state_id" id="repair_state_id">
                                                @foreach($repair_states as $id => $entry)
                                                <option value="{{ $id }}" {{ (old('repair_state_id') ? old('repair_state_id') : optional($repair->repair_state)->id) == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                                @endforeach
                                            </select>
                                            @if($errors->has('repair_state'))
                                            <span class="help-block" role="alert">{{ $errors->first('repair_state') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.repair.fields.repair_state_helper') }}</span>
                                        </div>
                                        <div class="form-group">
                                            <button class="btn btn-lg btn-danger" type="submit">
                                                {{ trans('global.save') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    var uploadedCheckinMap = {}
Dropzone.options.checkinDropzone = {
    url: '{{ route('admin.repairs.storeMedia') }}',
    maxFilesize: 2, // MB
    acceptedFiles: '.jpeg,.jpg,.png,.gif',
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2,
      width: 4096,
      height: 4096
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="checkin[]" value="' + response.name + '">')
      uploadedCheckinMap[file.name] = response.name
    },
    removedfile: function (file) {
      console.log(file)
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedCheckinMap[file.name]
      }
      $('form').find('input[name="checkin[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($repair) && $repair->checkin)
      var files = {!! json_encode($repair->checkin) !!}
          for (var i in files) {
          var file = files[i]
          this.options.addedfile.call(this, file)
          this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
          file.previewElement.classList.add('dz-complete')
          $('form').append('<input type="hidden" name="checkin[]" value="' + file.file_name + '">')
        }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}

</script>
<script>
    var uploadedCheckoutMap = {}
Dropzone.options.checkoutDropzone = {
    url: '{{ route('admin.repairs.storeMedia') }}',
    maxFilesize: 5, // MB
    acceptedFiles: '.jpeg,.jpg,.png,.gif',
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5,
      width: 4096,
      height: 4096
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="checkout[]" value="' + response.name + '">')
      uploadedCheckoutMap[file.name] = response.name
    },
    removedfile: function (file) {
      console.log(file)
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedCheckoutMap[file.name]
      }
      $('form').find('input[name="checkout[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($repair) && $repair->checkout)
      var files = {!! json_encode($repair->checkout) !!}
          for (var i in files) {
          var file = files[i]
          this.options.addedfile.call(this, file)
          this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
          file.previewElement.classList.add('dz-complete')
          $('form').append('<input type="hidden" name="checkout[]" value="' + file.file_name + '">')
        }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}

</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const progressBar = document.getElementById('progress-bar');

        function updateProgress() {
            const total = checkboxes.length;
            const checked = Array.from(checkboxes).filter(cb => cb.checked).length;
            const percent = total > 0 ? Math.round((checked / total) * 100) : 0;

            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
            progressBar.textContent = percent + '%';
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateProgress));

        // Atualizar quando a pÃ¡gina carregar
        updateProgress();
    });
</script>

@endsection
@section('styles')
    <style>
        #progress-container {
            position: sticky;
            top: 0;
            z-index: 999;
            background-color: #f8f9fa;
            padding: 10px;
        }

        /* Garante que o sticky nÃ£o seja bloqueado por elementos pai */
        .wrapper {
            position: relative;
            overflow: visible !important;
        }

    </style>
@endsection

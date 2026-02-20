@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.repair.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.repairs.store") }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group {{ $errors->has('vehicle') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_id">{{ trans('cruds.repair.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ old('vehicle_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.repair.fields.vehicle_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('obs_1') ? 'has-error' : '' }}">
                            <label for="obs_1">{{ trans('cruds.repair.fields.obs_1') }}</label>
                            <textarea class="form-control" name="obs_1" id="obs_1">{{ old('obs_1') }}</textarea>
                            @if($errors->has('obs_1'))
                                <span class="help-block" role="alert">{{ $errors->first('obs_1') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.repair.fields.obs_1_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('work_performed') ? 'has-error' : '' }}">
                            <label for="work_performed">{{ trans('cruds.repair.fields.work_performed') }}</label>
                            <textarea class="form-control" name="work_performed" id="work_performed">{{ old('work_performed') }}</textarea>
                            @if($errors->has('work_performed'))
                                <span class="help-block" role="alert">{{ $errors->first('work_performed') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.repair.fields.work_performed_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('materials_used') ? 'has-error' : '' }}">
                            <label for="materials_used">{{ trans('cruds.repair.fields.materials_used') }}</label>
                            <textarea class="form-control" name="materials_used" id="materials_used">{{ old('materials_used') }}</textarea>
                            @if($errors->has('materials_used'))
                                <span class="help-block" role="alert">{{ $errors->first('materials_used') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.repair.fields.materials_used_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                            <label for="kilometers">{{ trans('cruds.repair.fields.kilometers') }}</label>
                            <input class="form-control" type="number" name="kilometers" id="kilometers" value="{{ old('kilometers', '') }}" step="1">
                            @if($errors->has('kilometers'))
                                <span class="help-block" role="alert">{{ $errors->first('kilometers') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.repair.fields.kilometers_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('repair_state') ? 'has-error' : '' }}">
                            <label for="repair_state_id">{{ trans('cruds.repair.fields.repair_state') }}</label>
                            <select class="form-control select2" name="repair_state_id" id="repair_state_id">
                                @foreach($repair_states as $id => $entry)
                                    <option value="{{ $id }}" {{ old('repair_state_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('repair_state'))
                                <span class="help-block" role="alert">{{ $errors->first('repair_state') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.repair.fields.repair_state_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

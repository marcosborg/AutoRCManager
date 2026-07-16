@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.vehicleConsignment.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.vehicle-consignments.store") }}">
                        @csrf
                        <div class="form-group {{ $errors->has('vehicle_id') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_id">{{ trans('cruds.vehicleConsignment.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ old('vehicle_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle_id'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle_id') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleConsignment.fields.vehicle_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('from_unit_id') ? 'has-error' : '' }}">
                            <label class="required" for="from_unit_id">{{ trans('cruds.vehicleConsignment.fields.from_unit') }}</label>
                            <select class="form-control select2" name="from_unit_id" id="from_unit_id" required>
                                @foreach($units as $id => $entry)
                                    <option value="{{ $id }}" {{ old('from_unit_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('from_unit_id'))
                                <span class="help-block" role="alert">{{ $errors->first('from_unit_id') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleConsignment.fields.from_unit_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('to_unit_id') ? 'has-error' : '' }}">
                            <label for="to_unit_id">{{ trans('cruds.vehicleConsignment.fields.to_unit') }}</label>
                            <select class="form-control select2" name="to_unit_id" id="to_unit_id">
                                <option value="">{{ trans('global.pleaseSelect') }}</option>
                                @foreach($units as $id => $entry)
                                    <option value="{{ $id }}" {{ old('to_unit_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('to_unit_id'))
                                <span class="help-block" role="alert">{{ $errors->first('to_unit_id') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleConsignment.fields.to_unit_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('to_unit_name') ? 'has-error' : '' }}">
                            <label for="to_unit_name">Destino livre / particular</label>
                            <input class="form-control" type="text" name="to_unit_name" id="to_unit_name" value="{{ old('to_unit_name') }}" placeholder="Ex.: Joao Silva">
                            @if($errors->has('to_unit_name'))
                                <span class="help-block" role="alert">{{ $errors->first('to_unit_name') }}</span>
                            @endif
                            <span class="help-block">Preencha este campo quando o destino nao for uma unidade interna.</span>
                        </div>
                        <div class="form-group {{ $errors->has('starts_at') ? 'has-error' : '' }}">
                            <label class="required" for="starts_at">{{ trans('cruds.vehicleConsignment.fields.starts_at') }}</label>
                            <input class="form-control datetime" type="text" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" required>
                            @if($errors->has('starts_at'))
                                <span class="help-block" role="alert">{{ $errors->first('starts_at') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleConsignment.fields.starts_at_helper') }}</span>
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

@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Editar consignação</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.vehicle-consignments.update', $vehicleConsignment) }}">
                        @method('PUT')
                        @csrf

                        <div class="form-group {{ $errors->has('vehicle_id') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_id">{{ trans('cruds.vehicleConsignment.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ (string) old('vehicle_id', $vehicleConsignment->vehicle_id) === (string) $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle_id'))<span class="help-block">{{ $errors->first('vehicle_id') }}</span>@endif
                        </div>

                        <div class="form-group {{ $errors->has('from_unit_id') ? 'has-error' : '' }}">
                            <label class="required" for="from_unit_id">{{ trans('cruds.vehicleConsignment.fields.from_unit') }}</label>
                            <select class="form-control select2" name="from_unit_id" id="from_unit_id" required>
                                @foreach($units as $id => $entry)
                                    <option value="{{ $id }}" {{ (string) old('from_unit_id', $vehicleConsignment->from_unit_id) === (string) $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('from_unit_id'))<span class="help-block">{{ $errors->first('from_unit_id') }}</span>@endif
                        </div>

                        <div class="form-group {{ $errors->has('to_unit_id') ? 'has-error' : '' }}">
                            <label for="to_unit_id">{{ trans('cruds.vehicleConsignment.fields.to_unit') }}</label>
                            <select class="form-control select2" name="to_unit_id" id="to_unit_id">
                                <option value="">{{ trans('global.pleaseSelect') }}</option>
                                @foreach($units as $id => $entry)
                                    <option value="{{ $id }}" {{ (string) old('to_unit_id', $vehicleConsignment->to_unit_id) === (string) $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('to_unit_id'))<span class="help-block">{{ $errors->first('to_unit_id') }}</span>@endif
                        </div>

                        <div class="form-group {{ $errors->has('to_unit_name') ? 'has-error' : '' }}">
                            <label for="to_unit_name">Destino livre / particular</label>
                            <input class="form-control" type="text" name="to_unit_name" id="to_unit_name" value="{{ old('to_unit_name', $vehicleConsignment->to_unit_name) }}">
                            @if($errors->has('to_unit_name'))<span class="help-block">{{ $errors->first('to_unit_name') }}</span>@endif
                        </div>

                        <div class="form-group {{ $errors->has('starts_at') ? 'has-error' : '' }}">
                            <label class="required" for="starts_at">{{ trans('cruds.vehicleConsignment.fields.starts_at') }}</label>
                            <input class="form-control datetime" type="text" name="starts_at" id="starts_at" value="{{ old('starts_at', optional($vehicleConsignment->starts_at)->format(config('panel.date_format').' '.config('panel.time_format'))) }}" required>
                            @if($errors->has('starts_at'))<span class="help-block">{{ $errors->first('starts_at') }}</span>@endif
                        </div>

                        <div class="form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                            <label class="required" for="status">{{ trans('cruds.vehicleConsignment.fields.status') }}</label>
                            <select class="form-control" name="status" id="status" required>
                                <option value="active" {{ old('status', $vehicleConsignment->status) === 'active' ? 'selected' : '' }} {{ $vehicleConsignment->status === 'closed' ? 'disabled' : '' }}>Ativa</option>
                                <option value="closed" {{ old('status', $vehicleConsignment->status) === 'closed' ? 'selected' : '' }}>Encerrada</option>
                            </select>
                            @if($vehicleConsignment->status === 'closed')<input type="hidden" name="status" value="closed">@endif
                            @if($errors->has('status'))<span class="help-block">{{ $errors->first('status') }}</span>@endif
                        </div>

                        <div class="form-group {{ $errors->has('ends_at') ? 'has-error' : '' }}">
                            <label for="ends_at">{{ trans('cruds.vehicleConsignment.fields.ends_at') }}</label>
                            <input class="form-control datetime" type="text" name="ends_at" id="ends_at" value="{{ old('ends_at', optional($vehicleConsignment->ends_at)->format(config('panel.date_format').' '.config('panel.time_format'))) }}">
                            <span class="help-block">Obrigatória ao encerrar. Uma consignação encerrada não pode voltar a ativa.</span>
                            @if($errors->has('ends_at'))<span class="help-block">{{ $errors->first('ends_at') }}</span>@endif
                        </div>

                        <button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>
                        <a class="btn btn-default" href="{{ route('admin.vehicle-consignments.index') }}">{{ trans('global.cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.vehicleGroup.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.vehicle-groups.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.vehicleGroup.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleGroup.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('vehicles') ? 'has-error' : '' }}">
                            <label for="vehicles">{{ trans('cruds.vehicleGroup.fields.vehicles') }}</label>
                            <select class="form-control select2" name="vehicles[]" id="vehicles" multiple>
                                @foreach($vehicles as $id => $vehicle)
                                    <option value="{{ $id }}" {{ in_array($id, old('vehicles', [])) ? 'selected' : '' }}>{{ $vehicle }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicles'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicles') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleGroup.fields.vehicles_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('clients') ? 'has-error' : '' }}">
                            <label for="clients">{{ trans('cruds.vehicleGroup.fields.clients') }}</label>
                            <select class="form-control select2" name="clients[]" id="clients" multiple>
                                @foreach($clients as $id => $client)
                                    <option value="{{ $id }}" {{ in_array($id, old('clients', [])) ? 'selected' : '' }}>{{ $client }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('clients'))
                                <span class="help-block" role="alert">{{ $errors->first('clients') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleGroup.fields.clients_helper') }}</span>
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

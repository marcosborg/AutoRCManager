@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.vehicleConsignment.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.vehicle-consignments.update", [$vehicleConsignment->id]) }}">
                        @method('PUT')
                        @csrf
                        <div class="form-group">
                            <label>{{ trans('cruds.vehicleConsignment.fields.vehicle') }}</label>
                            <input class="form-control" type="text" value="{{ $vehicleConsignment->vehicle ? ($vehicleConsignment->vehicle->license ?? $vehicleConsignment->vehicle->foreign_license ?? $vehicleConsignment->vehicle->id) : '' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label>{{ trans('cruds.vehicleConsignment.fields.from_unit') }}</label>
                            <input class="form-control" type="text" value="{{ $vehicleConsignment->from_unit->name ?? '' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label>{{ trans('cruds.vehicleConsignment.fields.to_unit') }}</label>
                            <input class="form-control" type="text" value="{{ $vehicleConsignment->to_unit->name ?? '' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label>{{ trans('cruds.vehicleConsignment.fields.reference_value') }}</label>
                            <input class="form-control" type="text" value="{{ $vehicleConsignment->reference_value }}" disabled>
                        </div>
                        <div class="form-group">
                            <label>{{ trans('cruds.vehicleConsignment.fields.starts_at') }}</label>
                            <input class="form-control" type="text" value="{{ $vehicleConsignment->starts_at }}" disabled>
                        </div>
                        <div class="form-group {{ $errors->has('ends_at') ? 'has-error' : '' }}">
                            <label class="required" for="ends_at">{{ trans('cruds.vehicleConsignment.fields.ends_at') }}</label>
                            <input class="form-control datetime" type="text" name="ends_at" id="ends_at" value="{{ old('ends_at', $vehicleConsignment->ends_at) }}" required>
                            @if($errors->has('ends_at'))
                                <span class="help-block" role="alert">{{ $errors->first('ends_at') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleConsignment.fields.ends_at_helper') }}</span>
                        </div>
                        <input type="hidden" name="status" value="closed">
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

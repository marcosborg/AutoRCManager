@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.depreciation.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.depreciations.update", [$depreciation->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('license_plate') ? 'has-error' : '' }}">
                            <label class="required" for="license_plate">{{ trans('cruds.depreciation.fields.license_plate') }}</label>
                            <input class="form-control" type="text" name="license_plate" id="license_plate" value="{{ old('license_plate', $depreciation->license_plate) }}" required>
                            @if($errors->has('license_plate'))
                                <span class="help-block" role="alert">{{ $errors->first('license_plate') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.depreciation.fields.license_plate_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.depreciation.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', $depreciation->value) }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.depreciation.fields.value_helper') }}</span>
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
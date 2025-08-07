@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.timelog.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.timelogs.update", [$timelog->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('vehicle') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_id">{{ trans('cruds.timelog.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('vehicle_id') ? old('vehicle_id') : $timelog->vehicle->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.timelog.fields.vehicle_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('user') ? 'has-error' : '' }}">
                            <label class="required" for="user_id">{{ trans('cruds.timelog.fields.user') }}</label>
                            <select class="form-control select2" name="user_id" id="user_id" required>
                                @foreach($users as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('user_id') ? old('user_id') : $timelog->user->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('user'))
                                <span class="help-block" role="alert">{{ $errors->first('user') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.timelog.fields.user_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('start_time') ? 'has-error' : '' }}">
                            <label class="required" for="start_time">{{ trans('cruds.timelog.fields.start_time') }}</label>
                            <input class="form-control datetime" type="text" name="start_time" id="start_time" value="{{ old('start_time', $timelog->start_time) }}" required>
                            @if($errors->has('start_time'))
                                <span class="help-block" role="alert">{{ $errors->first('start_time') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.timelog.fields.start_time_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('end_time') ? 'has-error' : '' }}">
                            <label for="end_time">{{ trans('cruds.timelog.fields.end_time') }}</label>
                            <input class="form-control datetime" type="text" name="end_time" id="end_time" value="{{ old('end_time', $timelog->end_time) }}">
                            @if($errors->has('end_time'))
                                <span class="help-block" role="alert">{{ $errors->first('end_time') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.timelog.fields.end_time_helper') }}</span>
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
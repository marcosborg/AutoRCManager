@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.vehicleFinancialEntry.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.vehicle-financial-entries.update", [$vehicleFinancialEntry->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('vehicle') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_id">{{ trans('cruds.vehicleFinancialEntry.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('vehicle_id') ? old('vehicle_id') : $vehicleFinancialEntry->vehicle->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleFinancialEntry.fields.vehicle_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('entry_type') ? 'has-error' : '' }}">
                            <label class="required" for="entry_type">{{ trans('cruds.vehicleFinancialEntry.fields.entry_type') }}</label>
                            <select class="form-control select2" name="entry_type" id="entry_type" required>
                                @foreach($entryTypes as $value => $label)
                                    <option value="{{ $value }}" {{ (old('entry_type') ? old('entry_type') : $vehicleFinancialEntry->entry_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('entry_type'))
                                <span class="help-block" role="alert">{{ $errors->first('entry_type') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleFinancialEntry.fields.entry_type_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('category') ? 'has-error' : '' }}">
                            <label class="required" for="category">{{ trans('cruds.vehicleFinancialEntry.fields.category') }}</label>
                            <input class="form-control" type="text" name="category" id="category" value="{{ old('category', $vehicleFinancialEntry->category) }}" required>
                            @if($errors->has('category'))
                                <span class="help-block" role="alert">{{ $errors->first('category') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleFinancialEntry.fields.category_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                            <label class="required" for="amount">{{ trans('cruds.vehicleFinancialEntry.fields.amount') }}</label>
                            <input class="form-control" type="number" name="amount" id="amount" value="{{ old('amount', $vehicleFinancialEntry->amount) }}" step="0.01" min="0" required>
                            @if($errors->has('amount'))
                                <span class="help-block" role="alert">{{ $errors->first('amount') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleFinancialEntry.fields.amount_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('entry_date') ? 'has-error' : '' }}">
                            <label class="required" for="entry_date">{{ trans('cruds.vehicleFinancialEntry.fields.entry_date') }}</label>
                            <input class="form-control date" type="text" name="entry_date" id="entry_date" value="{{ old('entry_date', $vehicleFinancialEntry->entry_date) }}" required>
                            @if($errors->has('entry_date'))
                                <span class="help-block" role="alert">{{ $errors->first('entry_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleFinancialEntry.fields.entry_date_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <label for="notes">{{ trans('cruds.vehicleFinancialEntry.fields.notes') }}</label>
                            <textarea class="form-control" name="notes" id="notes">{{ old('notes', $vehicleFinancialEntry->notes) }}</textarea>
                            @if($errors->has('notes'))
                                <span class="help-block" role="alert">{{ $errors->first('notes') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleFinancialEntry.fields.notes_helper') }}</span>
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

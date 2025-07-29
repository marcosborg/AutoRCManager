@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.accountOperation.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.account-operations.update", [$accountOperation->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('vehicle') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_id">{{ trans('cruds.accountOperation.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id" required>
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('vehicle_id') ? old('vehicle_id') : $accountOperation->vehicle->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.vehicle_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('account_item') ? 'has-error' : '' }}">
                            <label class="required" for="account_item_id">{{ trans('cruds.accountOperation.fields.account_item') }}</label>
                            <select class="form-control select2" name="account_item_id" id="account_item_id" required>
                                @foreach($account_items as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('account_item_id') ? old('account_item_id') : $accountOperation->account_item->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('account_item'))
                                <span class="help-block" role="alert">{{ $errors->first('account_item') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.account_item_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('qty') ? 'has-error' : '' }}">
                            <label class="required" for="qty">{{ trans('cruds.accountOperation.fields.qty') }}</label>
                            <input class="form-control" type="number" name="qty" id="qty" value="{{ old('qty', $accountOperation->qty) }}" step="1" required>
                            @if($errors->has('qty'))
                                <span class="help-block" role="alert">{{ $errors->first('qty') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.qty_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('total') ? 'has-error' : '' }}">
                            <label class="required" for="total">{{ trans('cruds.accountOperation.fields.total') }}</label>
                            <input class="form-control" type="number" name="total" id="total" value="{{ old('total', $accountOperation->total) }}" step="0.01" required>
                            @if($errors->has('total'))
                                <span class="help-block" role="alert">{{ $errors->first('total') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.total_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('payment_method') ? 'has-error' : '' }}">
                            <label for="payment_method_id">{{ trans('cruds.accountOperation.fields.payment_method') }}</label>
                            <select class="form-control select2" name="payment_method_id" id="payment_method_id">
                                @foreach($payment_methods as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('payment_method_id') ? old('payment_method_id') : $accountOperation->payment_method->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('payment_method'))
                                <span class="help-block" role="alert">{{ $errors->first('payment_method') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.payment_method_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
                            <label for="date">{{ trans('cruds.accountOperation.fields.date') }}</label>
                            <input class="form-control date" type="text" name="date" id="date" value="{{ old('date', $accountOperation->date) }}">
                            @if($errors->has('date'))
                                <span class="help-block" role="alert">{{ $errors->first('date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.date_helper') }}</span>
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
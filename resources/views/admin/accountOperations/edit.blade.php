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
                        <div class="form-group {{ $errors->has('balance') ? 'has-error' : '' }}">
                            <label class="required" for="balance">{{ trans('cruds.accountOperation.fields.balance') }}</label>
                            <input class="form-control" type="number" name="balance" id="balance" value="{{ old('balance', $accountOperation->balance) }}" step="0.01" required>
                            @if($errors->has('balance'))
                                <span class="help-block" role="alert">{{ $errors->first('balance') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.balance_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('payments') ? 'has-error' : '' }}">
                            <label for="payments">{{ trans('cruds.accountOperation.fields.payments') }}</label>
                            <textarea class="form-control" name="payments" id="payments">{{ old('payments', $accountOperation->payments) }}</textarea>
                            @if($errors->has('payments'))
                                <span class="help-block" role="alert">{{ $errors->first('payments') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountOperation.fields.payments_helper') }}</span>
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
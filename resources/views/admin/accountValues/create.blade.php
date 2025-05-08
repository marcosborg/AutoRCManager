@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.accountValue.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.account-values.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('account_item') ? 'has-error' : '' }}">
                            <label class="required" for="account_item_id">{{ trans('cruds.accountValue.fields.account_item') }}</label>
                            <select class="form-control select2" name="account_item_id" id="account_item_id" required>
                                @foreach($account_items as $id => $entry)
                                    <option value="{{ $id }}" {{ old('account_item_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('account_item'))
                                <span class="help-block" role="alert">{{ $errors->first('account_item') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountValue.fields.account_item_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.accountValue.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', '') }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountValue.fields.value_helper') }}</span>
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
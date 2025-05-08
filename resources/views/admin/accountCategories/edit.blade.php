@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.accountCategory.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.account-categories.update", [$accountCategory->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.accountCategory.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $accountCategory->name) }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountCategory.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('account_department') ? 'has-error' : '' }}">
                            <label class="required" for="account_department_id">{{ trans('cruds.accountCategory.fields.account_department') }}</label>
                            <select class="form-control select2" name="account_department_id" id="account_department_id" required>
                                @foreach($account_departments as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('account_department_id') ? old('account_department_id') : $accountCategory->account_department->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('account_department'))
                                <span class="help-block" role="alert">{{ $errors->first('account_department') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.accountCategory.fields.account_department_helper') }}</span>
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
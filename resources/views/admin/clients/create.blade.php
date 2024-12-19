@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.client.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.clients.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.client.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('vat') ? 'has-error' : '' }}">
                            <label for="vat">{{ trans('cruds.client.fields.vat') }}</label>
                            <input class="form-control" type="text" name="vat" id="vat" value="{{ old('vat', '') }}">
                            @if($errors->has('vat'))
                                <span class="help-block" role="alert">{{ $errors->first('vat') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.vat_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('address') ? 'has-error' : '' }}">
                            <label for="address">{{ trans('cruds.client.fields.address') }}</label>
                            <input class="form-control" type="text" name="address" id="address" value="{{ old('address', '') }}">
                            @if($errors->has('address'))
                                <span class="help-block" role="alert">{{ $errors->first('address') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.address_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('location') ? 'has-error' : '' }}">
                            <label for="location">{{ trans('cruds.client.fields.location') }}</label>
                            <input class="form-control" type="text" name="location" id="location" value="{{ old('location', '') }}">
                            @if($errors->has('location'))
                                <span class="help-block" role="alert">{{ $errors->first('location') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.location_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('zip') ? 'has-error' : '' }}">
                            <label for="zip">{{ trans('cruds.client.fields.zip') }}</label>
                            <input class="form-control" type="text" name="zip" id="zip" value="{{ old('zip', '') }}">
                            @if($errors->has('zip'))
                                <span class="help-block" role="alert">{{ $errors->first('zip') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.zip_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                            <label for="phone">{{ trans('cruds.client.fields.phone') }}</label>
                            <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone', '') }}">
                            @if($errors->has('phone'))
                                <span class="help-block" role="alert">{{ $errors->first('phone') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.phone_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                            <label for="email">{{ trans('cruds.client.fields.email') }}</label>
                            <input class="form-control" type="email" name="email" id="email" value="{{ old('email') }}">
                            @if($errors->has('email'))
                                <span class="help-block" role="alert">{{ $errors->first('email') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.email_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('country') ? 'has-error' : '' }}">
                            <label for="country_id">{{ trans('cruds.client.fields.country') }}</label>
                            <select class="form-control select2" name="country_id" id="country_id">
                                @foreach($countries as $id => $entry)
                                    <option value="{{ $id }}" {{ old('country_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('country'))
                                <span class="help-block" role="alert">{{ $errors->first('country') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.country_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_name') ? 'has-error' : '' }}">
                            <label for="company_name">{{ trans('cruds.client.fields.company_name') }}</label>
                            <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name', '') }}">
                            @if($errors->has('company_name'))
                                <span class="help-block" role="alert">{{ $errors->first('company_name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_vat') ? 'has-error' : '' }}">
                            <label for="company_vat">{{ trans('cruds.client.fields.company_vat') }}</label>
                            <input class="form-control" type="text" name="company_vat" id="company_vat" value="{{ old('company_vat', '') }}">
                            @if($errors->has('company_vat'))
                                <span class="help-block" role="alert">{{ $errors->first('company_vat') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_vat_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_address') ? 'has-error' : '' }}">
                            <label for="company_address">{{ trans('cruds.client.fields.company_address') }}</label>
                            <input class="form-control" type="text" name="company_address" id="company_address" value="{{ old('company_address', '') }}">
                            @if($errors->has('company_address'))
                                <span class="help-block" role="alert">{{ $errors->first('company_address') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_address_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_location') ? 'has-error' : '' }}">
                            <label for="company_location">{{ trans('cruds.client.fields.company_location') }}</label>
                            <input class="form-control" type="text" name="company_location" id="company_location" value="{{ old('company_location', '') }}">
                            @if($errors->has('company_location'))
                                <span class="help-block" role="alert">{{ $errors->first('company_location') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_location_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_zip') ? 'has-error' : '' }}">
                            <label for="company_zip">{{ trans('cruds.client.fields.company_zip') }}</label>
                            <input class="form-control" type="text" name="company_zip" id="company_zip" value="{{ old('company_zip', '') }}">
                            @if($errors->has('company_zip'))
                                <span class="help-block" role="alert">{{ $errors->first('company_zip') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_zip_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_phone') ? 'has-error' : '' }}">
                            <label for="company_phone">{{ trans('cruds.client.fields.company_phone') }}</label>
                            <input class="form-control" type="text" name="company_phone" id="company_phone" value="{{ old('company_phone', '') }}">
                            @if($errors->has('company_phone'))
                                <span class="help-block" role="alert">{{ $errors->first('company_phone') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_phone_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_email') ? 'has-error' : '' }}">
                            <label for="company_email">{{ trans('cruds.client.fields.company_email') }}</label>
                            <input class="form-control" type="email" name="company_email" id="company_email" value="{{ old('company_email') }}">
                            @if($errors->has('company_email'))
                                <span class="help-block" role="alert">{{ $errors->first('company_email') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_email_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company_country') ? 'has-error' : '' }}">
                            <label for="company_country_id">{{ trans('cruds.client.fields.company_country') }}</label>
                            <select class="form-control select2" name="company_country_id" id="company_country_id">
                                @foreach($company_countries as $id => $entry)
                                    <option value="{{ $id }}" {{ old('company_country_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company_country'))
                                <span class="help-block" role="alert">{{ $errors->first('company_country') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.client.fields.company_country_helper') }}</span>
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
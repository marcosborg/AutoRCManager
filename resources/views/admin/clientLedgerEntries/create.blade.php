@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.clientLedgerEntry.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.client-ledger-entries.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('client') ? 'has-error' : '' }}">
                            <label class="required" for="client_id">{{ trans('cruds.clientLedgerEntry.fields.client') }}</label>
                            <select class="form-control select2" name="client_id" id="client_id" required>
                                @foreach($clients as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('client_id', $selectedClientId) == $id) ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('client'))
                                <span class="help-block" role="alert">{{ $errors->first('client') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.client_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('vehicle') ? 'has-error' : '' }}">
                            <label for="vehicle_id">{{ trans('cruds.clientLedgerEntry.fields.vehicle') }}</label>
                            <select class="form-control select2" name="vehicle_id" id="vehicle_id">
                                @foreach($vehicles as $id => $entry)
                                    <option value="{{ $id }}" {{ old('vehicle_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.vehicle_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('entry_type') ? 'has-error' : '' }}">
                            <label class="required" for="entry_type">{{ trans('cruds.clientLedgerEntry.fields.entry_type') }}</label>
                            <select class="form-control select2" name="entry_type" id="entry_type" required>
                                @foreach($entryTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('entry_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('entry_type'))
                                <span class="help-block" role="alert">{{ $errors->first('entry_type') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.entry_type_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                            <label class="required" for="amount">{{ trans('cruds.clientLedgerEntry.fields.amount') }}</label>
                            <input class="form-control" type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0" required>
                            @if($errors->has('amount'))
                                <span class="help-block" role="alert">{{ $errors->first('amount') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.amount_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('entry_date') ? 'has-error' : '' }}">
                            <label class="required" for="entry_date">{{ trans('cruds.clientLedgerEntry.fields.entry_date') }}</label>
                            <input class="form-control date" type="text" name="entry_date" id="entry_date" value="{{ old('entry_date') }}" required>
                            @if($errors->has('entry_date'))
                                <span class="help-block" role="alert">{{ $errors->first('entry_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.entry_date_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                            <label class="required" for="description">{{ trans('cruds.clientLedgerEntry.fields.description') }}</label>
                            <input class="form-control" type="text" name="description" id="description" value="{{ old('description') }}" required>
                            @if($errors->has('description'))
                                <span class="help-block" role="alert">{{ $errors->first('description') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.description_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <label for="notes">{{ trans('cruds.clientLedgerEntry.fields.notes') }}</label>
                            <textarea class="form-control" name="notes" id="notes">{{ old('notes') }}</textarea>
                            @if($errors->has('notes'))
                                <span class="help-block" role="alert">{{ $errors->first('notes') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.notes_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('attachment') ? 'has-error' : '' }}">
                            <label for="attachment">{{ trans('cruds.clientLedgerEntry.fields.attachment') }}</label>
                            <input class="form-control" type="file" name="attachment" id="attachment">
                            @if($errors->has('attachment'))
                                <span class="help-block" role="alert">{{ $errors->first('attachment') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.clientLedgerEntry.fields.attachment_helper') }}</span>
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

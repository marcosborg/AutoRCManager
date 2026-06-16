@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">Editar lead #{{ $lead->id }}</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.leads.update', $lead) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4 form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                                <label class="required">Estado</label>
                                <select class="form-control" name="status" required>
                                    @foreach($statuses as $status => $label)
                                        <option value="{{ $status }}" {{ old('status', $lead->status) === $status ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($errors->has('status'))<span class="help-block">{{ $errors->first('status') }}</span>@endif
                            </div>
                            <div class="col-md-4 form-group {{ $errors->has('assigned_user_id') ? 'has-error' : '' }}">
                                <label>Vendedor</label>
                                <select class="form-control select2" name="assigned_user_id">
                                    @foreach($salespeople as $id => $name)
                                        <option value="{{ $id }}" {{ (string) old('assigned_user_id', $lead->assigned_user_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @if($errors->has('assigned_user_id'))<span class="help-block">{{ $errors->first('assigned_user_id') }}</span>@endif
                            </div>
                        </div>
                        <div class="form-group {{ $errors->has('full_name') ? 'has-error' : '' }}">
                            <label>Nome</label>
                            <input class="form-control" name="full_name" value="{{ old('full_name', $lead->full_name) }}">
                            @if($errors->has('full_name'))<span class="help-block">{{ $errors->first('full_name') }}</span>@endif
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                                <label>Telefone</label>
                                <input class="form-control" name="phone" value="{{ old('phone', $lead->phone) }}">
                                @if($errors->has('phone'))<span class="help-block">{{ $errors->first('phone') }}</span>@endif
                            </div>
                            <div class="col-md-6 form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                                <label>Email</label>
                                <input class="form-control" name="email" value="{{ old('email', $lead->email) }}">
                                @if($errors->has('email'))<span class="help-block">{{ $errors->first('email') }}</span>@endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group {{ $errors->has('vehicle_interest') ? 'has-error' : '' }}">
                                <label>Veiculo/interesse</label>
                                <input class="form-control" name="vehicle_interest" value="{{ old('vehicle_interest', $lead->vehicle_interest) }}">
                                @if($errors->has('vehicle_interest'))<span class="help-block">{{ $errors->first('vehicle_interest') }}</span>@endif
                            </div>
                            <div class="col-md-6 form-group {{ $errors->has('budget') ? 'has-error' : '' }}">
                                <label>Orcamento</label>
                                <input class="form-control" name="budget" value="{{ old('budget', $lead->budget) }}">
                                @if($errors->has('budget'))<span class="help-block">{{ $errors->first('budget') }}</span>@endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group {{ $errors->has('financing') ? 'has-error' : '' }}">
                                <label>Financiamento</label>
                                <input class="form-control" name="financing" value="{{ old('financing', $lead->financing) }}">
                                @if($errors->has('financing'))<span class="help-block">{{ $errors->first('financing') }}</span>@endif
                            </div>
                            <div class="col-md-6 form-group {{ $errors->has('trade_in') ? 'has-error' : '' }}">
                                <label>Retoma</label>
                                <input class="form-control" name="trade_in" value="{{ old('trade_in', $lead->trade_in) }}">
                                @if($errors->has('trade_in'))<span class="help-block">{{ $errors->first('trade_in') }}</span>@endif
                            </div>
                        </div>
                        <button class="btn btn-success" type="submit">{{ trans('global.save') }}</button>
                        <a class="btn btn-default" href="{{ route('admin.leads.show', $lead) }}">{{ trans('global.cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

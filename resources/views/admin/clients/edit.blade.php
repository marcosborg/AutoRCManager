@extends('layouts.admin')
@section('content')
    <div class="content">

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{ trans('global.edit') }} {{ trans('cruds.client.title_singular') }}
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="{{ route('admin.clients.update', [$client->id]) }}"
                            enctype="multipart/form-data">
                            @method('PUT')
                            @csrf

                            {{-- Dados do cliente --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                                        <label class="required" for="name">{{ trans('cruds.client.fields.name') }}</label>
                                        <input class="form-control" type="text" name="name" id="name"
                                            value="{{ old('name', $client->name) }}" required>
                                        @if($errors->has('name'))
                                            <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.name_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('vat') ? 'has-error' : '' }}">
                                        <label for="vat">{{ trans('cruds.client.fields.vat') }}</label>
                                        <input class="form-control" type="text" name="vat" id="vat"
                                            value="{{ old('vat', $client->vat) }}">
                                        @if($errors->has('vat'))
                                            <span class="help-block" role="alert">{{ $errors->first('vat') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.vat_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                                        <label for="phone">{{ trans('cruds.client.fields.phone') }}</label>
                                        <input class="form-control" type="text" name="phone" id="phone"
                                            value="{{ old('phone', $client->phone) }}">
                                        @if($errors->has('phone'))
                                            <span class="help-block" role="alert">{{ $errors->first('phone') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.phone_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                                        <label for="email">{{ trans('cruds.client.fields.email') }}</label>
                                        <input class="form-control" type="email" name="email" id="email"
                                            value="{{ old('email', $client->email) }}">
                                        @if($errors->has('email'))
                                            <span class="help-block" role="alert">{{ $errors->first('email') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.email_helper') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Morada do cliente --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('address') ? 'has-error' : '' }}">
                                        <label for="address">{{ trans('cruds.client.fields.address') }}</label>
                                        <input class="form-control" type="text" name="address" id="address"
                                            value="{{ old('address', $client->address) }}">
                                        @if($errors->has('address'))
                                            <span class="help-block" role="alert">{{ $errors->first('address') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.address_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('location') ? 'has-error' : '' }}">
                                        <label for="location">{{ trans('cruds.client.fields.location') }}</label>
                                        <input class="form-control" type="text" name="location" id="location"
                                            value="{{ old('location', $client->location) }}">
                                        @if($errors->has('location'))
                                            <span class="help-block" role="alert">{{ $errors->first('location') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.location_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('zip') ? 'has-error' : '' }}">
                                        <label for="zip">{{ trans('cruds.client.fields.zip') }}</label>
                                        <input class="form-control" type="text" name="zip" id="zip"
                                            value="{{ old('zip', $client->zip) }}">
                                        @if($errors->has('zip'))
                                            <span class="help-block" role="alert">{{ $errors->first('zip') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.zip_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('country') ? 'has-error' : '' }}">
                                        <label for="country_id">{{ trans('cruds.client.fields.country') }}</label>
                                        <select class="form-control select2" name="country_id" id="country_id">
                                            @foreach($countries as $id => $entry)
                                                <option value="{{ $id }}" {{ (old('country_id') ? old('country_id') : $client->country->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                            @endforeach
                                        </select>
                                        @if($errors->has('country'))
                                            <span class="help-block" role="alert">{{ $errors->first('country') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.country_helper') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('provenience_id') ? 'has-error' : '' }}">
                                        <label class="required" for="provenience_id">{{ trans('cruds.client.fields.provenience') }}</label>
                                        <div class="input-group">
                                            <select class="form-control select2" name="provenience_id" id="provenience_id" required>
                                                @foreach($proveniences as $id => $entry)
                                                    <option value="{{ $id }}" {{ (old('provenience_id') ? old('provenience_id') : $client->provenience->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                                @endforeach
                                            </select>
                                            <span class="input-group-btn">
                                                <button class="btn btn-default js-create-provenience" type="button" data-target="#provenience_id">
                                                    Nova
                                                </button>
                                            </span>
                                        </div>
                                        @if($errors->has('provenience_id'))
                                            <span class="help-block" role="alert">{{ $errors->first('provenience_id') }}</span>
                                        @endif
                                        <span class="help-block">{{ trans('cruds.client.fields.provenience_helper') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Dados da empresa --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_name') ? 'has-error' : '' }}">
                                        <label for="company_name">{{ trans('cruds.client.fields.company_name') }}</label>
                                        <input class="form-control" type="text" name="company_name" id="company_name"
                                            value="{{ old('company_name', $client->company_name) }}">
                                        @if($errors->has('company_name'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_name') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_name_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_vat') ? 'has-error' : '' }}">
                                        <label for="company_vat">{{ trans('cruds.client.fields.company_vat') }}</label>
                                        <input class="form-control" type="text" name="company_vat" id="company_vat"
                                            value="{{ old('company_vat', $client->company_vat) }}">
                                        @if($errors->has('company_vat'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_vat') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_vat_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_phone') ? 'has-error' : '' }}">
                                        <label for="company_phone">{{ trans('cruds.client.fields.company_phone') }}</label>
                                        <input class="form-control" type="text" name="company_phone" id="company_phone"
                                            value="{{ old('company_phone', $client->company_phone) }}">
                                        @if($errors->has('company_phone'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_phone') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_phone_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_email') ? 'has-error' : '' }}">
                                        <label for="company_email">{{ trans('cruds.client.fields.company_email') }}</label>
                                        <input class="form-control" type="email" name="company_email" id="company_email"
                                            value="{{ old('company_email', $client->company_email) }}">
                                        @if($errors->has('company_email'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_email') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_email_helper') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Morada da empresa --}}
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_address') ? 'has-error' : '' }}">
                                        <label
                                            for="company_address">{{ trans('cruds.client.fields.company_address') }}</label>
                                        <input class="form-control" type="text" name="company_address" id="company_address"
                                            value="{{ old('company_address', $client->company_address) }}">
                                        @if($errors->has('company_address'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_address') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_address_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_location') ? 'has-error' : '' }}">
                                        <label
                                            for="company_location">{{ trans('cruds.client.fields.company_location') }}</label>
                                        <input class="form-control" type="text" name="company_location"
                                            id="company_location"
                                            value="{{ old('company_location', $client->company_location) }}">
                                        @if($errors->has('company_location'))
                                            <span class="help-block"
                                                role="alert">{{ $errors->first('company_location') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_location_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_zip') ? 'has-error' : '' }}">
                                        <label for="company_zip">{{ trans('cruds.client.fields.company_zip') }}</label>
                                        <input class="form-control" type="text" name="company_zip" id="company_zip"
                                            value="{{ old('company_zip', $client->company_zip) }}">
                                        @if($errors->has('company_zip'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_zip') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_zip_helper') }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('company_country') ? 'has-error' : '' }}">
                                        <label
                                            for="company_country_id">{{ trans('cruds.client.fields.company_country') }}</label>
                                        <select class="form-control select2" name="company_country_id"
                                            id="company_country_id">
                                            @foreach($company_countries as $id => $entry)
                                                <option value="{{ $id }}" {{ (old('company_country_id') ? old('company_country_id') : $client->company_country->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                            @endforeach
                                        </select>
                                        @if($errors->has('company_country'))
                                            <span class="help-block" role="alert">{{ $errors->first('company_country') }}</span>
                                        @endif
                                        <span
                                            class="help-block">{{ trans('cruds.client.fields.company_country_helper') }}</span>
                                    </div>
                                </div>
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


        @php
            $formatMoney = function ($value) {
                return number_format((float) $value, 2, ',', '.') . ' EUR';
            };
            $accountTotals = $currentAccount['totals'] ?? ['debit' => 0, 'credit' => 0, 'balance' => 0];
            $vehicleRows = $currentAccount['vehicleRows'] ?? collect();
            $lotRows = $currentAccount['lotRows'] ?? collect();
            $chargeRows = $currentAccount['chargeRows'] ?? collect();
            $receiptRows = $currentAccount['receiptRows'] ?? collect();
        @endphp

        <div class="panel panel-default">
            <div class="panel-heading">
                Conta corrente
            </div>
            <div class="panel-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#client-account-overview" aria-controls="client-account-overview" role="tab" data-toggle="tab">Conta corrente</a>
                    </li>
                    <li role="presentation">
                        <a href="#client-account-receipts" aria-controls="client-account-receipts" role="tab" data-toggle="tab">Recebimentos</a>
                    </li>
                    <li role="presentation">
                        <a href="#client-account-charges" aria-controls="client-account-charges" role="tab" data-toggle="tab">Outros d&eacute;bitos</a>
                    </li>
                    <li role="presentation">
                        <a href="#client-account-payment" aria-controls="client-account-payment" role="tab" data-toggle="tab">Novo pagamento</a>
                    </li>
                </ul>

                <div class="tab-content" style="padding-top: 15px;">
                    <div role="tabpanel" class="tab-pane active" id="client-account-overview">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="well well-sm">
                            <div><strong>D&eacute;bito total</strong></div>
                            <div class="lead">{{ $formatMoney($accountTotals['debit'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well well-sm">
                            <div><strong>Cr&eacute;dito total</strong></div>
                            <div class="lead">{{ $formatMoney($accountTotals['credit'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well well-sm">
                            <div><strong>Saldo</strong></div>
                            <div class="lead {{ ($accountTotals['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $formatMoney($accountTotals['balance'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <h4>Viaturas</h4>
                    @if($vehicleRows->isEmpty())
                        <p><em>Este cliente ainda n&atilde;o tem viaturas registadas como adquiridas.</em></p>
                    @else
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Viatura</th>
                                    <th>Estado</th>
                                    <th>Data venda</th>
                                    <th>Lote</th>
                                    <th class="text-right">D&eacute;bito</th>
                                    <th class="text-right">Cr&eacute;dito</th>
                                    <th class="text-right">Saldo</th>
                                    <th style="width: 110px;">A&ccedil;&atilde;o</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicleRows as $row)
                                    @php
                                        $vehicle = $row['vehicle'];
                                        $vehicleName = trim(($vehicle->license ?: $vehicle->foreign_license ?: 'Sem matricula') . ' - ' . ($vehicle->brand->name ?? '') . ' - ' . ($vehicle->model ?? ''));
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $vehicleName }}
                                            @if(! $row['counts_in_totals'])
                                                <br><small class="text-muted">N&atilde;o soma nos totais gerais porque pertence a lote.</small>
                                            @endif
                                        </td>
                                        <td>{{ $vehicle->general_state->name ?? '' }}</td>
                                        <td>{{ $vehicle->sale_date }}</td>
                                        <td>
                                            @forelse($row['lots'] as $lot)
                                                <a href="{{ route('admin.vehicle-groups.show', $lot->id) }}" class="label label-default" style="display: inline-block; margin-bottom: 2px;">
                                                    Pertence ao lote {{ $lot->name }}
                                                </a>
                                            @empty
                                                <span class="text-muted">Individual</span>
                                            @endforelse
                                        </td>
                                        <td class="text-right">
                                            @if($row['counts_in_totals'])
                                                {{ $formatMoney($row['debit']) }}
                                            @else
                                                <span class="text-muted">Valor no lote</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($row['counts_in_totals'])
                                                {{ $formatMoney($row['credit']) }}
                                            @else
                                                <span class="text-muted">Valor no lote</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($row['counts_in_totals'])
                                                <span class="{{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ $formatMoney($row['balance']) }}
                                                </span>
                                            @else
                                                <span class="text-muted">Valor no lote</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}" class="btn btn-xs btn-primary">
                                                Abrir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="table-responsive">
                    <h4>Lotes</h4>
                    @if($lotRows->isEmpty())
                        <p><em>Este cliente ainda n&atilde;o tem lotes adquiridos.</em></p>
                    @else
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Tipo</th>
                                    <th class="text-center">N.&ordm; viaturas</th>
                                    <th class="text-right">D&eacute;bito</th>
                                    <th class="text-right">Cr&eacute;dito aprovado</th>
                                    <th class="text-right">Saldo</th>
                                    <th style="width: 110px;">A&ccedil;&atilde;o</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lotRows as $row)
                                    @php
                                        $lot = $row['lot'];
                                        $typeLabel = $lot->type === 'unitario' ? 'Discriminado' : 'Global';
                                    @endphp
                                    <tr>
                                        <td>{{ $lot->name }}</td>
                                        <td>{{ $typeLabel }}</td>
                                        <td class="text-center">{{ $row['vehicles_count'] }}</td>
                                        <td class="text-right">{{ $formatMoney($row['debit']) }}</td>
                                        <td class="text-right">{{ $formatMoney($row['credit']) }}</td>
                                        <td class="text-right">
                                            <span class="{{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $formatMoney($row['balance']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.vehicle-groups.show', $lot->id) }}" class="btn btn-xs btn-primary">
                                                Abrir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="table-responsive">
                    <h4>Outros d&eacute;bitos</h4>
                    @if($chargeRows->isEmpty())
                        <p><em>Este cliente ainda n&atilde;o tem outros d&eacute;bitos registados.</em></p>
                    @else
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descri&ccedil;&atilde;o</th>
                                    <th class="text-right">D&eacute;bito</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chargeRows as $chargeRow)
                                    <tr>
                                        <td>{{ $chargeRow['charged_at'] }}</td>
                                        <td>{{ $chargeRow['description'] }}</td>
                                        <td class="text-right">{{ $formatMoney($chargeRow['amount']) }}</td>
                                        <td>{{ $chargeRow['notes'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="client-account-receipts">
                        <h4>Recebimentos</h4>
                        @if($receiptRows->isEmpty())
                            <p><em>Este cliente ainda n&atilde;o tem recebimentos registados.</em></p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Origem</th>
                                            <th>Refer&ecirc;ncia</th>
                                            <th>M&eacute;todo</th>
                                            <th>Estado</th>
                                            <th class="text-right">Valor</th>
                                            <th>Notas</th>
                                            <th style="width: 110px;">A&ccedil;&atilde;o</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($receiptRows as $receipt)
                                            <tr>
                                                <td>{{ $receipt['date'] }}</td>
                                                <td>{{ $receipt['source'] }}</td>
                                                <td>{{ $receipt['reference'] }}</td>
                                                <td>{{ $receipt['payment_method'] }}</td>
                                                <td>
                                                    <span class="{{ $receipt['counts_in_balance'] ? 'text-success' : 'text-muted' }}">
                                                        {{ $receipt['status'] }}
                                                    </span>
                                                </td>
                                                <td class="text-right">{{ $formatMoney($receipt['amount']) }}</td>
                                                <td>{{ $receipt['notes'] }}</td>
                                                <td>
                                                    @if($receipt['url'])
                                                        <a href="{{ $receipt['url'] }}" class="btn btn-xs btn-primary">Abrir</a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div role="tabpanel" class="tab-pane" id="client-account-charges">
                        <h4>Outros d&eacute;bitos</h4>

                        <form method="POST" action="{{ route('admin.clients.charges.store', $client->id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group {{ $errors->has('charged_at') ? 'has-error' : '' }}">
                                        <label class="required" for="client_charge_charged_at">Data</label>
                                        <input class="form-control" type="date" name="charged_at" id="client_charge_charged_at" value="{{ old('charged_at', now()->format('Y-m-d')) }}" required>
                                        @if($errors->has('charged_at'))
                                            <span class="help-block" role="alert">{{ $errors->first('charged_at') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                                        <label class="required" for="client_charge_description">Descri&ccedil;&atilde;o</label>
                                        <input class="form-control" type="text" name="description" id="client_charge_description" value="{{ old('description') }}" required>
                                        @if($errors->has('description'))
                                            <span class="help-block" role="alert">{{ $errors->first('description') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('charge_amount') ? 'has-error' : '' }}">
                                        <label class="required" for="client_charge_amount">Valor a debitar</label>
                                        <input class="form-control" type="number" name="charge_amount" id="client_charge_amount" value="{{ old('charge_amount') }}" step="0.01" min="0.01" required>
                                        @if($errors->has('charge_amount'))
                                            <span class="help-block" role="alert">{{ $errors->first('charge_amount') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>D&eacute;bitos gerais</label>
                                        <input class="form-control" type="text" value="{{ $formatMoney($accountTotals['client_charge_debit'] ?? 0) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group {{ $errors->has('charge_notes') ? 'has-error' : '' }}">
                                <label for="client_charge_notes">Notas</label>
                                <textarea class="form-control" name="charge_notes" id="client_charge_notes" rows="3">{{ old('charge_notes') }}</textarea>
                                @if($errors->has('charge_notes'))
                                    <span class="help-block" role="alert">{{ $errors->first('charge_notes') }}</span>
                                @endif
                            </div>
                            <button class="btn btn-warning" type="submit">Registar d&eacute;bito</button>
                        </form>

                        <hr>

                        @if($chargeRows->isEmpty())
                            <p><em>Este cliente ainda n&atilde;o tem outros d&eacute;bitos registados.</em></p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Descri&ccedil;&atilde;o</th>
                                            <th class="text-right">Valor</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($chargeRows as $chargeRow)
                                            <tr>
                                                <td>{{ $chargeRow['charged_at'] }}</td>
                                                <td>{{ $chargeRow['description'] }}</td>
                                                <td class="text-right">{{ $formatMoney($chargeRow['amount']) }}</td>
                                                <td>{{ $chargeRow['notes'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div role="tabpanel" class="tab-pane" id="client-account-payment">
                        <h4>Novo pagamento</h4>
                        <form method="POST" action="{{ route('admin.clients.payments.store', $client->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group {{ $errors->has('payment_type') ? 'has-error' : '' }}">
                                <label>Tipo de pagamento</label>
                                <div>
                                    <label class="radio-inline">
                                        <input type="radio" name="payment_type" value="money" {{ old('payment_type', 'money') !== 'trade_in' ? 'checked' : '' }}>
                                        Dinheiro / transfer&ecirc;ncia
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="payment_type" value="trade_in" {{ old('payment_type') === 'trade_in' ? 'checked' : '' }}>
                                        Retoma
                                    </label>
                                </div>
                                @if($errors->has('payment_type'))
                                    <span class="help-block" role="alert">{{ $errors->first('payment_type') }}</span>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group {{ $errors->has('paid_at') ? 'has-error' : '' }}">
                                        <label class="required" for="client_payment_paid_at">Data</label>
                                        <input class="form-control" type="date" name="paid_at" id="client_payment_paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required>
                                        @if($errors->has('paid_at'))
                                            <span class="help-block" role="alert">{{ $errors->first('paid_at') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('payment_method_id') ? 'has-error' : '' }}">
                                        <label for="client_payment_method_id" style="display: block;">M&eacute;todo</label>
                                        <select class="form-control select2" name="payment_method_id" id="client_payment_method_id" style="width: 100%;">
                                            @foreach($paymentMethods as $id => $entry)
                                                <option value="{{ $id }}" {{ old('payment_method_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                            @endforeach
                                        </select>
                                        @if($errors->has('payment_method_id'))
                                            <span class="help-block" role="alert">{{ $errors->first('payment_method_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                                        <label class="required" for="client_payment_amount">Valor</label>
                                        <input class="form-control" type="number" name="amount" id="client_payment_amount" value="{{ old('amount') }}" step="0.01" min="0.01" required>
                                        @if($errors->has('amount'))
                                            <span class="help-block" role="alert">{{ $errors->first('amount') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group {{ $errors->has('proof_file') ? 'has-error' : '' }}">
                                        <label for="client_payment_proof_file">Comprovativo</label>
                                        <input class="form-control" type="file" name="proof_file" id="client_payment_proof_file">
                                        @if($errors->has('proof_file'))
                                            <span class="help-block" role="alert">{{ $errors->first('proof_file') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Saldo atual</label>
                                        <input class="form-control" type="text" value="{{ $formatMoney($accountTotals['balance'] ?? 0) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div id="client-payment-trade-in-fields" style="display: none;">
                                <hr>
                                <h5>Dados da retoma</h5>
                                <p class="text-muted">
                                    Se a matr&iacute;cula j&aacute; existir, a viatura existente recebe o valor de aquisi&ccedil;&atilde;o e o fornecedor passa a ser este cliente.
                                    Se n&atilde;o existir, &eacute; criada uma retoma nova em stock.
                                </p>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group {{ $errors->has('trade_in_license') ? 'has-error' : '' }}">
                                            <label for="client_payment_trade_in_license">Matr&iacute;cula</label>
                                            <input class="form-control" type="text" name="trade_in_license" id="client_payment_trade_in_license" value="{{ old('trade_in_license') }}">
                                            @if($errors->has('trade_in_license'))
                                                <span class="help-block" role="alert">{{ $errors->first('trade_in_license') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group {{ $errors->has('trade_in_brand_id') ? 'has-error' : '' }}">
                                            <label for="client_payment_trade_in_brand_id">Marca</label>
                                            <select class="form-control select2" name="trade_in_brand_id" id="client_payment_trade_in_brand_id" style="width: 100%;">
                                                @foreach($brands as $id => $entry)
                                                    <option value="{{ $id }}" {{ old('trade_in_brand_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                                @endforeach
                                            </select>
                                            @if($errors->has('trade_in_brand_id'))
                                                <span class="help-block" role="alert">{{ $errors->first('trade_in_brand_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group {{ $errors->has('trade_in_model') ? 'has-error' : '' }}">
                                            <label for="client_payment_trade_in_model">Modelo</label>
                                            <input class="form-control" type="text" name="trade_in_model" id="client_payment_trade_in_model" value="{{ old('trade_in_model') }}">
                                            @if($errors->has('trade_in_model'))
                                                <span class="help-block" role="alert">{{ $errors->first('trade_in_model') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group {{ $errors->has('trade_in_year') ? 'has-error' : '' }}">
                                            <label for="client_payment_trade_in_year">Ano</label>
                                            <input class="form-control" type="number" name="trade_in_year" id="client_payment_trade_in_year" value="{{ old('trade_in_year') }}" min="1900" max="{{ now()->year + 1 }}">
                                            @if($errors->has('trade_in_year'))
                                                <span class="help-block" role="alert">{{ $errors->first('trade_in_year') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group {{ $errors->has('trade_in_kilometers') ? 'has-error' : '' }}">
                                            <label for="client_payment_trade_in_kilometers">Km</label>
                                            <input class="form-control" type="number" name="trade_in_kilometers" id="client_payment_trade_in_kilometers" value="{{ old('trade_in_kilometers') }}" min="0">
                                            @if($errors->has('trade_in_kilometers'))
                                                <span class="help-block" role="alert">{{ $errors->first('trade_in_kilometers') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                                <label for="client_payment_notes">Notas</label>
                                <textarea class="form-control" name="notes" id="client_payment_notes" rows="3">{{ old('notes') }}</textarea>
                                @if($errors->has('notes'))
                                    <span class="help-block" role="alert">{{ $errors->first('notes') }}</span>
                                @endif
                            </div>
                            <button class="btn btn-success" type="submit">Registar pagamento</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @include('admin.clients.partials.provenienceModal')
@endsection

@section('scripts')
@parent
@stack('scripts')
<script>
    $(function () {
        function syncClientPaymentTradeInFields() {
            var isTradeIn = $('input[name="payment_type"]:checked').val() === 'trade_in';
            $('#client-payment-trade-in-fields').toggle(isTradeIn);
            $('#client_payment_method_id').prop('required', !isTradeIn);
        }

        $(document).on('change', 'input[name="payment_type"]', syncClientPaymentTradeInFields);
        syncClientPaymentTradeInFields();
    });
</script>
@endsection

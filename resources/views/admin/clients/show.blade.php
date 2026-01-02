@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.client.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.clients.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $client->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $client->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.vat') }}
                                    </th>
                                    <td>
                                        {{ $client->vat }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.address') }}
                                    </th>
                                    <td>
                                        {{ $client->address }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.location') }}
                                    </th>
                                    <td>
                                        {{ $client->location }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.zip') }}
                                    </th>
                                    <td>
                                        {{ $client->zip }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.phone') }}
                                    </th>
                                    <td>
                                        {{ $client->phone }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.email') }}
                                    </th>
                                    <td>
                                        {{ $client->email }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.country') }}
                                    </th>
                                    <td>
                                        {{ $client->country->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_name') }}
                                    </th>
                                    <td>
                                        {{ $client->company_name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_vat') }}
                                    </th>
                                    <td>
                                        {{ $client->company_vat }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_address') }}
                                    </th>
                                    <td>
                                        {{ $client->company_address }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_location') }}
                                    </th>
                                    <td>
                                        {{ $client->company_location }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_zip') }}
                                    </th>
                                    <td>
                                        {{ $client->company_zip }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_phone') }}
                                    </th>
                                    <td>
                                        {{ $client->company_phone }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_email') }}
                                    </th>
                                    <td>
                                        {{ $client->company_email }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.company_country') }}
                                    </th>
                                    <td>
                                        {{ $client->company_country->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.client.fields.created_at') }}
                                    </th>
                                    <td>
                                        {{ $client->created_at }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.clients.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @can('client_ledger_entry_access')
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Conta corrente
                        @can('client_ledger_entry_create')
                            <a class="btn btn-xs btn-success pull-right" href="{{ route('admin.client-ledger-entries.create', ['client_id' => $client->id]) }}">
                                Adicionar movimento
                            </a>
                        @endcan
                    </div>
                    <div class="panel-body">
                        @if($ledgerEntries->isEmpty())
                            <p class="text-muted">Nenhum movimento registado.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Tipo</th>
                                            <th>Descricao</th>
                                            <th>Viatura</th>
                                            <th class="text-right">Valor</th>
                                            <th>&nbsp;</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ledgerEntries as $entry)
                                            <tr>
                                                <td>{{ $entry->entry_date }}</td>
                                                <td>{{ $entry->entry_type === 'debit' ? 'Debito' : 'Credito' }}</td>
                                                <td>{{ $entry->description }}</td>
                                                <td>{{ $entry->vehicle->license ?? '-' }}</td>
                                                <td class="text-right">€{{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                                                <td>
                                                    @can('client_ledger_entry_show')
                                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.client-ledger-entries.show', $entry->id) }}">
                                                            {{ trans('global.view') }}
                                                        </a>
                                                    @endcan
                                                    @can('client_ledger_entry_edit')
                                                        <a class="btn btn-xs btn-info" href="{{ route('admin.client-ledger-entries.edit', $entry->id) }}">
                                                            {{ trans('global.edit') }}
                                                        </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-3">
                                <div class="well well-sm text-center">
                                    <div><strong>Total debitos</strong></div>
                                    <div class="lead">€{{ number_format($ledgerTotalDebits, 2, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="well well-sm text-center">
                                    <div><strong>Total creditos</strong></div>
                                    <div class="lead">€{{ number_format($ledgerTotalCredits, 2, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="well well-sm text-center">
                                    <div><strong>Saldo</strong></div>
                                    <div class="lead">€{{ number_format($ledgerBalance, 2, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="well well-sm text-center">
                                    <div><strong>Em falta</strong></div>
                                    <div class="lead">€{{ number_format($ledgerOutstanding, 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan



        </div>
    </div>
</div>
@endsection

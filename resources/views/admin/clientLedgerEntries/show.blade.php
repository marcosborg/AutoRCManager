@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.clientLedgerEntry.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.client-ledger-entries.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.client') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->client->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.vehicle') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->vehicle->license ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.entry_type') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->entry_type === 'debit' ? 'Debito' : 'Credito' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.amount') }}
                                    </th>
                                    <td>
                                        {{ number_format((float) $clientLedgerEntry->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.entry_date') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->entry_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.description') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->description }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.notes') }}
                                    </th>
                                    <td>
                                        {{ $clientLedgerEntry->notes }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.attachment') }}
                                    </th>
                                    <td>
                                        @foreach($clientLedgerEntry->attachment as $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                                        @endforeach
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.client-ledger-entries.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

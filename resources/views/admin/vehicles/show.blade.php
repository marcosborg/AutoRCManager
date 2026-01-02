@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.vehicle.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicles.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.general_state') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->general_state->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.license') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->license }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.foreign_license') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->foreign_license }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.brand') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->brand->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.model') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->model }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.version') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->version }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.transmission') }}
                                    </th>
                                    <td>
                                        {{ App\Models\Vehicle::TRANSMISSION_SELECT[$vehicle->transmission] ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.year') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->year }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.month') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->month }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.license_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->license_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.color') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->color }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.fuel') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->fuel }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.kilometers') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->kilometers }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.inspec_b') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->inspec_b }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.purchase_and_sale_agreement') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->purchase_and_sale_agreement ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.copy_of_the_citizen_card') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->copy_of_the_citizen_card ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.tax_identification_card') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->tax_identification_card ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.copy_of_the_stamp_duty_receipt') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->copy_of_the_stamp_duty_receipt ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.vehicle_registration_document') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->vehicle_registration_document ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.vehicle_ownership_title') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->vehicle_ownership_title ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.vehicle_keys') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->vehicle_keys ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.vehicle_manuals') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->vehicle_manuals ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.release_of_reservation_or_mortgage') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->release_of_reservation_or_mortgage ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.leasing_agreement') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->leasing_agreement ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.cables') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $vehicle->cables ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.documents') }}
                                    </th>
                                    <td>
                                        @foreach($vehicle->documents as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.pending') }}
                                    </th>
                                    <td>
                                        {!! $vehicle->pending !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.additional_items') }}
                                    </th>
                                    <td>
                                        {!! $vehicle->additional_items !!}
                                    </td>
                                </tr>
                                @can('financial_sensitive_access')
                                    <tr>
                                        <th>
                                            {{ trans('cruds.vehicle.fields.purchase_price') }}
                                        </th>
                                        <td>
                                            {{ $vehicle->purchase_price }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            {{ trans('cruds.vehicle.fields.purchase_has_vat') }}
                                        </th>
                                        <td>
                                            <span class="label label-{{ $vehicle->purchase_has_vat ? 'success' : 'default' }}">
                                                {{ $vehicle->purchase_has_vat ? __('global.yes') : __('global.no') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            {{ trans('cruds.vehicle.fields.purchase_vat_value') }}
                                        </th>
                                        <td>
                                            {{ $vehicle->purchase_vat_value }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            Notas da aquisicao
                                        </th>
                                        <td>
                                            {{ $vehicle->acquisition_notes }}
                                        </td>
                                    </tr>
                                @endcan
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.photos') }}
                                    </th>
                                    <td>
                                        @foreach($vehicle->photos as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank" style="display: inline-block">
                                                <img src="{{ $media->getUrl('thumb') }}">
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.suplier') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->suplier->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.payment_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->payment_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.invoice') }}
                                    </th>
                                    <td>
                                        @foreach($vehicle->invoice as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.inicial') }}
                                    </th>
                                    <td>
                                        @foreach($vehicle->inicial as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank" style="display: inline-block">
                                                <img src="{{ $media->getUrl('thumb') }}">
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.payment_status') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->payment_status->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.amount_paid') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->amount_paid }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.carrier') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->carrier->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.storage_location') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->storage_location }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.withdrawal_authorization') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->withdrawal_authorization }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.withdrawal_authorization_file') }}
                                    </th>
                                    <td>
                                        @foreach($vehicle->withdrawal_authorization_file as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.withdrawal_authorization_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->withdrawal_authorization_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.withdrawal_documents') }}
                                    </th>
                                    <td>
                                        @foreach($vehicle->withdrawal_documents as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.pickup_state') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->pickup_state->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.pickup_state_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->pickup_state_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.total_price') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->total_price }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.minimum_price') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->minimum_price }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.pvp') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->pvp }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.client') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->client->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.client_amount_paid') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->client_amount_paid }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.client_registration') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->client_registration }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.chekin_documents') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->chekin_documents }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.chekin_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->chekin_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.sale_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->sale_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.sele_chekout') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->sele_chekout }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.first_key') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->first_key }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.scuts') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->scuts }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.key') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->key }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.manuals') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->manuals }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.elements_with_vehicle') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->elements_with_vehicle }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.sale_notes') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->sale_notes }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicle.fields.local') }}
                                    </th>
                                    <td>
                                        {{ $vehicle->local }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        @if($showWorkshopSection)
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    Oficina
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5><strong>Checklist</strong></h5>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->vehicle_manuals ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.vehicle_manuals') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->vehicle_keys ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.vehicle_keys') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->cables ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.cables') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->cables_2 ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.cables_2') }}
                                                </label>
                                            </div>
                                            <div>
                                                <strong>{{ trans('cruds.vehicle.fields.first_key') }}:</strong> {{ $vehicle->first_key ?? '-' }}
                                            </div>
                                            <div>
                                                <strong>{{ trans('cruds.vehicle.fields.key') }}:</strong> {{ $vehicle->key ?? '-' }}
                                            </div>
                                            <div>
                                                <strong>{{ trans('cruds.vehicle.fields.manuals') }}:</strong> {{ $vehicle->manuals ?? '-' }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5><strong>Documentos</strong></h5>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->vehicle_registration_document ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.vehicle_registration_document') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->vehicle_ownership_title ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.vehicle_ownership_title') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->tax_identification_card ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.tax_identification_card') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->copy_of_the_citizen_card ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.copy_of_the_citizen_card') }}
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->copy_of_the_stamp_duty_receipt ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.copy_of_the_stamp_duty_receipt') }}
                                                </label>
                                            </div>
                                            <div style="margin-top: 10px;">
                                                @foreach($vehicle->documents as $media)
                                                    <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                                                @endforeach
                                            </div>
                                            <div style="margin-top: 5px;">
                                                @foreach($vehicle->pdfs as $media)
                                                    <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <h5><strong>Fotos a chegada</strong></h5>
                                    <div>
                                        @foreach($vehicle->inicial as $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank" style="display: inline-block; margin-right: 5px;">
                                                <img src="{{ $media->getUrl('thumb') }}">
                                            </a>
                                        @endforeach
                                    </div>
                                    <hr>
                                    <h5><strong>Notas de venda</strong></h5>
                                    <div>
                                        {!! $vehicle->sale_notes !!}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @can('vehicle_financial_entry_access')
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    Financeiro detalhado
                                    @can('vehicle_financial_entry_create')
                                        <a class="btn btn-xs btn-success pull-right" href="{{ route('admin.vehicle-financial-entries.create', ['vehicle_id' => $vehicle->id]) }}">
                                            Adicionar linha
                                        </a>
                                    @endcan
                                </div>
                                <div class="panel-body">
                                    @if($financialEntries->isEmpty())
                                        <p class="text-muted">Nenhuma linha financeira registada.</p>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Tipo</th>
                                                        <th>Categoria</th>
                                                        <th class="text-right">Valor</th>
                                                        <th>Notas</th>
                                                        <th>&nbsp;</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($financialEntries as $entry)
                                                        <tr>
                                                            <td>{{ $entry->entry_date }}</td>
                                                            <td>{{ $entry->entry_type === 'cost' ? 'Custo' : 'Receita' }}</td>
                                                            <td>{{ $entry->category }}</td>
                                                            <td class="text-right">€{{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
                                                            <td>{{ $entry->notes }}</td>
                                                            <td>
                                                                @can('vehicle_financial_entry_show')
                                                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicle-financial-entries.show', $entry->id) }}">
                                                                        {{ trans('global.view') }}
                                                                    </a>
                                                                @endcan
                                                                @can('vehicle_financial_entry_edit')
                                                                    <a class="btn btn-xs btn-info" href="{{ route('admin.vehicle-financial-entries.edit', $entry->id) }}">
                                                                        {{ trans('global.edit') }}
                                                                    </a>
                                                                @endcan
                                                                @can('vehicle_financial_entry_delete')
                                                                    <form action="{{ route('admin.vehicle-financial-entries.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                                        <input type="hidden" name="_method" value="DELETE">
                                                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                        <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                                                    </form>
                                                                @endcan
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="well well-sm text-center">
                                                <div><strong>Total custos</strong></div>
                                                <div class="lead">€{{ number_format($financialTotalCost, 2, ',', '.') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="well well-sm text-center">
                                                <div><strong>Total receitas</strong></div>
                                                <div class="lead">€{{ number_format($financialTotalRevenue, 2, ',', '.') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="well well-sm text-center">
                                                <div><strong>Balanço</strong></div>
                                                <div class="lead">€{{ number_format($financialBalance, 2, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcan
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicles.index') }}">
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

@extends('layouts.admin')
@section('styles')
<style>
    .floating-save-btn {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 1100;
        padding: 12px 18px;
        border-radius: 999px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
    }

    @media (max-width: 767px) {
        .floating-save-btn {
            right: 14px;
            bottom: 14px;
            width: calc(100% - 28px);
            border-radius: 8px;
        }
    }

    .floating-save-btn.is-loading {
        opacity: 0.7;
        pointer-events: none;
    }
</style>
@endsection
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.vehicle.title_singular') }}
                </div>
                <div class="panel-body">
                    <form id="vehicle-edit-form" method="POST" action="{{ route('admin.vehicles.update', [$vehicle->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('general_state') ? 'has-error' : '' }}">
                                    <label class="required" for="general_state_id">{{ trans('cruds.vehicle.fields.general_state') }}</label>
                                    <select class="form-control select2" name="general_state_id" id="general_state_id" required>
                                        @foreach($general_states as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('general_state_id') ? old('general_state_id') : $vehicle->general_state->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('general_state'))
                                    <span class="help-block" role="alert">{{ $errors->first('general_state') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.general_state_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                                    <label for="license">{{ trans('cruds.vehicle.fields.license') }}</label>
                                    <input class="form-control" type="text" name="license" id="license" value="{{ old('license', $vehicle->license) }}">
                                    @if($errors->has('license'))
                                        <span class="help-block" role="alert">{{ $errors->first('license') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.license_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('foreign_license') ? 'has-error' : '' }}">
                                    <label for="foreign_license">{{ trans('cruds.vehicle.fields.foreign_license') }}</label>
                                    <input class="form-control" type="text" name="foreign_license" id="foreign_license" value="{{ old('foreign_license', $vehicle->foreign_license) }}">
                                    @if($errors->has('foreign_license'))
                                        <span class="help-block" role="alert">{{ $errors->first('foreign_license') }}</span>
                                    @endif
                                <span class="help-block">{{ trans('cruds.vehicle.fields.foreign_license_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('brand') ? 'has-error' : '' }}">
                                    <label for="brand_id">{{ trans('cruds.vehicle.fields.brand') }}</label>
                                    <select class="form-control select2" name="brand_id" id="brand_id">
                                        @foreach($brands as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('brand_id') ? old('brand_id') : $vehicle->brand->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('brand'))
                                        <span class="help-block" role="alert">{{ $errors->first('brand') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.brand_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('model') ? 'has-error' : '' }}">
                                    <label for="model">{{ trans('cruds.vehicle.fields.model') }}</label>
                                    <input class="form-control" type="text" name="model" id="model" value="{{ old('model', $vehicle->model) }}">
                                    @if($errors->has('model'))
                                        <span class="help-block" role="alert">{{ $errors->first('model') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.model_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('version') ? 'has-error' : '' }}">
                                    <label for="version">{{ trans('cruds.vehicle.fields.version') }}</label>
                                    <input class="form-control" type="text" name="version" id="version" value="{{ old('version', $vehicle->version) }}">
                                    @if($errors->has('version'))
                                        <span class="help-block" role="alert">{{ $errors->first('version') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.version_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('transmission') ? 'has-error' : '' }}">
                                    <label>{{ trans('cruds.vehicle.fields.transmission') }}</label>
                                    <select class="form-control" name="transmission" id="transmission">
                                        <option value disabled {{ old('transmission', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                                        @foreach(App\Models\Vehicle::TRANSMISSION_SELECT as $key => $label)
                                            <option value="{{ $key }}" {{ old('transmission', $vehicle->transmission) === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('transmission'))
                                        <span class="help-block" role="alert">{{ $errors->first('transmission') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.transmission_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('engine_displacement') ? 'has-error' : '' }}">
                                    <label for="engine_displacement">{{ trans('cruds.vehicle.fields.engine_displacement') }}</label>
                                    <input class="form-control" type="text" name="engine_displacement" id="engine_displacement" value="{{ old('engine_displacement', $vehicle->engine_displacement) }}">
                                    @if($errors->has('engine_displacement'))
                                        <span class="help-block" role="alert">{{ $errors->first('engine_displacement') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.engine_displacement_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('year') ? 'has-error' : '' }}">
                                    <label for="year">{{ trans('cruds.vehicle.fields.year') }}</label>
                                    <input class="form-control" type="number" name="year" id="year" value="{{ old('year', $vehicle->year) }}" step="1">
                                    @if($errors->has('year'))
                                        <span class="help-block" role="alert">{{ $errors->first('year') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.year_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('month') ? 'has-error' : '' }}">
                                    <label for="month">{{ trans('cruds.vehicle.fields.month') }}</label>
                                    <input class="form-control" type="text" name="month" id="month" value="{{ old('month', $vehicle->month) }}">
                                    @if($errors->has('month'))
                                        <span class="help-block" role="alert">{{ $errors->first('month') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.month_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('license_date') ? 'has-error' : '' }}">
                                    <label for="license_date">{{ trans('cruds.vehicle.fields.license_date') }}</label>
                                    <input class="form-control date" type="text" name="license_date" id="license_date" value="{{ old('license_date', $vehicle->license_date) }}">
                                    @if($errors->has('license_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('license_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.license_date_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('color') ? 'has-error' : '' }}">
                                    <label for="color">{{ trans('cruds.vehicle.fields.color') }}</label>
                                    <input class="form-control" type="text" name="color" id="color" value="{{ old('color', $vehicle->color) }}">
                                    @if($errors->has('color'))
                                        <span class="help-block" role="alert">{{ $errors->first('color') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.color_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('fuel') ? 'has-error' : '' }}">
                                    <label for="fuel">{{ trans('cruds.vehicle.fields.fuel') }}</label>
                                    <input class="form-control" type="text" name="fuel" id="fuel" value="{{ old('fuel', $vehicle->fuel) }}">
                                    @if($errors->has('fuel'))
                                        <span class="help-block" role="alert">{{ $errors->first('fuel') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.fuel_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                                    <label for="kilometers">{{ trans('cruds.vehicle.fields.kilometers') }}</label>
                                    <input class="form-control" type="number" name="kilometers" id="kilometers" value="{{ old('kilometers', $vehicle->kilometers) }}" step="1">
                                    @if($errors->has('kilometers'))
                                        <span class="help-block" role="alert">{{ $errors->first('kilometers') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.kilometers_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('inspec_b') ? 'has-error' : '' }}">
                                    <label for="inspec_b">{{ trans('cruds.vehicle.fields.inspec_b') }}</label>
                                    <input class="form-control" type="text" name="inspec_b" id="inspec_b" value="{{ old('inspec_b', $vehicle->inspec_b) }}">
                                    @if($errors->has('inspec_b'))
                                        <span class="help-block" role="alert">{{ $errors->first('inspec_b') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.inspec_b_helper') }}</span>
                                </div>
                            </div>
                        </div>

                        @if(
                            auth()->user()->can('financial_sensitive_access')
                            || auth()->user()->can('suplier_access')
                            || auth()->user()->can('suplier_show')
                            || auth()->user()->can('suplier_edit')
                        )
                            <h4>Aquisição da viatura</h4>
                            <hr>
                        @endif
                        <div class="row">
                            @can('financial_sensitive_access')
                            <div class="col-md-3">
                                @can('superadmin')
                                <div class="form-group {{ $errors->has('purchase_price') ? 'has-error' : '' }}">
                                    <label for="purchase_price">{{ trans('cruds.vehicle.fields.purchase_price') }}</label>
                                    <input class="form-control" type="number" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $vehicle->purchase_price) }}" step="0.01">
                                    @if($errors->has('purchase_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('purchase_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.purchase_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('purchase_has_vat') ? 'has-error' : '' }}">
                                    <div class="checkbox">
                                        <label for="purchase_has_vat" class="required">
                                            <input type="hidden" name="purchase_has_vat" value="0">
                                            <input type="checkbox" name="purchase_has_vat" id="purchase_has_vat" value="1" {{ old('purchase_has_vat', $vehicle->purchase_has_vat) ? 'checked' : '' }}>
                                            {{ trans('cruds.vehicle.fields.purchase_has_vat') }}
                                        </label>
                                    </div>
                                    @if($errors->has('purchase_has_vat'))
                                        <span class="help-block" role="alert">{{ $errors->first('purchase_has_vat') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.purchase_has_vat_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('purchase_vat_value') ? 'has-error' : '' }}">
                                    <label for="purchase_vat_value">{{ trans('cruds.vehicle.fields.purchase_vat_value') }}</label>
                                    <input class="form-control" type="number" name="purchase_vat_value" id="purchase_vat_value" value="{{ old('purchase_vat_value', $vehicle->purchase_vat_value) }}" step="0.01">
                                    @if($errors->has('purchase_vat_value'))
                                        <span class="help-block" role="alert">{{ $errors->first('purchase_vat_value') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.purchase_vat_value_helper') }}</span>
                                </div>
                                @endcan
                                <div class="form-group {{ $errors->has('iuc_price') ? 'has-error' : '' }}">
                                    <label for="iuc_price">{{ trans('cruds.vehicle.fields.iuc_price') }}</label>
                                    <input class="form-control" type="number" name="iuc_price" id="iuc_price" value="{{ old('iuc_price', $vehicle->iuc_price) }}" step="0.01">
                                    @if($errors->has('iuc_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('iuc_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.iuc_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('commission') ? 'has-error' : '' }}">
                                    <label for="commission">{{ trans('cruds.vehicle.fields.commission') }}</label>
                                    <input class="form-control" type="number" name="commission" id="commission" value="{{ old('commission', $vehicle->commission) }}" step="0.01">
                                    @if($errors->has('commission'))
                                        <span class="help-block" role="alert">{{ $errors->first('commission') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.commission_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('tow_price') ? 'has-error' : '' }}">
                                    <label for="tow_price">{{ trans('cruds.vehicle.fields.tow_price') }}</label>
                                    <input class="form-control" type="number" name="tow_price" id="tow_price" value="{{ old('tow_price', $vehicle->tow_price) }}" step="0.01">
                                    @if($errors->has('tow_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('tow_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.tow_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('acquisition_notes') ? 'has-error' : '' }}">
                                    <label for="acquisition_notes">Notas da aquisicao</label>
                                    <textarea class="form-control" name="acquisition_notes" id="acquisition_notes">{{ old('acquisition_notes', $vehicle->acquisition_notes) }}</textarea>
                                    @if($errors->has('acquisition_notes'))
                                        <span class="help-block" role="alert">{{ $errors->first('acquisition_notes') }}</span>
                                    @endif
                                    <span class="help-block">Notas internas da aquisicao.</span>
                                </div>
                                <div class="panel panel-default" id="acquisition-expenses-panel">
                                    <div class="panel-body" style="padding: 10px;">
                                        <strong>Total despesas aquisicao:</strong>
                                        <span id="acquisition-expenses-total" data-base-total="{{ (float) ($acquisitionExpensesTotal ?? 0) }}">
                                            {{ number_format((float) ($acquisitionExpensesTotal ?? 0), 2, ',', '.') }}
                                        </span>
                                        EUR
                                        <div class="text-muted" style="margin-top: 6px;">
                                            Inclui compra, IVA da compra, IUC, comissoes, reboque e despesas livres registadas.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('suplier') ? 'has-error' : '' }}">
                                    <label for="suplier_id">{{ trans('cruds.vehicle.fields.suplier') }}</label>
                                    <select class="form-control select2" name="suplier_id" id="suplier_id">
                                        @foreach($supliers as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('suplier_id') ? old('suplier_id') : $vehicle->suplier->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('suplier'))
                                        <span class="help-block" role="alert">{{ $errors->first('suplier') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.suplier_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('payment_status') ? 'has-error' : '' }}">
                                    <label for="payment_status_id">{{ trans('cruds.vehicle.fields.payment_status') }}</label>
                                    <select class="form-control select2" name="payment_status_id" id="payment_status_id">
                                        @foreach($payment_statuses as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('payment_status_id') ? old('payment_status_id') : $vehicle->payment_status->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('payment_status'))
                                        <span class="help-block" role="alert">{{ $errors->first('payment_status') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.payment_status_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('payment_date') ? 'has-error' : '' }}">
                                    <label for="payment_date">{{ trans('cruds.vehicle.fields.payment_date') }}</label>
                                    <input class="form-control date" type="text" name="payment_date" id="payment_date" value="{{ old('payment_date', $vehicle->payment_date) }}">
                                    @if($errors->has('payment_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('payment_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.payment_date_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('supplier_payment_date') ? 'has-error' : '' }}">
                                    <label for="supplier_payment_date">Data pagamento fornecedor</label>
                                    <input class="form-control date" type="text" name="supplier_payment_date" id="supplier_payment_date" value="{{ old('supplier_payment_date') }}">
                                    @if($errors->has('supplier_payment_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('supplier_payment_date') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('supplier_payment_amount') ? 'has-error' : '' }}">
                                    <label for="supplier_payment_amount">Valor pagamento fornecedor</label>
                                    <input class="form-control" type="number" name="supplier_payment_amount" id="supplier_payment_amount" value="{{ old('supplier_payment_amount') }}" step="0.01" min="0.01">
                                    @if($errors->has('supplier_payment_amount'))
                                        <span class="help-block" role="alert">{{ $errors->first('supplier_payment_amount') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('supplier_payment_method_id') ? 'has-error' : '' }}">
                                    <label for="supplier_payment_method_id">Meio de pagamento fornecedor</label>
                                    <select class="form-control select2" name="supplier_payment_method_id" id="supplier_payment_method_id">
                                        @foreach($payment_methods as $id => $entry)
                                            <option value="{{ $id }}" {{ old('supplier_payment_method_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('supplier_payment_method_id'))
                                        <span class="help-block" role="alert">{{ $errors->first('supplier_payment_method_id') }}</span>
                                    @endif
                                </div>
                                <div class="panel panel-default" id="supplier-payments-panel">
                                    <div class="panel-heading">Pagamentos ao fornecedor</div>
                                    <div class="panel-body" style="padding: 10px;">
                                        <div style="margin-bottom: 8px;">
                                            <strong>Compra:</strong> {{ number_format((float) ($vehicle->purchase_price ?? 0), 2, ',', '.') }} EUR
                                            <br>
                                            <strong>Total pago:</strong>
                                            <span id="supplier-payments-total" data-base-total="{{ (float) ($supplierPaymentsTotal ?? 0) }}">
                                                {{ number_format((float) ($supplierPaymentsTotal ?? 0), 2, ',', '.') }}
                                            </span>
                                            EUR
                                            <br>
                                            <strong>Em divida:</strong> {{ number_format((float) ($supplierPaymentsOutstanding ?? 0), 2, ',', '.') }} EUR
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-condensed">
                                                <thead>
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Valor</th>
                                                        <th>Meio</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(($supplierPayments ?? collect()) as $supplierPayment)
                                                        <tr>
                                                            <td>{{ $supplierPayment->paid_at }}</td>
                                                            <td>{{ number_format((float) $supplierPayment->amount, 2, ',', '.') }} EUR</td>
                                                            <td>{{ $supplierPayment->payment_method->name ?? '-' }}</td>
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-xs btn-danger js-delete-payment"
                                                                    data-delete-url="{{ route('admin.vehicles.supplier-payments.destroy', [$vehicle->id, $supplierPayment->id]) }}"
                                                                >
                                                                    Eliminar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4">Sem pagamentos registados.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group {{ $errors->has('generic_payment_expense_label') ? 'has-error' : '' }}">
                                    <label for="generic_payment_expense_label">Despesa (livre)</label>
                                    <input class="form-control" type="text" name="generic_payment_expense_label" id="generic_payment_expense_label" value="{{ old('generic_payment_expense_label') }}">
                                    @if($errors->has('generic_payment_expense_label'))
                                        <span class="help-block" role="alert">{{ $errors->first('generic_payment_expense_label') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('generic_payment_date') ? 'has-error' : '' }}">
                                    <label for="generic_payment_date">Data pagamento</label>
                                    <input class="form-control date" type="text" name="generic_payment_date" id="generic_payment_date" value="{{ old('generic_payment_date') }}">
                                    @if($errors->has('generic_payment_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('generic_payment_date') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('generic_payment_amount') ? 'has-error' : '' }}">
                                    <label for="generic_payment_amount">Valor despesa</label>
                                    <input class="form-control" type="number" name="generic_payment_amount" id="generic_payment_amount" value="{{ old('generic_payment_amount') }}" step="0.01" min="0.01">
                                    @if($errors->has('generic_payment_amount'))
                                        <span class="help-block" role="alert">{{ $errors->first('generic_payment_amount') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('generic_payment_method_id') ? 'has-error' : '' }}">
                                    <label for="generic_payment_method_id">Meio de pagamento</label>
                                    <select class="form-control select2" name="generic_payment_method_id" id="generic_payment_method_id">
                                        @foreach($payment_methods as $id => $entry)
                                            <option value="{{ $id }}" {{ old('generic_payment_method_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('generic_payment_method_id'))
                                        <span class="help-block" role="alert">{{ $errors->first('generic_payment_method_id') }}</span>
                                    @endif
                                </div>
                                <div class="panel panel-default" id="generic-payments-panel">
                                    <div class="panel-heading">Pagamentos genericos da viatura</div>
                                    <div class="panel-body" style="padding: 10px;">
                                        <div style="margin-bottom: 8px;">
                                            <strong>Total acumulado:</strong>
                                            <span id="generic-payments-total" data-base-total="{{ (float) ($genericPaymentsTotal ?? 0) }}">
                                                {{ number_format((float) ($genericPaymentsTotal ?? 0), 2, ',', '.') }}
                                            </span>
                                            EUR
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-condensed">
                                                <thead>
                                                    <tr>
                                                        <th>Despesa</th>
                                                        <th>Data</th>
                                                        <th>Valor</th>
                                                        <th>Meio</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(($genericPayments ?? collect()) as $genericPayment)
                                                        <tr>
                                                            <td>{{ $genericPayment->expense_label }}</td>
                                                            <td>{{ $genericPayment->paid_at }}</td>
                                                            <td>{{ number_format((float) $genericPayment->amount, 2, ',', '.') }} EUR</td>
                                                            <td>{{ $genericPayment->payment_method->name ?? '-' }}</td>
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-xs btn-danger js-delete-payment"
                                                                    data-delete-url="{{ route('admin.vehicles.generic-payments.destroy', [$vehicle->id, $genericPayment->id]) }}"
                                                                >
                                                                    Eliminar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5">Sem pagamentos genericos registados.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
@endcan
                            @cannot('financial_sensitive_access')
                                @canany(['suplier_access', 'suplier_show', 'suplier_edit'])
                                    <div class="col-md-3">
                                        <div class="form-group {{ $errors->has('suplier') ? 'has-error' : '' }}">
                                            <label for="suplier_id">{{ trans('cruds.vehicle.fields.suplier') }}</label>
                                            <select class="form-control select2" name="suplier_id" id="suplier_id">
                                                @foreach($supliers as $id => $entry)
                                                    <option value="{{ $id }}" {{ (old('suplier_id') ? old('suplier_id') : $vehicle->suplier->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                                @endforeach
                                            </select>
                                            @if($errors->has('suplier'))
                                                <span class="help-block" role="alert">{{ $errors->first('suplier') }}</span>
                                            @endif
                                            <span class="help-block">{{ trans('cruds.vehicle.fields.suplier_helper') }}</span>
                                        </div>
                                    </div>
                                @endcanany
                            @endcannot
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('inicial') ? 'has-error' : '' }}">
                                    <label for="inicial">{{ trans('cruds.vehicle.fields.inicial') }}</label>
                                    <div class="needsclick dropzone" id="inicial-dropzone">
                                    </div>
                                    @if($errors->has('inicial'))
                                        <span class="help-block" role="alert">{{ $errors->first('inicial') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.inicial_helper') }}</span>
                                </div>
                                @can('financial_sensitive_access')
                                <div class="form-group {{ $errors->has('invoice') ? 'has-error' : '' }}">
                                    <label for="invoice">{{ trans('cruds.vehicle.fields.invoice') }}</label>
                                    <div class="needsclick dropzone" id="invoice-dropzone">
                                    </div>
                                    @if($errors->has('invoice'))
                                        <span class="help-block" role="alert">{{ $errors->first('invoice') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.invoice_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('pdfs') ? 'has-error' : '' }}">
                                    <label for="pdfs">{{ trans('cruds.vehicle.fields.pdfs') }}</label>
                                    <div class="needsclick dropzone" id="pdfs-dropzone">
                                    </div>
                                    @if($errors->has('pdfs'))
                                        <span class="help-block" role="alert">{{ $errors->first('pdfs') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pdfs_helper') }}</span>
                                </div>
                                @endcan
                            </div>
                        </div>

                        @php
                            $hasAllDocuments = (int) ($vehicle->purchase_and_sale_agreement ?? 0) === 1
                                && (int) ($vehicle->copy_of_the_citizen_card ?? 0) === 1
                                && (int) ($vehicle->tax_identification_card ?? 0) === 1
                                && (int) ($vehicle->copy_of_the_stamp_duty_receipt ?? 0) === 1
                                && (int) ($vehicle->vehicle_ownership_title ?? 0) === 1
                                && (int) ($vehicle->release_of_reservation_or_mortgage ?? 0) === 1
                                && (int) ($vehicle->leasing_agreement ?? 0) === 1;
                        @endphp

                        @can('vehicle_documents_area_access')
                            <h4>Documentos da viatura</h4>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox" style="margin-top: 0;">
                                        <label for="has_all_documents_toggle" style="font-weight: 600;">
                                            <input type="checkbox" id="has_all_documents_toggle" {{ $hasAllDocuments ? 'checked' : '' }}>
                                            Possui todos os documentos
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('purchase_and_sale_agreement') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="purchase_and_sale_agreement" value="0">
                                            <input type="checkbox" name="purchase_and_sale_agreement" id="purchase_and_sale_agreement" value="1" {{ $vehicle->purchase_and_sale_agreement || old('purchase_and_sale_agreement', 0) == 1 ? 'checked' : '' }}>
                                            <label for="purchase_and_sale_agreement" style="font-weight: 400">{{ trans('cruds.vehicle.fields.purchase_and_sale_agreement') }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('copy_of_the_citizen_card') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="copy_of_the_citizen_card" value="0">
                                            <input type="checkbox" name="copy_of_the_citizen_card" id="copy_of_the_citizen_card" value="1" {{ $vehicle->copy_of_the_citizen_card || old('copy_of_the_citizen_card', 0) == 1 ? 'checked' : '' }}>
                                            <label for="copy_of_the_citizen_card" style="font-weight: 400">{{ trans('cruds.vehicle.fields.copy_of_the_citizen_card') }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('tax_identification_card') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="tax_identification_card" value="0">
                                            <input type="checkbox" name="tax_identification_card" id="tax_identification_card" value="1" {{ $vehicle->tax_identification_card || old('tax_identification_card', 0) == 1 ? 'checked' : '' }}>
                                            <label for="tax_identification_card" style="font-weight: 400">{{ trans('cruds.vehicle.fields.tax_identification_card') }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('copy_of_the_stamp_duty_receipt') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="copy_of_the_stamp_duty_receipt" value="0">
                                            <input type="checkbox" name="copy_of_the_stamp_duty_receipt" id="copy_of_the_stamp_duty_receipt" value="1" {{ $vehicle->copy_of_the_stamp_duty_receipt || old('copy_of_the_stamp_duty_receipt', 0) == 1 ? 'checked' : '' }}>
                                            <label for="copy_of_the_stamp_duty_receipt" style="font-weight: 400">{{ trans('cruds.vehicle.fields.copy_of_the_stamp_duty_receipt') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('vehicle_ownership_title') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="vehicle_ownership_title" value="0">
                                            <input type="checkbox" name="vehicle_ownership_title" id="vehicle_ownership_title" value="1" {{ $vehicle->vehicle_ownership_title || old('vehicle_ownership_title', 0) == 1 ? 'checked' : '' }}>
                                            <label for="vehicle_ownership_title" style="font-weight: 400">DUA</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('release_of_reservation_or_mortgage') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="release_of_reservation_or_mortgage" value="0">
                                            <input type="checkbox" name="release_of_reservation_or_mortgage" id="release_of_reservation_or_mortgage" value="1" {{ $vehicle->release_of_reservation_or_mortgage || old('release_of_reservation_or_mortgage', 0) == 1 ? 'checked' : '' }}>
                                            <label for="release_of_reservation_or_mortgage" style="font-weight: 400">Extinção Reserva / Hipotéca</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('leasing_agreement') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="leasing_agreement" value="0">
                                            <input type="checkbox" name="leasing_agreement" id="leasing_agreement" value="1" {{ $vehicle->leasing_agreement || old('leasing_agreement', 0) == 1 ? 'checked' : '' }}>
                                            <label for="leasing_agreement" style="font-weight: 400">{{ trans('cruds.vehicle.fields.leasing_agreement') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('documents') ? 'has-error' : '' }}">
                                        <label for="documents">DUA / Inspeção</label>
                                        <div class="needsclick dropzone" id="documents-dropzone"></div>
                                    </div>
                                    <div class="form-group {{ $errors->has('additional_items') ? 'has-error' : '' }}">
                                        <label for="additional_items">Outros documentos</label>
                                        <textarea class="form-control ckeditor" name="additional_items" id="additional_items">{!! old('additional_items', $vehicle->additional_items) !!}</textarea>
                                        @if($errors->has('additional_items'))
                                            <span class="help-block" role="alert">{{ $errors->first('additional_items') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endcan

                        @can('vehicle_others_area_access')
                            <h4>Outros</h4>
                            <hr>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('elements_with_vehicle') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="elements_with_vehicle" value="0">
                                            <input type="checkbox" name="elements_with_vehicle" id="elements_with_vehicle" value="1" {{ (string) old('elements_with_vehicle', $vehicle->elements_with_vehicle) === '1' ? 'checked' : '' }}>
                                            <label for="elements_with_vehicle" style="font-weight: 400">Acessórios</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('first_key') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="first_key" value="0">
                                            <input type="checkbox" name="first_key" id="first_key" value="1" {{ (string) old('first_key', $vehicle->first_key) === '1' ? 'checked' : '' }}>
                                            <label for="first_key" style="font-weight: 400">1.ª chave</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('key') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="key" value="0">
                                            <input type="checkbox" name="key" id="key" value="1" {{ (string) old('key', $vehicle->key) === '1' ? 'checked' : '' }}>
                                            <label for="key" style="font-weight: 400">2.ª chave</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('vehicle_manuals') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="vehicle_manuals" value="0">
                                            <input type="checkbox" name="vehicle_manuals" id="vehicle_manuals" value="1" {{ $vehicle->vehicle_manuals || old('vehicle_manuals', 0) == 1 ? 'checked' : '' }}>
                                            <label for="vehicle_manuals" style="font-weight: 400">Manuais do veículo</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group {{ $errors->has('cables') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="cables" value="0">
                                            <input type="checkbox" name="cables" id="cables" value="1" {{ $vehicle->cables || old('cables', 0) == 1 ? 'checked' : '' }}>
                                            <label for="cables" style="font-weight: 400">{{ trans('cruds.vehicle.fields.cables') }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('cables_2') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="cables_2" value="0">
                                            <input type="checkbox" name="cables_2" id="cables_2" value="1" {{ $vehicle->cables_2 || old('cables_2', 0) == 1 ? 'checked' : '' }}>
                                            <label for="cables_2" style="font-weight: 400">{{ trans('cruds.vehicle.fields.cables_2') }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('scuts') ? 'has-error' : '' }}">
                                        <div>
                                            <input type="hidden" name="scuts" value="0">
                                            <input type="checkbox" name="scuts" id="scuts" value="1" {{ (string) old('scuts', $vehicle->scuts) === '1' ? 'checked' : '' }}>
                                            <label for="scuts" style="font-weight: 400">Alerta Portagens</label>
                                        </div>
                                    </div>
                                    <div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
                                        <label for="date">{{ trans('cruds.vehicle.fields.date') }}</label>
                                        <input class="form-control date" type="text" name="date" id="date" value="{{ old('date', $vehicle->date) }}">
                                    </div>
                                </div>
                            </div>
                        @endcan

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group {{ $errors->has('pending') ? 'has-error' : '' }}">
                                    <label for="pending">{{ trans('cruds.vehicle.fields.pending') }}</label>
                                    <textarea class="form-control ckeditor" name="pending" id="pending">{!! old('pending', $vehicle->pending) !!}</textarea>
                                    @if($errors->has('pending'))
                                        <span class="help-block" role="alert">{{ $errors->first('pending') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pending_helper') }}</span>
                                </div>
                            </div>
                        </div>

                        <h4>Levantamento da viatura</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('carrier') ? 'has-error' : '' }}">
                                    <label for="carrier_id">{{ trans('cruds.vehicle.fields.carrier') }}</label>
                                    <select class="form-control select2" name="carrier_id" id="carrier_id">
                                        @foreach($carriers as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('carrier_id') ? old('carrier_id') : $vehicle->carrier->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('carrier'))
                                        <span class="help-block" role="alert">{{ $errors->first('carrier') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.carrier_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('storage_location') ? 'has-error' : '' }}">
                                    <label for="storage_location">{{ trans('cruds.vehicle.fields.storage_location') }}</label>
                                    <input class="form-control" type="text" name="storage_location" id="storage_location" value="{{ old('storage_location', $vehicle->storage_location) }}">
                                    @if($errors->has('storage_location'))
                                        <span class="help-block" role="alert">{{ $errors->first('storage_location') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.storage_location_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('withdrawal_authorization') ? 'has-error' : '' }}">
                                    <label for="withdrawal_authorization">{{ trans('cruds.vehicle.fields.withdrawal_authorization') }}</label>
                                    <input class="form-control" type="text" name="withdrawal_authorization" id="withdrawal_authorization" value="{{ old('withdrawal_authorization', $vehicle->withdrawal_authorization) }}">
                                    @if($errors->has('withdrawal_authorization'))
                                        <span class="help-block" role="alert">{{ $errors->first('withdrawal_authorization') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.withdrawal_authorization_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('withdrawal_authorization_date') ? 'has-error' : '' }}">
                                    <label for="withdrawal_authorization_date">{{ trans('cruds.vehicle.fields.withdrawal_authorization_date') }}</label>
                                    <input class="form-control date" type="text" name="withdrawal_authorization_date" id="withdrawal_authorization_date" value="{{ old('withdrawal_authorization_date', $vehicle->withdrawal_authorization_date) }}">
                                    @if($errors->has('withdrawal_authorization_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('withdrawal_authorization_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.withdrawal_authorization_date_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('pickup_state') ? 'has-error' : '' }}">
                                    <label for="pickup_state_id">{{ trans('cruds.vehicle.fields.pickup_state') }}</label>
                                    <select class="form-control select2" name="pickup_state_id" id="pickup_state_id">
                                        @foreach($pickup_states as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('pickup_state_id') ? old('pickup_state_id') : $vehicle->pickup_state->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('pickup_state'))
                                        <span class="help-block" role="alert">{{ $errors->first('pickup_state') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pickup_state_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('pickup_state_date') ? 'has-error' : '' }}">
                                    <label for="pickup_state_date">{{ trans('cruds.vehicle.fields.pickup_state_date') }}</label>
                                    <input class="form-control date" type="text" name="pickup_state_date" id="pickup_state_date" value="{{ old('pickup_state_date', $vehicle->pickup_state_date) }}">
                                    @if($errors->has('pickup_state_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('pickup_state_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pickup_state_date_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('withdrawal_authorization_file') ? 'has-error' : '' }}">
                                    <label for="withdrawal_authorization_file">{{ trans('cruds.vehicle.fields.withdrawal_authorization_file') }}</label>
                                    <div class="needsclick dropzone" id="withdrawal_authorization_file-dropzone">
                                    </div>
                                    @if($errors->has('withdrawal_authorization_file'))
                                        <span class="help-block" role="alert">{{ $errors->first('withdrawal_authorization_file') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.withdrawal_authorization_file_helper') }}</span>
                                </div>
                            </div>
                        </div>
                        @php
                            $pvp = (float) ($vehicle->pvp ?? 0);
                            $sales_iuc = (float) ($vehicle->sales_iuc ?? 0);
                            $sales_tow = (float) ($vehicle->sales_tow ?? 0);
                            $sales_transfer = (float) ($vehicle->sales_transfer ?? 0);
                            $sales_others = (float) ($vehicle->sales_others ?? 0);
                            $final_total = $pvp + $sales_iuc + $sales_tow + $sales_transfer + $sales_others;
                        @endphp
                        <h4>Preparação e venda da viatura</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('total_price') ? 'has-error' : '' }}">
                                    <label for="total_price">{{ trans('cruds.vehicle.fields.total_price') }}</label>
                                    <input class="form-control" type="number" name="total_price" id="total_price" value="{{ old('total_price', $vehicle->total_price) }}" step="0.01">
                                    @if($errors->has('total_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('total_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.total_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('minimum_price') ? 'has-error' : '' }}">
                                    <label for="minimum_price">{{ trans('cruds.vehicle.fields.minimum_price') }}</label>
                                    <input class="form-control" type="number" name="minimum_price" id="minimum_price" value="{{ old('minimum_price', $vehicle->minimum_price) }}" step="0.01">
                                    @if($errors->has('minimum_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('minimum_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.minimum_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('pvp') ? 'has-error' : '' }}">
                                    <label for="pvp">{{ trans('cruds.vehicle.fields.pvp') }}</label>
                                    <input class="form-control" type="number" name="pvp" id="pvp" value="{{ old('pvp', $vehicle->pvp) }}" step="0.01">
                                    @if($errors->has('pvp'))
                                        <span class="help-block" role="alert">{{ $errors->first('pvp') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pvp_helper') }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="final_total">Total final</label>
                                    <input class="form-control" type="text" name="final_total" id="final_total"
                                        value="{{ number_format((float)$final_total, 2, ',', '.') }} €" readonly>
                                </div>
                                <div class="form-group {{ $errors->has('sales_iuc') ? 'has-error' : '' }}">
                                    <label for="sales_iuc">{{ trans('cruds.vehicle.fields.sales_iuc') }}</label>
                                    <input class="form-control" type="number" name="sales_iuc" id="sales_iuc" value="{{ old('sales_iuc', $vehicle->sales_iuc) }}" step="0.01">
                                    @if($errors->has('sales_iuc'))
                                        <span class="help-block" role="alert">{{ $errors->first('sales_iuc') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sales_iuc_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sales_tow') ? 'has-error' : '' }}">
                                    <label for="sales_tow">{{ trans('cruds.vehicle.fields.sales_tow') }}</label>
                                    <input class="form-control" type="number" name="sales_tow" id="sales_tow" value="{{ old('sales_tow', $vehicle->sales_tow) }}" step="0.01">
                                    @if($errors->has('sales_tow'))
                                        <span class="help-block" role="alert">{{ $errors->first('sales_tow') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sales_tow_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sales_transfer') ? 'has-error' : '' }}">
                                    <label for="sales_transfer">{{ trans('cruds.vehicle.fields.sales_transfer') }}</label>
                                    <input class="form-control" type="number" name="sales_transfer" id="sales_transfer" value="{{ old('sales_transfer', $vehicle->sales_transfer) }}" step="0.01">
                                    @if($errors->has('sales_transfer'))
                                        <span class="help-block" role="alert">{{ $errors->first('sales_transfer') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sales_transfer_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sales_others') ? 'has-error' : '' }}">
                                    <label for="sales_others">{{ trans('cruds.vehicle.fields.sales_others') }}</label>
                                    <input class="form-control" type="number" name="sales_others" id="sales_others" value="{{ old('sales_others', $vehicle->sales_others) }}" step="0.01">
                                    @if($errors->has('sales_others'))
                                        <span class="help-block" role="alert">{{ $errors->first('sales_others') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sales_others_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('client') ? 'has-error' : '' }}">
                                    <label for="client_id">{{ trans('cruds.vehicle.fields.client') }}</label>
                                    <select class="form-control select2" name="client_id" id="client_id">
                                        @foreach($clients as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('client_id') ? old('client_id') : $vehicle->client->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('client'))
                                        <span class="help-block" role="alert">{{ $errors->first('client') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.client_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('client_payment_date') ? 'has-error' : '' }}">
                                    <label for="client_payment_date">Data pagamento cliente</label>
                                    <input class="form-control date" type="text" name="client_payment_date" id="client_payment_date" value="{{ old('client_payment_date') }}">
                                    @if($errors->has('client_payment_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('client_payment_date') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('client_payment_amount') ? 'has-error' : '' }}">
                                    <label for="client_payment_amount">Valor pagamento cliente</label>
                                    <input class="form-control" type="number" name="client_payment_amount" id="client_payment_amount" value="{{ old('client_payment_amount') }}" step="0.01" min="0.01">
                                    @if($errors->has('client_payment_amount'))
                                        <span class="help-block" role="alert">{{ $errors->first('client_payment_amount') }}</span>
                                    @endif
                                </div>
                                <div class="form-group {{ $errors->has('client_payment_method_id') ? 'has-error' : '' }}">
                                    <label for="client_payment_method_id">Meio de pagamento cliente</label>
                                    <select class="form-control select2" name="client_payment_method_id" id="client_payment_method_id">
                                        @foreach($payment_methods as $id => $entry)
                                            <option value="{{ $id }}" {{ old('client_payment_method_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('client_payment_method_id'))
                                        <span class="help-block" role="alert">{{ $errors->first('client_payment_method_id') }}</span>
                                    @endif
                                </div>
                                <div class="panel panel-default" id="client-payments-panel">
                                    <div class="panel-heading">Pagamentos do cliente final</div>
                                    <div class="panel-body" style="padding: 10px;">
                                        <div style="margin-bottom: 8px;">
                                            <strong>Total final:</strong> {{ number_format((float) ($salesFinalTotal ?? 0), 2, ',', '.') }} EUR
                                            <br>
                                            <strong>Total recebido:</strong> {{ number_format((float) ($clientPaymentsTotal ?? 0), 2, ',', '.') }} EUR
                                        </div>
                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label for="balance-3">Em dívida</label>
                                            <input class="form-control" type="text" id="balance-3" value="{{ number_format((float) ($clientPaymentsOutstanding ?? 0), 2, ',', '.') }} €" readonly>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="table-department-3" class="table table-bordered table-striped table-condensed">
                                                <thead>
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Meio</th>
                                                        <th>Valor</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(($clientPayments ?? collect()) as $clientPayment)
                                                        <tr data-total-raw="{{ (float) $clientPayment->amount }}">
                                                            <td>{{ $clientPayment->paid_at }}</td>
                                                            <td>{{ $clientPayment->payment_method->name ?? '-' }}</td>
                                                            <td>{{ number_format((float) $clientPayment->amount, 2, ',', '.') }} EUR</td>
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-xs btn-danger js-delete-payment"
                                                                    data-delete-url="{{ route('admin.vehicles.client-payments.destroy', [$vehicle->id, $clientPayment->id]) }}"
                                                                >
                                                                    Eliminar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4">Sem pagamentos de cliente registados.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group {{ $errors->has('payment_comprovant') ? 'has-error' : '' }}">
                                    <label for="payment_comprovant">{{ trans('cruds.vehicle.fields.payment_comprovant') }}</label>
                                    <div class="needsclick dropzone" id="payment_comprovant-dropzone">
                                    </div>
                                    @if($errors->has('payment_comprovant'))
                                        <span class="help-block" role="alert">{{ $errors->first('payment_comprovant') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.payment_comprovant_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('payment_notes') ? 'has-error' : '' }}">
                                    <label for="payment_notes">{{ trans('cruds.vehicle.fields.payment_notes') }}</label>
                                    <textarea class="form-control ckeditor" name="payment_notes" id="payment_notes">{!! old('payment_notes', $vehicle->payment_notes) !!}</textarea>
                                    @if($errors->has('payment_notes'))
                                        <span class="help-block" role="alert">{{ $errors->first('payment_notes') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.payment_notes_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('client_registration') ? 'has-error' : '' }}">
                                    <label for="client_registration">{{ trans('cruds.vehicle.fields.client_registration') }}</label>
                                    <input class="form-control" type="text" name="client_registration" id="client_registration" value="{{ old('client_registration', $vehicle->client_registration) }}">
                                    @if($errors->has('client_registration'))
                                        <span class="help-block" role="alert">{{ $errors->first('client_registration') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.client_registration_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('chekin_date') ? 'has-error' : '' }}">
                                    <label for="chekin_date">{{ trans('cruds.vehicle.fields.chekin_date') }}</label>
                                    <input class="form-control date" type="text" name="chekin_date" id="chekin_date" value="{{ old('chekin_date', $vehicle->chekin_date) }}">
                                    @if($errors->has('chekin_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('chekin_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.chekin_date_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sale_date') ? 'has-error' : '' }}">
                                    <label for="sale_date">{{ trans('cruds.vehicle.fields.sale_date') }}</label>
                                    <input class="form-control date" type="text" name="sale_date" id="sale_date" value="{{ old('sale_date', $vehicle->sale_date) }}">
                                    @if($errors->has('sale_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('sale_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sale_date_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sele_chekout') ? 'has-error' : '' }}">
                                    <label for="sele_chekout">{{ trans('cruds.vehicle.fields.sele_chekout') }}</label>
                                    <input class="form-control date" type="text" name="sele_chekout" id="sele_chekout" value="{{ old('sele_chekout', $vehicle->sele_chekout) }}">
                                    @if($errors->has('sele_chekout'))
                                        <span class="help-block" role="alert">{{ $errors->first('sele_chekout') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sele_chekout_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('photos') ? 'has-error' : '' }}">
                                    <label for="photos">{{ trans('cruds.vehicle.fields.photos') }}</label>
                                    <div class="needsclick dropzone" id="photos-dropzone">
                                    </div>
                                    @if($errors->has('photos'))
                                        <span class="help-block" role="alert">{{ $errors->first('photos') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.photos_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sale_notes') ? 'has-error' : '' }}">
                                    <label for="sale_notes">{{ trans('cruds.vehicle.fields.sale_notes') }}</label>
                                    <textarea class="form-control" name="sale_notes" id="sale_notes">{{ old('sale_notes', $vehicle->sale_notes) }}</textarea>
                                    @if($errors->has('sale_notes'))
                                        <span class="help-block" role="alert">{{ $errors->first('sale_notes') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sale_notes_helper') }}</span>
                                </div>
                            </div>
                        </div>

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
                                        </div>
                                        <div class="col-md-6">
                                            <h5><strong>Documentos</strong></h5>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->vehicle_ownership_title ? 'checked' : '' }}>
                                                    DUA
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
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->release_of_reservation_or_mortgage ? 'checked' : '' }}>
                                                    Extinção Reserva / Hipotéca
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label style="font-weight: 400">
                                                    <input type="checkbox" disabled {{ $vehicle->leasing_agreement ? 'checked' : '' }}>
                                                    {{ trans('cruds.vehicle.fields.leasing_agreement') }}
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
                                            <a href="{{ $media->getUrl() }}" data-lightbox="vehicle-inicial-gallery" style="display: inline-block; margin-right: 5px;">
                                                <img src="{{ $media->getUrl('thumb') }}" alt="Foto inicial da viatura">
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
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                    <button type="submit" form="vehicle-edit-form" class="btn btn-danger floating-save-btn">
                        {{ trans('global.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')

<script>
    var uploadedDocumentsMap = {}
    Dropzone.options.documentsDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10, // MB
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="documents[]" value="' + response.name + '">')
            uploadedDocumentsMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedDocumentsMap[file.name]
            $('form').find('input[name="documents[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->documents)
                var files = {!! json_encode($vehicle->documents) !!}
                for (var i in files) {
                    const currentFile = files[i]
                    this.options.addedfile.call(this, currentFile)
                    currentFile.previewElement.classList.add('dz-complete')
                    currentFile.previewElement.style.cursor = 'pointer'
                    currentFile.previewElement.addEventListener('click', function (e) {
                        if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                            return
                        }
                        window.open(currentFile.original_url, '_blank')
                    })
                    $('form').append('<input type="hidden" name="documents[]" value="' + currentFile.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    (function () {
        const masterCheckbox = document.getElementById('has_all_documents_toggle');
        if (!masterCheckbox) return;

        const documentFieldIds = [
            'purchase_and_sale_agreement',
            'copy_of_the_citizen_card',
            'tax_identification_card',
            'copy_of_the_stamp_duty_receipt',
            'vehicle_ownership_title',
            'release_of_reservation_or_mortgage',
            'leasing_agreement'
        ];

        const documentCheckboxes = documentFieldIds
            .map(function (id) { return document.getElementById(id); })
            .filter(function (el) { return !!el; });

        function syncMasterFromDocuments() {
            if (documentCheckboxes.length === 0) return;
            masterCheckbox.checked = documentCheckboxes.every(function (checkbox) { return checkbox.checked; });
        }

        masterCheckbox.addEventListener('change', function () {
            documentCheckboxes.forEach(function (checkbox) {
                checkbox.checked = masterCheckbox.checked;
            });
        });

        documentCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncMasterFromDocuments);
        });

        syncMasterFromDocuments();
    })();
</script>

<script>
    window.vehicleEditCkEditors = window.vehicleEditCkEditors || [];
    $(document).ready(function () {
        function SimpleUploadAdapter(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
                return {
                    upload: function() {
                        return loader.file.then(function (file) {
                            return new Promise(function(resolve, reject) {
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', '{{ route('admin.vehicles.storeCKEditorImages') }}', true);
                                xhr.setRequestHeader('x-csrf-token', window._token);
                                xhr.setRequestHeader('Accept', 'application/json');
                                xhr.responseType = 'json';

                                var genericErrorText = `Couldn't upload file: ${ file.name }.`;
                                xhr.addEventListener('error', function() { reject(genericErrorText) });
                                xhr.addEventListener('abort', function() { reject() });
                                xhr.addEventListener('load', function() {
                                    var response = xhr.response;
                                    if (!response || xhr.status !== 201) {
                                        return reject(response && response.message
                                            ? `${genericErrorText}\n${xhr.status} ${response.message}`
                                            : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
                                    }
                                    $('form').append('<input type="hidden" name="ck-media[]" value="' + response.id + '">');
                                    resolve({ default: response.url });
                                });

                                if (xhr.upload) {
                                    xhr.upload.addEventListener('progress', function(e) {
                                        if (e.lengthComputable) {
                                            loader.uploadTotal = e.total;
                                            loader.uploaded = e.loaded;
                                        }
                                    });
                                }

                                var data = new FormData();
                                data.append('upload', file);
                                data.append('crud_id', '{{ $vehicle->id ?? 0 }}');
                                xhr.send(data);
                            });
                        })
                    }
                };
            }
        }

        var allEditors = document.querySelectorAll('.ckeditor');
        for (var i = 0; i < allEditors.length; ++i) {
            ClassicEditor
                .create(allEditors[i], { extraPlugins: [SimpleUploadAdapter] })
                .then(function (editor) {
                    window.vehicleEditCkEditors.push(editor);
                });
        }
    });
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
    var uploadedPhotosMap = {}
    Dropzone.options.photosDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10,
        acceptedFiles: '.jpeg,.jpg,.png,.gif',
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10, width: 4096, height: 4096 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="photos[]" value="' + response.name + '">')
            uploadedPhotosMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedPhotosMap[file.name]
            $('form').find('input[name="photos[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->photos)
                var files = {!! json_encode($vehicle->photos) !!}
                for (var i in files) {
                    var file = files[i];
                    this.options.addedfile.call(this, file)
                    this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
                    file.previewElement.classList.add('dz-complete')
                    $('form').append('<input type="hidden" name="photos[]" value="' + file.file_name + '">')
                    const img = file.previewElement.querySelector("img");
                    if (img) {
                        img.style.cursor = "pointer";
                        const a = document.createElement('a');
                        a.href = file.original_url;
                        a.setAttribute('data-lightbox', 'gallery');
                        img.parentNode.insertBefore(a, img);
                        a.appendChild(img);
                        file.previewElement.style.cursor = "pointer";
                        file.previewElement.addEventListener('click', function (e) {
                            if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                                return;
                            }
                            a.click();
                        });
                    }
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedInvoiceMap = {}
    Dropzone.options.invoiceDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="invoice[]" value="' + response.name + '">')
            uploadedInvoiceMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedInvoiceMap[file.name]
            $('form').find('input[name="invoice[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->invoice)
                var files = {!! json_encode($vehicle->invoice) !!}
                for (var i in files) {
                    const currentFile = files[i]
                    this.options.addedfile.call(this, currentFile)
                    currentFile.previewElement.classList.add('dz-complete')
                    currentFile.previewElement.style.cursor = 'pointer'
                    currentFile.previewElement.addEventListener('click', function (e) {
                        if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                            return
                        }
                        window.open(currentFile.original_url, '_blank')
                    })
                    $('form').append('<input type="hidden" name="invoice[]" value="' + currentFile.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedPdfsMap = {}
    Dropzone.options.pdfsDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="pdfs[]" value="' + response.name + '">')
            uploadedPdfsMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedPdfsMap[file.name]
            $('form').find('input[name="pdfs[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->pdfs)
                var files = {!! json_encode($vehicle->pdfs) !!}
                for (var i in files) {
                    const currentFile = files[i]
                    this.options.addedfile.call(this, currentFile)
                    currentFile.previewElement.classList.add('dz-complete')
                    currentFile.previewElement.style.cursor = 'pointer'
                    currentFile.previewElement.addEventListener('click', function (e) {
                        if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                            return
                        }
                        window.open(currentFile.original_url, '_blank')
                    })
                    $('form').append('<input type="hidden" name="pdfs[]" value="' + currentFile.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedInicialMap = {}
    Dropzone.options.inicialDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10,
        acceptedFiles: '.jpeg,.jpg,.png,.gif',
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10, width: 4096, height: 4096 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="inicial[]" value="' + response.name + '">')
            uploadedInicialMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedInicialMap[file.name]
            $('form').find('input[name="inicial[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->inicial)
                var files = {!! json_encode($vehicle->inicial) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
                    file.previewElement.classList.add('dz-complete')
                    $('form').append('<input type="hidden" name="inicial[]" value="' + file.file_name + '">')
                    const img = file.previewElement.querySelector("img");
                    if (img) {
                        img.style.cursor = "pointer";
                        const a = document.createElement('a');
                        a.href = file.original_url;
                        a.setAttribute('data-lightbox', 'gallery');
                        img.parentNode.insertBefore(a, img);
                        a.appendChild(img);
                        file.previewElement.style.cursor = "pointer";
                        file.previewElement.addEventListener('click', function (e) {
                            if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                                return;
                            }
                            a.click();
                        });
                    }
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedWithdrawalAuthorizationFileMap = {}
    Dropzone.options.withdrawalAuthorizationFileDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + response.name + '">')
            uploadedWithdrawalAuthorizationFileMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedWithdrawalAuthorizationFileMap[file.name]
            $('form').find('input[name="withdrawal_authorization_file[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->withdrawal_authorization_file)
                var files = {!! json_encode($vehicle->withdrawal_authorization_file) !!}
                for (var i in files) {
                    const currentFile = files[i]
                    this.options.addedfile.call(this, currentFile)
                    currentFile.previewElement.classList.add('dz-complete')
                    currentFile.previewElement.style.cursor = 'pointer'
                    currentFile.previewElement.addEventListener('click', function (e) {
                        if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                            return
                        }
                        window.open(currentFile.original_url, '_blank')
                    })
                    $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + currentFile.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

{{-- *********************
     TOTAL FINAL & SALDO (parser robusto)
     ********************* --}}
<script>
    /**
     * Converte strings com vírgula/ponto e separadores de milhar numa float correta.
     * Exemplos aceites: "5.250", "5,250", "5 250", "5.250,75", "5,250.75", "100,00", "100.00", "10000"
     */
    function toFloatSafe(input) {
        if (input == null) return 0;
        let s = String(input).trim();

        if (s === '') return 0;

        // remove espaços e símbolos (mantém dígitos, vírgula e ponto)
        s = s.replace(/[^\d.,-]/g, '');

        const hasDot = s.indexOf('.') !== -1;
        const hasComma = s.indexOf(',') !== -1;

        if (hasDot && hasComma) {
            // se tem os dois, o separador decimal é o que aparece por último
            const lastDot = s.lastIndexOf('.');
            const lastComma = s.lastIndexOf(',');
            if (lastComma > lastDot) {
                // vírgula como decimal -> remove pontos (milhar), troca vírgula por ponto
                s = s.replace(/\./g, '').replace(',', '.');
            } else {
                // ponto como decimal -> remove vírgulas (milhar)
                s = s.replace(/,/g, '');
            }
        } else if (hasComma && !hasDot) {
            // só vírgula -> se houver 1-2 dígitos após a vírgula, tratamos como decimal
            const parts = s.split(',');
            if (parts.length === 2 && parts[1].length <= 2) {
                s = parts[0].replace(/\./g, '') + '.' + parts[1];
            } else {
                // caso "5,250" (milhar com vírgula) -> remove vírgulas
                s = s.replace(/,/g, '');
            }
        } else {
            // só ponto ou nenhum -> remove separadores de milhar estilo "10.000"
            // mas mantém o último ponto como decimal (caso "100.50")
            const matches = s.match(/\./g);
            if (matches && matches.length > 1) {
                const last = s.lastIndexOf('.');
                s = s.replace(/\./g, '');
                // reintroduz o ponto decimal na última posição
                s = s.slice(0, last - (matches.length - 1)) + '.' + s.slice(last - (matches.length - 1));
            }
        }

        const n = parseFloat(s);
        return isNaN(n) ? 0 : n;
    }

    function fmtEUR(num) {
        return (Number(num) || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getFinalTotalFromInputs() {
        const pvp            = toFloatSafe(document.getElementById('pvp')?.value);
        const sales_iuc      = toFloatSafe(document.getElementById('sales_iuc')?.value);
        const sales_tow      = toFloatSafe(document.getElementById('sales_tow')?.value);
        const sales_transfer = toFloatSafe(document.getElementById('sales_transfer')?.value);
        const sales_others   = toFloatSafe(document.getElementById('sales_others')?.value);
        return pvp + sales_iuc + sales_tow + sales_transfer + sales_others;
    }

    function calcTotalPaidFromClientTable() {
        // 1) preferimos linhas com data-total-raw
        let rows = document.querySelectorAll('#table-department-3 tbody tr[data-total-raw]');
        if (rows.length > 0) {
            let sum = 0;
            rows.forEach(tr => sum += toFloatSafe(tr.getAttribute('data-total-raw')));
            return sum;
        }
        // 2) fallback: 3ª coluna com "1.234,56 €"
        rows = document.querySelectorAll('#table-department-3 tbody tr');
        let total = 0;
        rows.forEach(tr => {
            const td = tr.querySelector('td:nth-child(3)');
            if (td) total += toFloatSafe(td.textContent);
        });
        return total;
    }

    function setFinalTotalAndBalance() {
        const finalTotal = getFinalTotalFromInputs();

        const finalTotalInput = document.getElementById('final_total');
        if (finalTotalInput) {
            finalTotalInput.value = fmtEUR(finalTotal) + ' €';
        }

        const totalPaid = calcTotalPaidFromClientTable();
        const balanceInput = document.getElementById('balance-3');
        if (balanceInput) {
            const balance = finalTotal - totalPaid;
            balanceInput.value = fmtEUR(balance) + ' €';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['pvp','sales_iuc','sales_tow','sales_transfer','sales_others'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', setFinalTotalAndBalance);
                el.addEventListener('change', setFinalTotalAndBalance);
            }
        });
        setFinalTotalAndBalance();
    });
</script>

{{-- *********************
     PAGAMENTOS (CRUD) — sem alterações, mas recalcula saldo no fim
     ********************* --}}
<script>
    (function () {
        const form = document.getElementById('vehicle-edit-form');
        if (!form) return;

        const submitButtons = Array.from(document.querySelectorAll('button[type="submit"][form="vehicle-edit-form"], #vehicle-edit-form button[type="submit"]'));
        let isSubmitting = false;

        function parseLocaleNumber(value) {
            if (value === null || typeof value === 'undefined') return 0;
            const normalized = String(value).trim().replace(/[^\d,.\-]/g, '').replace(/\./g, '').replace(',', '.');
            const parsed = parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function formatLocaleNumber(value) {
            return new Intl.NumberFormat('pt-PT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value || 0);
        }

        function getBaseTotal(elementId) {
            const el = document.getElementById(elementId);
            if (!el) return 0;

            if (el.dataset.baseTotal) {
                return parseLocaleNumber(el.dataset.baseTotal);
            }

            return parseLocaleNumber(el.textContent);
        }

        function refreshAcquisitionExpensesTotal() {
            const purchasePrice = parseLocaleNumber(document.getElementById('purchase_price')?.value);
            const purchaseVatValue = parseLocaleNumber(document.getElementById('purchase_vat_value')?.value);
            const iucPrice = parseLocaleNumber(document.getElementById('iuc_price')?.value);
            const commission = parseLocaleNumber(document.getElementById('commission')?.value);
            const towPrice = parseLocaleNumber(document.getElementById('tow_price')?.value);
            const genericBaseTotal = getBaseTotal('generic-payments-total');
            const genericDraft = parseLocaleNumber(document.getElementById('generic_payment_amount')?.value);
            const total = purchasePrice + purchaseVatValue + iucPrice + commission + towPrice + genericBaseTotal + genericDraft;

            const totalEl = document.getElementById('acquisition-expenses-total');
            if (totalEl) {
                totalEl.textContent = formatLocaleNumber(total);
            }
        }

        function setLoadingState(loading) {
            submitButtons.forEach(function (button) {
                button.disabled = loading;
                if (button.classList.contains('floating-save-btn')) {
                    button.classList.toggle('is-loading', loading);
                }
            });
        }

        function showAjaxAlert(type, message) {
            const existing = document.getElementById('vehicle-ajax-alert');
            if (existing) existing.remove();

            const alert = document.createElement('div');
            alert.id = 'vehicle-ajax-alert';
            alert.className = 'alert alert-' + type;
            alert.style.position = 'fixed';
            alert.style.right = '24px';
            alert.style.bottom = '86px';
            alert.style.zIndex = '1200';
            alert.style.maxWidth = '420px';
            alert.style.boxShadow = '0 6px 18px rgba(0,0,0,.2)';
            alert.textContent = message;
            document.body.appendChild(alert);

            setTimeout(function () {
                if (alert && alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 3500);
        }

        function refreshPaymentsPanels() {
            return $.get(window.location.href).done(function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const panelIds = ['acquisition-expenses-panel', 'supplier-payments-panel', 'generic-payments-panel', 'client-payments-panel'];

                panelIds.forEach(function (panelId) {
                    const currentPanel = document.getElementById(panelId);
                    const freshPanel = doc.getElementById(panelId);
                    if (currentPanel && freshPanel) {
                        currentPanel.innerHTML = freshPanel.innerHTML;
                    }
                });

                if (typeof setFinalTotalAndBalance === 'function') {
                    setFinalTotalAndBalance();
                }

                refreshAcquisitionExpensesTotal();
            });
        }

        function clearPaymentEntryFields() {
            const inputIds = [
                'supplier_payment_date',
                'supplier_payment_amount',
                'supplier_payment_method_id',
                'generic_payment_expense_label',
                'generic_payment_date',
                'generic_payment_amount',
                'generic_payment_method_id',
                'client_payment_date',
                'client_payment_amount',
                'client_payment_method_id'
            ];

            inputIds.forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;

                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                    if (window.jQuery && $(el).hasClass('select2-hidden-accessible')) {
                        $(el).trigger('change');
                    }
                } else {
                    el.value = '';
                }
            });
        }

        window.showVehicleAjaxAlert = showAjaxAlert;
        window.refreshVehiclePaymentsPanels = refreshPaymentsPanels;

        ['purchase_price', 'purchase_vat_value', 'iuc_price', 'commission', 'tow_price', 'generic_payment_amount'].forEach(function (id) {
            const input = document.getElementById(id);
            if (!input) return;

            input.addEventListener('input', refreshAcquisitionExpensesTotal);
            input.addEventListener('change', refreshAcquisitionExpensesTotal);
        });

        refreshAcquisitionExpensesTotal();

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (isSubmitting) return;

            isSubmitting = true;
            setLoadingState(true);

            if (window.vehicleEditCkEditors && window.vehicleEditCkEditors.length > 0) {
                window.vehicleEditCkEditors.forEach(function (editor) {
                    if (editor && editor.sourceElement) {
                        editor.sourceElement.value = editor.getData();
                    }
                });
            }

            const formData = new FormData(form);

            $.ajax({
                url: form.action,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (response) {
                showAjaxAlert('success', response.message || 'Atualizado com sucesso');
                refreshPaymentsPanels();
                clearPaymentEntryFields();
                refreshAcquisitionExpensesTotal();
            }).fail(function (xhr) {
                let message = 'Ocorreu um erro ao gravar.';

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    if (firstKey && xhr.responseJSON.errors[firstKey] && xhr.responseJSON.errors[firstKey][0]) {
                        message = xhr.responseJSON.errors[firstKey][0];
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                showAjaxAlert('danger', message);
            }).always(function () {
                isSubmitting = false;
                setLoadingState(false);
            });
        });

        document.addEventListener('click', function (event) {
            const target = event.target.closest('.js-delete-payment');
            if (!target) return;

            event.preventDefault();

            const deleteUrl = target.getAttribute('data-delete-url');
            if (!deleteUrl) return;

            if (!confirm('Tens a certeza que queres eliminar este pagamento?')) {
                return;
            }

            $.ajax({
                url: deleteUrl,
                method: 'POST',
                data: { _method: 'DELETE' },
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (response) {
                showAjaxAlert('success', response.message || 'Pagamento removido com sucesso');
                refreshPaymentsPanels();
            }).fail(function (xhr) {
                let message = 'Não foi possível eliminar o pagamento.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAjaxAlert('danger', message);
            });
        });
    })();
</script>

<script>
    var uploadedPaymentComprovantMap = {}
    Dropzone.options.paymentComprovantDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 10,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 10 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="payment_comprovant[]" value="' + response.name + '">')
            uploadedPaymentComprovantMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedPaymentComprovantMap[file.name]
            $('form').find('input[name="payment_comprovant[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->payment_comprovant)
                var files = {!! json_encode($vehicle->payment_comprovant) !!}
                for (var i in files) {
                    const currentFile = files[i]
                    this.options.addedfile.call(this, currentFile)
                    currentFile.previewElement.classList.add('dz-complete')
                    currentFile.previewElement.style.cursor = 'pointer'
                    currentFile.previewElement.addEventListener('click', function (e) {
                        if (e.target && e.target.classList && e.target.classList.contains('dz-remove')) {
                            return
                        }
                        window.open(currentFile.original_url, '_blank')
                    })
                    $('form').append('<input type="hidden" name="payment_comprovant[]" value="' + currentFile.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

@endsection


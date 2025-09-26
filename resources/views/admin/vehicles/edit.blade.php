@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.vehicle.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.vehicles.update', [$vehicle->id]) }}" enctype="multipart/form-data">
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

                        @can('aquisition_of_the_vehicle')
                        <h4>Aquisição da viatura</h4>
                        @endcan
                        <hr>
                        <div class="row">
                            @can('aquisition_of_the_vehicle')
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('purchase_price') ? 'has-error' : '' }}">
                                    <label for="purchase_price">{{ trans('cruds.vehicle.fields.purchase_price') }}</label>
                                    <input class="form-control" type="number" name="purchase_price" id="purchase_price" value="{{ old('purchase_price', $vehicle->purchase_price) }}" step="0.01">
                                    @if($errors->has('purchase_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('purchase_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.purchase_price_helper') }}</span>
                                </div>
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
                                <div class="form-group {{ $errors->has('payment_date') ? 'has-error' : '' }}">
                                    <label for="payment_date">{{ trans('cruds.vehicle.fields.payment_date') }}</label>
                                    <input class="form-control date" type="text" name="payment_date" id="payment_date" value="{{ old('payment_date', $vehicle->payment_date) }}">
                                    @if($errors->has('payment_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('payment_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.payment_date_helper') }}</span>
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
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="purchase_payment_method_id" class="required">Métodos de pagamento</label>
                                    <select class="form-control select2" name="purchase_payment_method_id" id="purchase_payment_method_id">
                                        @foreach($payment_methods as $id => $entry)
                                        <option value="{{ $id }}">{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="purchase_date">Data</label>
                                    <input type="text" class="form-control date" name="purchase_date" id="purchase_date">
                                </div>
                                <div class="form-group">
                                    <label for="purchase_value">Value</label>
                                    <input type="number" class="form-control" name="purchase_value" id="purchase_value" step="0.01">
                                </div>
                                <!-- In your blade input button -->
                                <button type="button" class="btn btn-success btn-sm" onclick="newPurchasePayment(1)">Save</button>
                                <div id="payment-status" style="display:none; margin-top: 10px;"></div>

                                <!-- Display balance below value -->
                                @php
                                    $account_operations = $vehicle->acquisition_operations ?? collect();
                                    $totalPaid = $account_operations->sum(fn($op) => (float) $op->total);
                                    $purchasePrice = (float) ($vehicle->purchase_price ?? 0);
                                    $balance = $purchasePrice - $totalPaid;
                                @endphp
                                <hr>
                                <div class="form-group">
                                    <label>Balance</label>
                                    <input type="text" class="form-control" id="balance-1" value="{{ number_format((float)$balance, 2, ',', '.') }} €" readonly>
                                </div>

                                <!-- Payment history list -->
                                <table class="table table-bordered" id="table-department-1">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Item</th>
                                            <th>Valor</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($account_operations as $op)
                                            <tr>
                                                <td>{{ $op->date ? \Carbon\Carbon::parse($op->date)->format('d/m/Y') : $op->created_at->format('d/m/Y') }}</td>
                                                <td>{{ $op->account_item->name ?? '-' }}</td>
                                                <td>{{ number_format((float)$op->total, 2, ',', '.') }} €</td>
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-warning" onclick="editPayment({{ $op->id }}, {{ (float)$op->total }}, 1)">Editar</button>
                                                    <button type="button" class="btn btn-xs btn-danger" onclick="deletePayment({{ $op->id }}, 1)">Apagar</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>
                            @endcan
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
                                @can('aquisition_of_the_vehicle')
                                <div class="form-group {{ $errors->has('invoice') ? 'has-error' : '' }}">
                                    <label for="invoice">{{ trans('cruds.vehicle.fields.invoice') }}</label>
                                    <div class="needsclick dropzone" id="invoice-dropzone">
                                    </div>
                                    @if($errors->has('invoice'))
                                        <span class="help-block" role="alert">{{ $errors->first('invoice') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.invoice_helper') }}</span>
                                </div>
                                @endcan
                            </div>
                        </div>

                        <h4>Documentos da viatura</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('purchase_and_sale_agreement') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="purchase_and_sale_agreement" value="0">
                                        <input type="checkbox" name="purchase_and_sale_agreement" id="purchase_and_sale_agreement" value="1" {{ $vehicle->purchase_and_sale_agreement || old('purchase_and_sale_agreement', 0) == 1 ? 'checked' : '' }}>
                                        <label for="purchase_and_sale_agreement" style="font-weight: 400">{{ trans('cruds.vehicle.fields.purchase_and_sale_agreement') }}</label>
                                    </div>
                                    @if($errors->has('purchase_and_sale_agreement'))
                                        <span class="help-block" role="alert">{{ $errors->first('purchase_and_sale_agreement') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.purchase_and_sale_agreement_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('copy_of_the_citizen_card') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="copy_of_the_citizen_card" value="0">
                                        <input type="checkbox" name="copy_of_the_citizen_card" id="copy_of_the_citizen_card" value="1" {{ $vehicle->copy_of_the_citizen_card || old('copy_of_the_citizen_card', 0) == 1 ? 'checked' : '' }}>
                                        <label for="copy_of_the_citizen_card" style="font-weight: 400">{{ trans('cruds.vehicle.fields.copy_of_the_citizen_card') }}</label>
                                    </div>
                                    @if($errors->has('copy_of_the_citizen_card'))
                                        <span class="help-block" role="alert">{{ $errors->first('copy_of_the_citizen_card') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.copy_of_the_citizen_card_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('tax_identification_card') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="tax_identification_card" value="0">
                                        <input type="checkbox" name="tax_identification_card" id="tax_identification_card" value="1" {{ $vehicle->tax_identification_card || old('tax_identification_card', 0) == 1 ? 'checked' : '' }}>
                                        <label for="tax_identification_card" style="font-weight: 400">{{ trans('cruds.vehicle.fields.tax_identification_card') }}</label>
                                    </div>
                                    @if($errors->has('tax_identification_card'))
                                        <span class="help-block" role="alert">{{ $errors->first('tax_identification_card') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.tax_identification_card_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('copy_of_the_stamp_duty_receipt') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="copy_of_the_stamp_duty_receipt" value="0">
                                        <input type="checkbox" name="copy_of_the_stamp_duty_receipt" id="copy_of_the_stamp_duty_receipt" value="1" {{ $vehicle->copy_of_the_stamp_duty_receipt || old('copy_of_the_stamp_duty_receipt', 0) == 1 ? 'checked' : '' }}>
                                        <label for="copy_of_the_stamp_duty_receipt" style="font-weight: 400">{{ trans('cruds.vehicle.fields.copy_of_the_stamp_duty_receipt') }}</label>
                                    </div>
                                    @if($errors->has('copy_of_the_stamp_duty_receipt'))
                                        <span class="help-block" role="alert">{{ $errors->first('copy_of_the_stamp_duty_receipt') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.copy_of_the_stamp_duty_receipt_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('vehicle_registration_document') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="vehicle_registration_document" value="0">
                                        <input type="checkbox" name="vehicle_registration_document" id="vehicle_registration_document" value="1" {{ $vehicle->vehicle_registration_document || old('vehicle_registration_document', 0) == 1 ? 'checked' : '' }}>
                                        <label for="vehicle_registration_document" style="font-weight: 400">{{ trans('cruds.vehicle.fields.vehicle_registration_document') }}</label>
                                    </div>
                                    @if($errors->has('vehicle_registration_document'))
                                        <span class="help-block" role="alert">{{ $errors->first('vehicle_registration_document') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.vehicle_registration_document_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('vehicle_ownership_title') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="vehicle_ownership_title" value="0">
                                        <input type="checkbox" name="vehicle_ownership_title" id="vehicle_ownership_title" value="1" {{ $vehicle->vehicle_ownership_title || old('vehicle_ownership_title', 0) == 1 ? 'checked' : '' }}>
                                        <label for="vehicle_ownership_title" style="font-weight: 400">{{ trans('cruds.vehicle.fields.vehicle_ownership_title') }}</label>
                                    </div>
                                    @if($errors->has('vehicle_ownership_title'))
                                        <span class="help-block" role="alert">{{ $errors->first('vehicle_ownership_title') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.vehicle_ownership_title_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('vehicle_keys') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="vehicle_keys" value="0">
                                        <input type="checkbox" name="vehicle_keys" id="vehicle_keys" value="1" {{ $vehicle->vehicle_keys || old('vehicle_keys', 0) == 1 ? 'checked' : '' }}>
                                        <label for="vehicle_keys" style="font-weight: 400">{{ trans('cruds.vehicle.fields.vehicle_keys') }}</label>
                                    </div>
                                    @if($errors->has('vehicle_keys'))
                                        <span class="help-block" role="alert">{{ $errors->first('vehicle_keys') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.vehicle_keys_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('vehicle_manuals') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="vehicle_manuals" value="0">
                                        <input type="checkbox" name="vehicle_manuals" id="vehicle_manuals" value="1" {{ $vehicle->vehicle_manuals || old('vehicle_manuals', 0) == 1 ? 'checked' : '' }}>
                                        <label for="vehicle_manuals" style="font-weight: 400">{{ trans('cruds.vehicle.fields.vehicle_manuals') }}</label>
                                    </div>
                                    @if($errors->has('vehicle_manuals'))
                                        <span class="help-block" role="alert">{{ $errors->first('vehicle_manuals') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.vehicle_manuals_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('release_of_reservation_or_mortgage') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="release_of_reservation_or_mortgage" value="0">
                                        <input type="checkbox" name="release_of_reservation_or_mortgage" id="release_of_reservation_or_mortgage" value="1" {{ $vehicle->release_of_reservation_or_mortgage || old('release_of_reservation_or_mortgage', 0) == 1 ? 'checked' : '' }}>
                                        <label for="release_of_reservation_or_mortgage" style="font-weight: 400">{{ trans('cruds.vehicle.fields.release_of_reservation_or_mortgage') }}</label>
                                    </div>
                                    @if($errors->has('release_of_reservation_or_mortgage'))
                                        <span class="help-block" role="alert">{{ $errors->first('release_of_reservation_or_mortgage') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.release_of_reservation_or_mortgage_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('leasing_agreement') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="leasing_agreement" value="0">
                                        <input type="checkbox" name="leasing_agreement" id="leasing_agreement" value="1" {{ $vehicle->leasing_agreement || old('leasing_agreement', 0) == 1 ? 'checked' : '' }}>
                                        <label for="leasing_agreement" style="font-weight: 400">{{ trans('cruds.vehicle.fields.leasing_agreement') }}</label>
                                    </div>
                                    @if($errors->has('leasing_agreement'))
                                        <span class="help-block" role="alert">{{ $errors->first('leasing_agreement') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.leasing_agreement_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('cables') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="cables" value="0">
                                        <input type="checkbox" name="cables" id="cables" value="1" {{ $vehicle->cables || old('cables', 0) == 1 ? 'checked' : '' }}>
                                        <label for="cables" style="font-weight: 400">{{ trans('cruds.vehicle.fields.cables') }}</label>
                                    </div>
                                    @if($errors->has('cables'))
                                        <span class="help-block" role="alert">{{ $errors->first('cables') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.cables_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('date') ? 'has-error' : '' }}">
                                    <label for="date">{{ trans('cruds.vehicle.fields.date') }}</label>
                                    <input class="form-control date" type="text" name="date" id="date" value="{{ old('date', $vehicle->date) }}">
                                    @if($errors->has('date'))
                                        <span class="help-block" role="alert">{{ $errors->first('date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.date_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('documents') ? 'has-error' : '' }}">
                                    <label for="documents">{{ trans('cruds.vehicle.fields.documents') }}</label>
                                    <div class="needsclick dropzone" id="documents-dropzone">
                                    </div>
                                    @if($errors->has('documents'))
                                        <span class="help-block" role="alert">{{ $errors->first('documents') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.documents_helper') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('pending') ? 'has-error' : '' }}">
                                    <label for="pending">{{ trans('cruds.vehicle.fields.pending') }}</label>
                                    <textarea class="form-control ckeditor" name="pending" id="pending">{!! old('pending', $vehicle->pending) !!}</textarea>
                                    @if($errors->has('pending'))
                                        <span class="help-block" role="alert">{{ $errors->first('pending') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pending_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('additional_items') ? 'has-error' : '' }}">
                                    <label for="additional_items">{{ trans('cruds.vehicle.fields.additional_items') }}</label>
                                    <textarea class="form-control ckeditor" name="additional_items" id="additional_items">{!! old('additional_items', $vehicle->additional_items) !!}</textarea>
                                    @if($errors->has('additional_items'))
                                        <span class="help-block" role="alert">{{ $errors->first('additional_items') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.additional_items_helper') }}</span>
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
                                <div class="form-group {{ $errors->has('withdrawal_documents') ? 'has-error' : '' }}">
                                    <label for="withdrawal_documents">{{ trans('cruds.vehicle.fields.withdrawal_documents') }}</label>
                                    <div class="needsclick dropzone" id="withdrawal_documents-dropzone">
                                    </div>
                                    @if($errors->has('withdrawal_documents'))
                                        <span class="help-block" role="alert">{{ $errors->first('withdrawal_documents') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.withdrawal_documents_helper') }}</span>
                                </div>
                            </div>
                        </div>

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
                                <div class="form-group">
                                    <label>Pagamento Cliente</label>
                                    <select class="form-control select2" name="client_payment_item" id="client_payment_item">
                                        <option selected disabled>Selecionar item</option>
                                        @foreach ($sale_categories as $category)
                                            @foreach ($category->account_items as $item)
                                                <option value="{{ $item->id }}">{{ $category->name }} - {{ $item->name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="payment_method_id" class="required">Métodos de pagamento</label>
                                    <select class="form-control select2" name="client_payment_method_id" id="client_payment_method_id">
                                        @foreach($payment_methods as $id => $entry)
                                        <option value="{{ $id }}">{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="purchase_date">Data</label>
                                    <input type="text" class="form-control date" name="client_date" id="client_date">
                                </div>
                                <div class="form-group">
                                    <label>Valor</label>
                                    <input type="number" class="form-control" id="client_payment_value" step="0.01">
                                </div>
                                <button type="button" class="btn btn-success btn-sm" onclick="newClientPayment(3)">Registar</button>
                                <div id="client-status" style="display:none; margin-top: 10px;"></div>
                                <hr>
                                <!-- Display balance below value -->
                                @php
                                    $client_operations = $vehicle->client_operations ?? collect();
                                    $totalPaidClient = $client_operations->sum(fn($op) => (float) $op->total);
                                    $pvp = (float) ($vehicle->pvp ?? 0);
                                    $balanceClient = $pvp - $totalPaidClient;
                                @endphp
                                <div class="form-group">
                                    <label>Balance</label>
                                    <input type="text" class="form-control" id="balance-3" value="{{ number_format((float)$balanceClient, 2, ',', '.') }} €" readonly>
                                </div>

                                <table class="table table-bordered" id="table-department-3">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Item</th>
                                            <th>Valor</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($client_operations as $op)
                                            <tr>
                                                <td>{{ $op->date ? \Carbon\Carbon::parse($op->date)->format('d/m/Y') : $op->created_at->format('d/m/Y') }}</td>
                                                <td>{{ $op->account_item->name ?? '-' }}</td>
                                                <td>{{ number_format((float)$op->total, 2, ',', '.') }} €</td>
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-warning" onclick="editPayment({{ $op->id }}, {{ (float)$op->total }}, 3)">Editar</button>
                                                    <button type="button" class="btn btn-xs btn-danger" onclick="deletePayment({{ $op->id }}, 3)">Apagar</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

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
                                <div class="form-group {{ $errors->has('chekin_documents') ? 'has-error' : '' }}">
                                    <label for="chekin_documents">{{ trans('cruds.vehicle.fields.chekin_documents') }}</label>
                                    <input class="form-control" type="text" name="chekin_documents" id="chekin_documents" value="{{ old('chekin_documents', $vehicle->chekin_documents) }}">
                                    @if($errors->has('chekin_documents'))
                                        <span class="help-block" role="alert">{{ $errors->first('chekin_documents') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.chekin_documents_helper') }}</span>
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
                                <div class="form-group {{ $errors->has('first_key') ? 'has-error' : '' }}">
                                    <label for="first_key">{{ trans('cruds.vehicle.fields.first_key') }}</label>
                                    <input class="form-control" type="text" name="first_key" id="first_key" value="{{ old('first_key', $vehicle->first_key) }}">
                                    @if($errors->has('first_key'))
                                        <span class="help-block" role="alert">{{ $errors->first('first_key') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.first_key_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('scuts') ? 'has-error' : '' }}">
                                    <label for="scuts">{{ trans('cruds.vehicle.fields.scuts') }}</label>
                                    <input class="form-control" type="text" name="scuts" id="scuts" value="{{ old('scuts', $vehicle->scuts) }}">
                                    @if($errors->has('scuts'))
                                        <span class="help-block" role="alert">{{ $errors->first('scuts') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.scuts_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('key') ? 'has-error' : '' }}">
                                    <label for="key">{{ trans('cruds.vehicle.fields.key') }}</label>
                                    <input class="form-control" type="text" name="key" id="key" value="{{ old('key', $vehicle->key) }}">
                                    @if($errors->has('key'))
                                        <span class="help-block" role="alert">{{ $errors->first('key') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.key_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('manuals') ? 'has-error' : '' }}">
                                    <label for="manuals">{{ trans('cruds.vehicle.fields.manuals') }}</label>
                                    <input class="form-control" type="text" name="manuals" id="manuals" value="{{ old('manuals', $vehicle->manuals) }}">
                                    @if($errors->has('manuals'))
                                        <span class="help-block" role="alert">{{ $errors->first('manuals') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.manuals_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('elements_with_vehicle') ? 'has-error' : '' }}">
                                    <label for="elements_with_vehicle">{{ trans('cruds.vehicle.fields.elements_with_vehicle') }}</label>
                                    <input class="form-control" type="text" name="elements_with_vehicle" id="elements_with_vehicle" value="{{ old('elements_with_vehicle', $vehicle->elements_with_vehicle) }}">
                                    @if($errors->has('elements_with_vehicle'))
                                        <span class="help-block" role="alert">{{ $errors->first('elements_with_vehicle') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.elements_with_vehicle_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('local') ? 'has-error' : '' }}">
                                    <label for="local">{{ trans('cruds.vehicle.fields.local') }}</label>
                                    <input class="form-control" type="text" name="local" id="local" value="{{ old('local', $vehicle->local) }}">
                                    @if($errors->has('local'))
                                        <span class="help-block" role="alert">{{ $errors->first('local') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.local_helper') }}</span>
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

@section('styles')
<style>
    .dz-details { display: none !important; }
</style>
@endsection

@section('scripts')

<script>
    var uploadedDocumentsMap = {}
Dropzone.options.documentsDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 2000, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: { size: 2000 },
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
        var file = files[i]
        this.options.addedfile.call(this, file)
        file.previewElement.classList.add('dz-complete')
        file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
        $('form').append('<input type="hidden" name="documents[]" value="' + file.file_name + '">')
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
                  return reject(response && response.message ? `${genericErrorText}\n${xhr.status} ${response.message}` : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
                }
                $('form').append('<input type="hidden" name="ck-media[]" value="' + response.id + '">');
                resolve({ default: response.url });
              });

              if (xhr.upload) {
                xhr.upload.addEventListener('progress', function(e) {
                  if (e.length computable) {
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
    ClassicEditor.create(allEditors[i], { extraPlugins: [SimpleUploadAdapter] });
  }
});
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
var uploadedPhotosMap = {}
Dropzone.options.photosDropzone = {
  url: '{{ route('admin.vehicles.storeMedia') }}',
  maxFilesize: 20,
  acceptedFiles: '.jpeg,.jpg,.png,.gif',
  addRemoveLinks: true,
  headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
  params: { size: 20, width: 4096, height: 4096 },
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
  maxFilesize: 20,
  addRemoveLinks: true,
  headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
  params: { size: 20 },
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
      var file = files[i]
      this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
      $('form').append('<input type="hidden" name="invoice[]" value="' + file.file_name + '">')
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
  maxFilesize: 2000,
  acceptedFiles: '.jpeg,.jpg,.png,.gif',
  addRemoveLinks: true,
  headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
  params: { size: 2000, width: 4096, height: 4096 },
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
  maxFilesize: 2000,
  addRemoveLinks: true,
  headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
  params: { size: 2000 },
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
      var file = files[i]
      this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
      $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + file.file_name + '">')
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
var uploadedWithdrawalDocumentsMap = {}
Dropzone.options.withdrawalDocumentsDropzone = {
  url: '{{ route('admin.vehicles.storeMedia') }}',
  maxFilesize: 2000,
  addRemoveLinks: true,
  headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
  params: { size: 2000 },
  success: function (file, response) {
    $('form').append('<input type="hidden" name="withdrawal_documents[]" value="' + response.name + '">')
    uploadedWithdrawalDocumentsMap[file.name] = response.name
  },
  removedfile: function (file) {
    file.previewElement.remove()
    var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedWithdrawalDocumentsMap[file.name]
    $('form').find('input[name="withdrawal_documents[]"][value="' + name + '"]').remove()
  },
  init: function () {
@if(isset($vehicle) && $vehicle->withdrawal_documents)
    var files = {!! json_encode($vehicle->withdrawal_documents) !!}
    for (var i in files) {
      var file = files[i]
      this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
      $('form').append('<input type="hidden" name="withdrawal_documents[]" value="' + file.file_name + '">')
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
function newPurchasePayment(account_department_id) {
    const itemId = 1;
    const payment_method_id = document.getElementById('purchase_payment_method_id').value;
    const date = document.getElementById('purchase_date').value;
    const value = parseFloat(document.getElementById('purchase_value').value);

    if (!itemId || isNaN(value)) {
        showStatus('Por favor, selecione o item e insira o valor.', 'danger', account_department_id);
        return;
    }

    fetch(`{{ route('admin.vehicles.account-operations.store', ['vehicle' => $vehicle->id]) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            account_department_id: account_department_id,
            account_item_id: itemId,
            payment_method_id: payment_method_id,
            date: date,
            total: value,
            qty: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus('Pagamento registado com sucesso.', 'success', account_department_id);
            refreshPayments(data.account_department_id);
        } else {
            showStatus('Erro ao registar o pagamento.', 'danger', account_department_id);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showStatus('Erro de rede. Tente novamente.', 'danger', account_department_id);
    });
}

function newClientPayment(account_department_id) {
    const itemId = document.getElementById('client_payment_item').value;
    const payment_method_id = document.getElementById('client_payment_method_id').value;
    const date = document.getElementById('client_date').value;
    const value = parseFloat(document.getElementById('client_payment_value').value);

    if (!itemId || isNaN(value)) {
        showStatus('Por favor, selecione o item e insira o valor.', 'danger', account_department_id);
        return;
    }

    fetch(`{{ route('admin.vehicles.account-operations.store', ['vehicle' => $vehicle->id]) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            account_department_id: account_department_id,
            account_item_id: itemId,
            payment_method_id: payment_method_id,
            date: date,
            total: value,
            qty: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus('Pagamento registado com sucesso.', 'success', account_department_id);
            refreshPayments(data.account_department_id);
        } else {
            showStatus('Erro ao registar o pagamento.', 'danger', account_department_id);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showStatus('Erro de rede. Tente novamente.', 'danger', account_department_id);
    });
}

function editPayment(id, value, account_department_id) {
    const newValue = prompt("Novo valor do pagamento:", value);
    if (newValue === null) return;

    fetch(`/admin/account-operations/update/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ total: parseFloat(newValue) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus('Pagamento atualizado com sucesso.', 'success', account_department_id);
            refreshPayments(account_department_id);
        } else {
            showStatus('Erro ao atualizar o pagamento.', 'danger', account_department_id);
        }
    });
}

function deletePayment(id, account_department_id) {
    if (!confirm('Tem certeza que deseja apagar este pagamento?')) return;

    fetch(`/admin/account-operations/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus('Pagamento apagado com sucesso.', 'success', account_department_id);
            refreshPayments(account_department_id);
        } else {
            showStatus('Erro ao apagar o pagamento.', 'danger', account_department_id);
        }
    });
}

function showStatus(message, type, account_department_id) {
    let status = (account_department_id == 1)
        ? document.getElementById('payment-status')
        : document.getElementById('client-status');

    status.innerText = message;
    status.className = `alert alert-${type}`;
    status.style.display = 'block';
}

function refreshPayments(account_department_id) {
    fetch('/admin/vehicles/{{ $vehicle->id }}/get-payments/' + account_department_id)
        .then(response => response.json())
        .then(data => {
            const table = document.querySelector(`#table-department-${account_department_id}`);
            let tableBody = table.querySelector('tbody');
            if (!tableBody) {
                tableBody = document.createElement('tbody');
                table.appendChild(tableBody);
            }
            const balanceInput = document.getElementById(`balance-${account_department_id}`);

            // Atualiza a tabela
            let tbody = '';
            data.payments.forEach(op => {
                tbody += `
                    <tr>
                        <td>${op.date}</td>
                        <td>${op.item}</td>
                        <td>${op.total} €</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-warning" onclick="editPayment(${op.id}, ${op.total_raw}, ${account_department_id})">Editar</button>
                            <button type="button" class="btn btn-xs btn-danger" onclick="deletePayment(${op.id}, ${account_department_id})">Apagar</button>
                        </td>
                    </tr>
                `;
            });
            tableBody.innerHTML = tbody;

            // Atualiza o saldo
            if (balanceInput) { balanceInput.value = `${data.balance} €`; }
        });
}
</script>

<script>
var uploadedPaymentComprovantMap = {}
Dropzone.options.paymentComprovantDropzone = {
  url: '{{ route('admin.vehicles.storeMedia') }}',
  maxFilesize: 5,
  addRemoveLinks: true,
  headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
  params: { size: 5 },
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
      var file = files[i]
      this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
      $('form').append('<input type="hidden" name="payment_comprovant[]" value="' + file.file_name + '">')
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

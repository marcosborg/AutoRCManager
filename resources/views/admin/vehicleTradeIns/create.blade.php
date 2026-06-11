@extends('layouts.admin')

@section('content')
@php
    $tradeInErrors = $errors->getBag('trade_in');
    $checklist = [
        'has_vehicle_delivery_declaration' => 'Declaracao de entrega de viatura',
        'has_ipo' => 'Inspecao Periodica Obrigatoria (IPO) - Ficha',
        'has_internal_invoice' => 'Fatura interna',
        'has_reservation_extinction_authorization' => 'Autorizacao para extincao de reserva',
    ];
@endphp

<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Adicionar retoma sem venda associada</div>
        <div class="panel-body">
            <p class="text-muted">
                Esta operacao cria imediatamente uma viatura em stock e deixa a retoma pendente para verificacao pelo administrador.
            </p>

            <form method="POST" action="{{ route('admin.vehicle-trade-ins.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group {{ $tradeInErrors->has('trade_in_license') ? 'has-error' : '' }}">
                            <label for="trade_in_license">Matricula da retoma</label>
                            <input class="form-control" type="text" name="trade_in_license" id="trade_in_license" value="{{ old('trade_in_license') }}" required>
                            @if($tradeInErrors->has('trade_in_license'))<span class="help-block">{{ $tradeInErrors->first('trade_in_license') }}</span>@endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group {{ $tradeInErrors->has('trade_in_amount') ? 'has-error' : '' }}">
                            <label for="trade_in_amount">Valor da retoma</label>
                            <input class="form-control" type="number" name="trade_in_amount" id="trade_in_amount" value="{{ old('trade_in_amount') }}" step="0.01" min="0.01" required>
                            @if($tradeInErrors->has('trade_in_amount'))<span class="help-block">{{ $tradeInErrors->first('trade_in_amount') }}</span>@endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group {{ $tradeInErrors->has('trade_in_brand_id') ? 'has-error' : '' }}">
                            <label for="trade_in_brand_id">Marca</label>
                            <select class="form-control select2" name="trade_in_brand_id" id="trade_in_brand_id" required style="width: 100%;">
                                @foreach($brands as $id => $entry)
                                    <option value="{{ $id }}" {{ (string) old('trade_in_brand_id') === (string) $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($tradeInErrors->has('trade_in_brand_id'))<span class="help-block">{{ $tradeInErrors->first('trade_in_brand_id') }}</span>@endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group {{ $tradeInErrors->has('trade_in_model') ? 'has-error' : '' }}">
                            <label for="trade_in_model">Modelo</label>
                            <input class="form-control" type="text" name="trade_in_model" id="trade_in_model" value="{{ old('trade_in_model') }}" required>
                            @if($tradeInErrors->has('trade_in_model'))<span class="help-block">{{ $tradeInErrors->first('trade_in_model') }}</span>@endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group {{ $tradeInErrors->has('trade_in_year') ? 'has-error' : '' }}">
                            <label for="trade_in_year">Ano</label>
                            <input class="form-control" type="number" name="trade_in_year" id="trade_in_year" value="{{ old('trade_in_year') }}" min="1900" max="{{ now()->year + 1 }}" required>
                            @if($tradeInErrors->has('trade_in_year'))<span class="help-block">{{ $tradeInErrors->first('trade_in_year') }}</span>@endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group {{ $tradeInErrors->has('trade_in_kilometers') ? 'has-error' : '' }}">
                            <label for="trade_in_kilometers">Kms</label>
                            <input class="form-control" type="number" name="trade_in_kilometers" id="trade_in_kilometers" value="{{ old('trade_in_kilometers') }}" min="0" step="1" required>
                            @if($tradeInErrors->has('trade_in_kilometers'))<span class="help-block">{{ $tradeInErrors->first('trade_in_kilometers') }}</span>@endif
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">Checklist</div>
                    <div class="panel-body">
                        @foreach($checklist as $field => $label)
                            <input type="hidden" name="{{ $field }}" value="0">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="{{ $field }}" value="1" {{ old($field) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label for="trade_in_notes">Notas</label>
                    <textarea class="form-control" name="trade_in_notes" id="trade_in_notes" rows="3">{{ old('trade_in_notes') }}</textarea>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">Fotos e documentos</div>
                    <div class="panel-body">
                        <div class="form-group {{ $tradeInErrors->has('inicial') || $tradeInErrors->has('inicial.*') ? 'has-error' : '' }}">
                            <label>Fotos iniciais da aquisicao <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" name="inicial[]" multiple required>
                            @if($tradeInErrors->has('inicial'))<span class="help-block">{{ $tradeInErrors->first('inicial') }}</span>@endif
                            @if($tradeInErrors->has('inicial.*'))<span class="help-block">{{ $tradeInErrors->first('inicial.*') }}</span>@endif
                        </div>

                        @foreach(\App\Models\VehicleTradeIn::STANDALONE_DOCUMENT_COLLECTIONS as $collection => $label)
                            @php($required = in_array($collection, ['vehicle_delivery_declaration', 'internal_invoice'], true))
                            <div class="form-group {{ $tradeInErrors->has($collection) || $tradeInErrors->has($collection . '.*') ? 'has-error' : '' }}">
                                <label>{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
                                <input class="form-control" type="file" name="{{ $collection }}[]" multiple {{ $required ? 'required' : '' }}>
                                @if($tradeInErrors->has($collection))<span class="help-block">{{ $tradeInErrors->first($collection) }}</span>@endif
                                @if($tradeInErrors->has($collection . '.*'))<span class="help-block">{{ $tradeInErrors->first($collection . '.*') }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <button class="btn btn-success" type="submit">Criar retoma e viatura em stock</button>
                <a class="btn btn-default" href="{{ route('admin.vehicle-trade-ins.index', ['status' => \App\Models\VehicleTradeIn::STATUS_CONVERTED]) }}">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection

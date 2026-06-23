@php
    $tradeIns = $vehicle->trade_ins ?? collect();
    $tradeInChecklist = [
        'has_purchase_sale_rgpd' => 'Declaracao de Compra e Venda + RGPD',
        'has_ipo' => 'Inspecao Periodica Obrigatoria (IPO) - Ficha',
        'has_internal_invoice' => 'Fatura interna',
        'has_reservation_extinction_authorization' => 'Autorizacao do cliente para extincao de reserva',
    ];
    $tradeInUploads = \App\Models\VehicleTradeIn::DOCUMENT_COLLECTIONS;
    $tradeInErrors = $errors->getBag('trade_in');
    $tradeInDocumentsRequired = \App\Support\RolePreview::hasAnyEffectiveRole(auth()->user(), ['Stand']);
@endphp

<div class="panel panel-default" id="vehicle-trade-ins-panel">
    <div class="panel-heading">
        Retomas
        @if($tradeIns->where('status', \App\Models\VehicleTradeIn::STATUS_PENDING)->count())
            <span class="label label-warning">{{ $tradeIns->where('status', \App\Models\VehicleTradeIn::STATUS_PENDING)->count() }} pendente(s)</span>
        @endif
    </div>
    <div class="panel-body" style="padding: 10px;">
        <input form="vehicle-trade-in-create-form" type="hidden" name="create_trade_in_confirmed" id="create_trade_in_confirmed" value="0">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group {{ $tradeInErrors->has('trade_in_license') ? 'has-error' : '' }}">
                    <label for="trade_in_license">Matricula da retoma</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="text" name="trade_in_license" id="trade_in_license" value="{{ old('trade_in_license') }}" required>
                    @if($tradeInErrors->has('trade_in_license'))<span class="help-block">{{ $tradeInErrors->first('trade_in_license') }}</span>@endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group {{ $tradeInErrors->has('trade_in_amount') ? 'has-error' : '' }}">
                    <label for="trade_in_amount">Valor da retoma</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="number" name="trade_in_amount" id="trade_in_amount" value="{{ old('trade_in_amount') }}" step="0.01" min="0.01" required>
                    @if($tradeInErrors->has('trade_in_amount'))<span class="help-block">{{ $tradeInErrors->first('trade_in_amount') }}</span>@endif
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group {{ $tradeInErrors->has('trade_in_brand_id') ? 'has-error' : '' }}">
                    <label for="trade_in_brand_id">Marca</label>
                    <select class="form-control select2" form="vehicle-trade-in-create-form" name="trade_in_brand_id" id="trade_in_brand_id" required style="width: 100%;">
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
                    <input class="form-control" form="vehicle-trade-in-create-form" type="text" name="trade_in_model" id="trade_in_model" value="{{ old('trade_in_model') }}" required>
                    @if($tradeInErrors->has('trade_in_model'))<span class="help-block">{{ $tradeInErrors->first('trade_in_model') }}</span>@endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group {{ $tradeInErrors->has('trade_in_year') ? 'has-error' : '' }}">
                    <label for="trade_in_year">Ano</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="number" name="trade_in_year" id="trade_in_year" value="{{ old('trade_in_year') }}" min="1900" max="{{ now()->year + 1 }}" required>
                    @if($tradeInErrors->has('trade_in_year'))<span class="help-block">{{ $tradeInErrors->first('trade_in_year') }}</span>@endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group {{ $tradeInErrors->has('trade_in_kilometers') ? 'has-error' : '' }}">
                    <label for="trade_in_kilometers">Kms</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="number" name="trade_in_kilometers" id="trade_in_kilometers" value="{{ old('trade_in_kilometers') }}" min="0" step="1" required>
                    @if($tradeInErrors->has('trade_in_kilometers'))<span class="help-block">{{ $tradeInErrors->first('trade_in_kilometers') }}</span>@endif
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">Checklist - documentacao e acessorios</div>
            <div class="panel-body" style="padding: 8px;">
                @foreach($tradeInChecklist as $field => $label)
                    <input form="vehicle-trade-in-create-form" type="hidden" name="{{ $field }}" value="0">
                    <div class="checkbox" style="margin: 4px 0;">
                        <label style="font-weight: 400;">
                            <input form="vehicle-trade-in-create-form" type="checkbox" name="{{ $field }}" value="1" {{ old($field) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label for="trade_in_notes">Notas</label>
            <textarea class="form-control" form="vehicle-trade-in-create-form" name="trade_in_notes" id="trade_in_notes">{{ old('trade_in_notes') }}</textarea>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">Anexos da retoma</div>
            <div class="panel-body" style="padding: 8px;">
                <div class="form-group {{ $tradeInErrors->has('inicial') || $tradeInErrors->has('inicial.*') ? 'has-error' : '' }}">
                    <label>Fotos iniciais da aquisicao @if($tradeInDocumentsRequired)<span class="text-danger">*</span>@endif</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="file" name="inicial[]" multiple {{ $tradeInDocumentsRequired ? 'required' : '' }}>
                    @if($tradeInErrors->has('inicial'))<span class="help-block">{{ $tradeInErrors->first('inicial') }}</span>@endif
                    @if($tradeInErrors->has('inicial.*'))<span class="help-block">{{ $tradeInErrors->first('inicial.*') }}</span>@endif
                </div>
                @foreach($tradeInUploads as $collection => $label)
                    @php($requiredUpload = $tradeInDocumentsRequired && in_array($collection, ['purchase_sale_rgpd', 'internal_invoice'], true))
                    <div class="form-group {{ $tradeInErrors->has($collection) || $tradeInErrors->has($collection . '.*') ? 'has-error' : '' }}">
                        <label>
                            {{ $label }}
                            @if($requiredUpload)
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <input class="form-control" form="vehicle-trade-in-create-form" type="file" name="{{ $collection }}[]" multiple {{ $requiredUpload ? 'required' : '' }}>
                        @if($tradeInErrors->has($collection))<span class="help-block">{{ $tradeInErrors->first($collection) }}</span>@endif
                        @if($tradeInErrors->has($collection . '.*'))<span class="help-block">{{ $tradeInErrors->first($collection . '.*') }}</span>@endif
                    </div>
                @endforeach
            </div>
        </div>

        <button class="btn btn-warning btn-sm" id="create-trade-in-button" type="button">
            Criar retoma e viatura em stock
        </button>

        <hr>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th>Matricula</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Criado por</th>
                        <th>Documentos</th>
                        <th>Viatura em stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tradeIns as $tradeIn)
                        <tr>
                            <td>{{ $tradeIn->license }}</td>
                            <td>{{ number_format((float) $tradeIn->amount, 2, ',', '.') }} EUR</td>
                            <td>{{ \App\Models\VehicleTradeIn::STATUS_SELECT[$tradeIn->status] ?? $tradeIn->status }}</td>
                            <td>{{ $tradeIn->created_by->name ?? '-' }}</td>
                            <td>
                                @foreach($tradeInUploads as $collection => $label)
                                    @foreach($tradeIn->getMedia($collection) as $media)
                                        <a href="{{ $media->getUrl() }}" target="_blank" class="btn btn-xs btn-default" style="margin-bottom: 2px;">{{ $label }}</a>
                                    @endforeach
                                @endforeach
                            </td>
                            <td>
                                @if($tradeIn->created_vehicle_id)
                                    <a href="{{ route('admin.vehicles.edit', $tradeIn->created_vehicle_id) }}">#{{ $tradeIn->created_vehicle_id }}</a>
                                @elseif($tradeIn->status === \App\Models\VehicleTradeIn::STATUS_REJECTED)
                                    <span class="text-muted">{{ $tradeIn->rejection_reason }}</span>
                                @else
                                    <span class="text-muted">A aguardar verificacao</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Sem retomas registadas para esta venda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

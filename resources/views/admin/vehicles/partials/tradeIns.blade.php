@php
    $tradeIns = $vehicle->trade_ins ?? collect();
    $tradeInChecklist = [
        'has_purchase_sale_rgpd' => 'Declaracao de Compra e Venda + RGPD',
        'has_ipo' => 'Inspecao Periodica Obrigatoria (IPO) - Ficha',
        'has_internal_invoice' => 'Fatura interna',
        'has_reservation_extinction_authorization' => 'Autorizacao do cliente para extincao de reserva',
    ];
    $tradeInUploads = \App\Models\VehicleTradeIn::DOCUMENT_COLLECTIONS;
@endphp

<div class="panel panel-default" id="vehicle-trade-ins-panel">
    <div class="panel-heading">
        Retomas
        @if($tradeIns->where('status', \App\Models\VehicleTradeIn::STATUS_PENDING)->count())
            <span class="label label-warning">{{ $tradeIns->where('status', \App\Models\VehicleTradeIn::STATUS_PENDING)->count() }} pendente(s)</span>
        @endif
    </div>
    <div class="panel-body" style="padding: 10px;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                    <label for="trade_in_license">Matricula da retoma</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="text" name="license" id="trade_in_license" value="{{ old('license') }}" required>
                    @if($errors->has('license'))<span class="help-block">{{ $errors->first('license') }}</span>@endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                    <label for="trade_in_amount">Valor da retoma</label>
                    <input class="form-control" form="vehicle-trade-in-create-form" type="number" name="amount" id="trade_in_amount" value="{{ old('amount') }}" step="0.01" min="0.01" required>
                    @if($errors->has('amount'))<span class="help-block">{{ $errors->first('amount') }}</span>@endif
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
            <textarea class="form-control" form="vehicle-trade-in-create-form" name="notes" id="trade_in_notes">{{ old('notes') }}</textarea>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">Anexos da retoma</div>
            <div class="panel-body" style="padding: 8px;">
                @foreach($tradeInUploads as $collection => $label)
                    <div class="form-group">
                        <label>{{ $label }}</label>
                        <input class="form-control" form="vehicle-trade-in-create-form" type="file" name="{{ $collection }}[]" multiple>
                    </div>
                @endforeach
            </div>
        </div>

        <button class="btn btn-warning btn-sm" form="vehicle-trade-in-create-form" type="submit">
            Criar pedido de retoma
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
                        <th>Viatura criada</th>
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
                                    <span class="text-muted">A aguardar conversao</span>
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

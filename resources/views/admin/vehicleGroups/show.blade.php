@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            Lote: {{ $vehicleGroup->name }}
            @canany(['vehicle_group_edit', 'vehicle_lot_edit'])
                <a href="{{ route('admin.vehicle-groups.edit', $vehicleGroup->id) }}" class="btn btn-xs btn-info pull-right">Editar lote</a>
            @endcanany
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Cliente:</strong> {{ $vehicleGroup->customer->name ?? '-' }}</div>
                <div class="col-md-2"><strong>Tipo:</strong> {{ $vehicleGroup->type === 'unitario' ? 'Discriminado' : 'Global' }}</div>
                <div class="col-md-2"><strong>Estado:</strong> {{ $vehicleGroup->status }}</div>
                <div class="col-md-3"><strong>Distribuicao:</strong> {{ $vehicleGroup->distribution_mode === 'equal' ? 'Igual' : 'Proporcional' }}</div>
                <div class="col-md-2"><strong>Valor:</strong> &euro;{{ number_format($financial['target'], 2, ',', '.') }}</div>
            </div>
            @if($canApproveLots && !$vehicleGroup->approved_at)
                <hr>
                <form method="POST" action="{{ route('admin.vehicle-groups.approve', $vehicleGroup->id) }}">
                    @csrf
                    <button class="btn btn-success" type="submit">Aprovar lote</button>
                </form>
            @elseif($vehicleGroup->approved_at)
                <hr>
                <div><strong>Aprovado por:</strong> {{ $vehicleGroup->approver->name ?? '-' }} em {{ $vehicleGroup->approved_at }}</div>
            @endif
            @if($vehicleGroup->notes)
                <hr>
                <div><strong>Observacoes:</strong> {{ $vehicleGroup->notes }}</div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-default"><div class="panel-heading">Total venda</div><div class="panel-body"><h4>&euro;{{ number_format($financial['target'], 2, ',', '.') }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default"><div class="panel-heading">Recebido aprovado</div><div class="panel-body"><h4>&euro;{{ number_format($financial['paid'], 2, ',', '.') }}</h4></div></div>
        </div>
        <div class="col-md-2">
            <div class="panel panel-default"><div class="panel-heading">Faturado</div><div class="panel-body"><h4>&euro;{{ number_format($financial['invoiced'], 2, ',', '.') }}</h4></div></div>
        </div>
        <div class="col-md-2">
            <div class="panel panel-default"><div class="panel-heading">Caixa</div><div class="panel-body"><h4>&euro;{{ number_format($financial['cash'], 2, ',', '.') }}</h4></div></div>
        </div>
        <div class="col-md-2">
            <div class="panel panel-default"><div class="panel-heading">Por receber</div><div class="panel-body"><h4>&euro;{{ number_format($financial['balance'], 2, ',', '.') }}</h4></div></div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">Viaturas do lote</div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Viatura</th>
                        <th>Estado operacional</th>
                        <th>Original</th>
                        <th>Ajustado</th>
                        <th>Alocado</th>
                        <th>Recebido</th>
                        <th>Faturado</th>
                        <th>Caixa</th>
                        <th>Estado financeiro</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicleGroup->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('admin.vehicles.show', $item->vehicle_id) }}">
                                    {{ $item->vehicle->license ?? $item->vehicle->foreign_license ?? ('#' . $item->vehicle_id) }}
                                </a>
                                <div class="text-muted small">{{ $item->vehicle->brand->name ?? '' }} {{ $item->vehicle->model ?? '' }}</div>
                            </td>
                            <td>{{ $item->vehicle->general_state->name ?? '-' }}</td>
                            <td>&euro;{{ number_format($item->original_price ?? 0, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($item->adjusted_price ?? 0, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($item->allocated_amount ?? 0, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($item->paid_amount ?? 0, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($item->invoiced_amount ?? 0, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($item->cash_amount ?? 0, 2, ',', '.') }}</td>
                            <td>{{ $item->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted">Sem viaturas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canCreateLotPayments)
        <div class="panel panel-default">
            <div class="panel-heading">Submeter pagamento</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.vehicle-groups.payments.store', $vehicleGroup->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-2">
                            <label>Data</label>
                            <input class="form-control date" name="paid_at" value="{{ old('paid_at', now()->format(config('panel.date_format'))) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label>Metodo</label>
                            <select class="form-control select2" name="payment_method_id" required>
                                @foreach($paymentMethods as $id => $name)
                                    <option value="{{ $id }}" {{ old('payment_method_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><label>Recebido</label><input class="form-control" type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required></div>
                        <div class="col-md-2"><label>Faturado</label><input class="form-control" type="number" name="invoiced_amount" value="{{ old('invoiced_amount', 0) }}" step="0.01" min="0" required></div>
                        <div class="col-md-2"><label>Caixa</label><input class="form-control" type="number" name="cash_amount" value="{{ old('cash_amount', 0) }}" step="0.01" min="0" required></div>
                        <div class="col-md-2"><label>Comprovativo</label><input class="form-control" type="file" name="proof_file"></div>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Notas</label>
                        <textarea class="form-control" name="notes">{{ old('notes') }}</textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Submeter para aprovacao</button>
                </form>
            </div>
        </div>
    @endif

    <div class="panel panel-default">
        <div class="panel-heading">Pagamentos</div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Metodo</th>
                        <th>Recebido</th>
                        <th>Faturado</th>
                        <th>Caixa</th>
                        <th>Estado</th>
                        <th>Criado por</th>
                        <th>Comprovativo</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicleGroup->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at }}</td>
                            <td>{{ $payment->payment_method->name ?? '-' }}</td>
                            <td>&euro;{{ number_format($payment->amount, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($payment->invoiced_amount, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($payment->cash_amount, 2, ',', '.') }}</td>
                            <td>{{ $payment->approval_status }}</td>
                            <td>{{ $payment->creator->name ?? '-' }}</td>
                            <td>
                                @foreach($payment->proof_file as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                                @endforeach
                            </td>
                            <td>
                                @if($canApproveLots && $payment->approval_status === \App\Models\LotPayment::STATUS_PENDING)
                                    <form method="POST" action="{{ route('admin.vehicle-groups.payments.approve', [$vehicleGroup->id, $payment->id]) }}" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-xs btn-success" type="submit">Aprovar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.vehicle-groups.payments.reject', [$vehicleGroup->id, $payment->id]) }}" style="display:inline-block">
                                        @csrf
                                        <input type="hidden" name="rejection_reason" value="Rejeitado no detalhe do lote">
                                        <button class="btn btn-xs btn-danger" type="submit">Rejeitar</button>
                                    </form>
                                @elseif($payment->approval_status === \App\Models\LotPayment::STATUS_REJECTED)
                                    <span class="text-muted">{{ $payment->rejection_reason }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted">Sem pagamentos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

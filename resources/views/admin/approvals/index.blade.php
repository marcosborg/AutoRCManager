@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Pagamentos pendentes</div>
                <div class="panel-body"><h3>{{ $pendingPayments->count() }}</h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Lotes sem aprovacao</div>
                <div class="panel-body"><h3>{{ $pendingLots->count() }}</h3></div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">Aguardam confirmacao</div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Cliente</th>
                        <th>Metodo</th>
                        <th>Recebido</th>
                        <th>Faturado</th>
                        <th>Banco</th>
                        <th>Caixa 1</th>
                        <th>Caixa 2</th>
                        <th>Criado por</th>
                        <th>Comprovativo</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingPayments as $payment)
                        <tr>
                            <td><a href="{{ route('admin.vehicle-groups.show', $payment->vehicle_group_id) }}">{{ $payment->lot->name ?? '-' }}</a></td>
                            <td>{{ $payment->lot->customer->name ?? '-' }}</td>
                            <td>{{ $payment->payment_method->name ?? '-' }}</td>
                            <td>&euro;{{ number_format($payment->amount, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($payment->invoiced_amount, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($payment->bank_amount, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($payment->cash_amount, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($payment->cash_2_amount, 2, ',', '.') }}</td>
                            <td>{{ $payment->creator->name ?? '-' }}</td>
                            <td>
                                @foreach($payment->proof_file as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                                @endforeach
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.vehicle-groups.payments.approve', [$payment->vehicle_group_id, $payment->id]) }}" style="display:inline-block">
                                    @csrf
                                    <button class="btn btn-xs btn-success" type="submit">Aprovar</button>
                                </form>
                                <form method="POST" action="{{ route('admin.vehicle-groups.payments.reject', [$payment->vehicle_group_id, $payment->id]) }}" style="display:inline-block">
                                    @csrf
                                    <input class="form-control input-sm" style="width: 180px; display:inline-block" name="rejection_reason" placeholder="Motivo" required>
                                    <button class="btn btn-xs btn-danger" type="submit">Rejeitar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted">Sem pagamentos pendentes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">Lotes sem aprovacao formal</div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingLots as $lot)
                        <tr>
                            <td><a href="{{ route('admin.vehicle-groups.show', $lot->id) }}">{{ $lot->name }}</a></td>
                            <td>{{ $lot->customer->name ?? '-' }}</td>
                            <td>&euro;{{ number_format($lot->effective_total, 2, ',', '.') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.vehicle-groups.approve', $lot->id) }}">
                                    @csrf
                                    <button class="btn btn-xs btn-success" type="submit">Aprovar lote</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">Sem lotes pendentes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

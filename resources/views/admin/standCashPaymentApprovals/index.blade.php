@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Validação de numerário do Stand</div>
        <div class="panel-body">
            <form method="GET" class="form-inline" style="margin-bottom: 12px;">
                <div class="form-group">
                    <label for="status">Estado</label>
                    <select class="form-control" name="status" id="status">
                        <option value="">Pendentes</option>
                        @foreach(\App\Models\StandCashPaymentApproval::STATUS_SELECT as $status => $label)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-default" href="{{ route('admin.stand-cash-payment-approvals.index') }}">Limpar</a>
            </form>
        </div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Viatura</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Criado por</th>
                        <th>Estado</th>
                        <th>Caixa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $approval)
                        <tr>
                            <td>{{ $approval->payment->paid_at ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.vehicles.edit', $approval->vehicle_id) }}">
                                    {{ $approval->vehicle->license ?? $approval->vehicle->foreign_license ?? ('#' . $approval->vehicle_id) }}
                                </a>
                                <div class="text-muted small">{{ $approval->vehicle->brand->name ?? '' }} {{ $approval->vehicle->model ?? '' }}</div>
                            </td>
                            <td>{{ $approval->vehicle->client->name ?? '-' }}</td>
                            <td>{{ number_format((float) optional($approval->payment)->amount, 2, ',', '.') }} EUR</td>
                            <td>{{ $approval->created_by->name ?? '-' }}</td>
                            <td>
                                {{ \App\Models\StandCashPaymentApproval::STATUS_SELECT[$approval->status] ?? $approval->status }}
                                @if($approval->approved_at)
                                    <div class="text-muted small">{{ $approval->approved_at->format('Y-m-d H:i') }} por {{ $approval->approved_by->name ?? '-' }}</div>
                                @elseif($approval->rejected_at)
                                    <div class="text-muted small">{{ $approval->rejected_at->format('Y-m-d H:i') }} por {{ $approval->approved_by->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $approval->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                @if($approval->cash_operation_id)
                                    <a href="{{ route('admin.cash.index', ['highlight' => $approval->cash_operation_id]) }}">Movimento #{{ $approval->cash_operation_id }}</a>
                                @else
                                    <span class="text-muted">A aguardar validação</span>
                                @endif
                            </td>
                            <td style="min-width: 260px;">
                                @if($approval->status === \App\Models\StandCashPaymentApproval::STATUS_PENDING)
                                    <form method="POST" action="{{ route('admin.stand-cash-payment-approvals.approve', $approval) }}" style="display:inline-block;">
                                        @csrf
                                        <button class="btn btn-xs btn-success" type="submit">Validar entrega</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.stand-cash-payment-approvals.reject', $approval) }}" style="margin-top: 6px;">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" name="rejection_reason" placeholder="Motivo" required>
                                            <span class="input-group-btn">
                                                <button class="btn btn-danger" type="submit">Rejeitar</button>
                                            </span>
                                        </div>
                                    </form>
                                @else
                                    <a class="btn btn-xs btn-default" href="{{ route('admin.vehicles.edit', $approval->vehicle_id) }}">Abrir viatura</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted">Sem validações de numerário.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $approvals->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Validacao de fechos de venda</div>
        <div class="panel-body">
            <form method="GET" class="form-inline" style="margin-bottom: 12px;">
                <div class="form-group">
                    <label for="status">Estado</label>
                    <select class="form-control" name="status" id="status">
                        <option value="">Pendentes</option>
                        @foreach(\App\Models\SaleClosureApproval::STATUS_SELECT as $status => $label)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="closed_by_id">Utilizador</label>
                    <select class="form-control" name="closed_by_id" id="closed_by_id">
                        <option value="">Todos</option>
                        @foreach($users as $id => $name)
                            <option value="{{ $id }}" {{ (string) request('closed_by_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_from">De</label>
                    <input class="form-control" type="date" name="date_from" id="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="form-group">
                    <label for="date_to">Ate</label>
                    <input class="form-control" type="date" name="date_to" id="date_to" value="{{ request('date_to') }}">
                </div>
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-default" href="{{ route('admin.sale-closure-approvals.index') }}">Limpar</a>
                <a class="btn btn-success" href="{{ route('admin.sale-closure-approvals.export', request()->query()) }}">Exportar Excel</a>
            </form>
        </div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data fecho</th>
                        <th>Viatura</th>
                        <th>Cliente</th>
                        <th>Utilizador</th>
                        <th>Origem</th>
                        <th>Total venda</th>
                        <th>Pagamentos</th>
                        <th>Retomas</th>
                        <th>Em divida</th>
                        <th>Estado</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $approval)
                        <tr>
                            <td>{{ optional($approval->closed_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.vehicles.edit', $approval->vehicle_id) }}">
                                    {{ $approval->vehicle->license ?? $approval->vehicle->foreign_license ?? ('#' . $approval->vehicle_id) }}
                                </a>
                                <div class="text-muted small">{{ $approval->vehicle->brand->name ?? '' }} {{ $approval->vehicle->model ?? '' }}</div>
                            </td>
                            <td>{{ $approval->vehicle->client->name ?? '-' }}</td>
                            <td>{{ $approval->closed_by->name ?? '-' }}</td>
                            <td>{{ \App\Models\SaleClosureApproval::TRIGGER_SELECT[$approval->trigger_type] ?? $approval->trigger_type }}</td>
                            <td>{{ number_format((float) $approval->sales_total, 2, ',', '.') }} EUR</td>
                            <td>{{ number_format((float) $approval->client_payments_total, 2, ',', '.') }} EUR</td>
                            <td>{{ number_format((float) $approval->trade_ins_total, 2, ',', '.') }} EUR</td>
                            <td>{{ number_format((float) $approval->outstanding_amount, 2, ',', '.') }} EUR</td>
                            <td>
                                {{ \App\Models\SaleClosureApproval::STATUS_SELECT[$approval->status] ?? $approval->status }}
                                @if($approval->approved_at)
                                    <div class="text-muted small">{{ $approval->approved_at->format('Y-m-d H:i') }} por {{ $approval->approved_by->name ?? '-' }}</div>
                                @elseif($approval->rejected_at)
                                    <div class="text-muted small">{{ $approval->rejected_at->format('Y-m-d H:i') }} por {{ $approval->approved_by->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $approval->rejection_reason }}</div>
                                @endif
                            </td>
                            <td style="min-width: 260px;">
                                @if($approval->status === \App\Models\SaleClosureApproval::STATUS_PENDING)
                                    <form method="POST" action="{{ route('admin.sale-closure-approvals.approve', $approval) }}" style="display:inline-block;">
                                        @csrf
                                        <button class="btn btn-xs btn-success" type="submit">Validar fecho</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.sale-closure-approvals.reject', $approval) }}" style="margin-top: 6px;">
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
                            <td colspan="11" class="text-muted">Sem fechos de venda por validar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $approvals->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

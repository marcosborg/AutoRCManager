@extends('layouts.admin')
@section('styles')
@parent
<style>
    .dashboard-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 24px;
        margin-bottom: 18px;
    }

    .dashboard-metrics .small-box {
        min-height: 92px;
        margin-bottom: 0;
    }

    .dashboard-metrics .small-box .inner {
        min-height: 92px;
    }

    .dashboard-metrics .small-box h3 {
        font-size: 30px;
        line-height: 1.1;
        white-space: nowrap;
    }

    @media (max-width: 1199px) {
        .dashboard-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .dashboard-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
@section('content')
<div class="content">
    <h3 class="page-title">Dashboard</h3>

    @if(session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="dashboard-metrics">
        <div>
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>{{ $business['month_count'] }}</h3>
                    <p>Viaturas vendidas este mes</p>
                </div>
                <div class="icon"><i class="fa fa-car"></i></div>
            </div>
        </div>
        <div>
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{ number_format($business['month_total'], 0, ',', '.') }} EUR</h3>
                    <p>Volume deste mes</p>
                </div>
                <div class="icon"><i class="fa fa-eur"></i></div>
            </div>
        </div>
        <div>
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>{{ number_format($business['year_total'], 0, ',', '.') }} EUR</h3>
                    <p>Volume anual</p>
                </div>
                <div class="icon"><i class="fa fa-line-chart"></i></div>
            </div>
        </div>
        <div>
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>{{ $business['stock_count'] }}</h3>
                    <p>Viaturas em stock</p>
                </div>
                <div class="icon"><i class="fa fa-cubes"></i></div>
            </div>
        </div>
    </div>

    <div class="dashboard-metrics">
        <a href="{{ route('admin.part-orders.index', ['delayed' => 1]) }}">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>{{ $partOrderStats['delayed_orders'] ?? 0 }}</h3>
                    <p>Encomendas atrasadas</p>
                </div>
                <div class="icon"><i class="fa fa-exclamation-triangle"></i></div>
            </div>
        </a>
        <a href="{{ route('admin.part-orders.index') }}">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3>{{ $partOrderStats['pending_items'] ?? 0 }}</h3>
                    <p>Peças pendentes</p>
                </div>
                <div class="icon"><i class="fa fa-cogs"></i></div>
            </div>
        </a>
        <a href="{{ route('admin.part-payments.index', ['overdue' => 1]) }}">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3>{{ $partOrderStats['overdue_payments'] ?? 0 }}</h3>
                    <p>Pagamentos vencidos</p>
                </div>
                <div class="icon"><i class="fa fa-credit-card"></i></div>
            </div>
        </a>
        <a href="{{ route('admin.part-orders.index') }}">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>{{ $partOrderStats['vehicles_waiting_parts'] ?? 0 }}</h3>
                    <p>Viaturas à espera de peças</p>
                </div>
                <div class="icon"><i class="fa fa-wrench"></i></div>
            </div>
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Tarefas para hoje
                    <a href="{{ route('admin.systemCalendar') }}" class="btn btn-xs btn-default pull-right">Calendario</a>
                </div>
                <div class="panel-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tarefa</th>
                                <th>Data</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasksToday as $task)
                                <tr>
                                    <td>{{ $task->title }}</td>
                                    <td>{{ $task->due_date }}</td>
                                    <td>{{ $task->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Sem tarefas pendentes para hoje.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Mudancas de estado por verificar
                    <a href="{{ route('admin.vehicle-state-transfers.index') }}" class="btn btn-xs btn-default pull-right">Historico</a>
                </div>
                <div class="panel-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Estado</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stateChanges as $transfer)
                                <tr>
                                    <td>
                                        @if($transfer->vehicle)
                                            <a href="{{ route('admin.vehicles.edit', $transfer->vehicle->id) }}">
                                                {{ $transfer->vehicle->license ?? $transfer->vehicle->foreign_license ?? 'Viatura #' . $transfer->vehicle->id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $transfer->from_general_state->name ?? '-' }} &rarr; {{ $transfer->to_general_state->name ?? '-' }}</td>
                                    <td>{{ optional($transfer->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Sem mudancas por verificar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    IUC a pagamento em {{ $currentIucMonthLabel ?? 'este mes' }}
                    <div class="pull-right">
                        <a href="{{ route('admin.iuc-due.export') }}" class="btn btn-xs btn-success">
                            <i class="fa fa-file-excel-o"></i> Excel
                        </a>
                        <span class="label label-info">{{ $iucDueVehicles->count() ?? 0 }}</span>
                    </div>
                </div>
                <div class="panel-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Matricula</th>
                                <th>Viatura</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($iucDueVehicles as $vehicle)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}">
                                            {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matricula' }}
                                        </a>
                                    </td>
                                    <td>{{ $vehicle->brand->name ?? '' }} {{ $vehicle->model ?? '' }}</td>
                                    <td>{{ $vehicle->general_state->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Sem viaturas com IUC a pagamento neste mes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">Ultimas viaturas vendidas</div>
                <div class="panel-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Cliente</th>
                                <th>Venda</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestSoldVehicles as $vehicle)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}">
                                            {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matricula' }}
                                            {{ $vehicle->brand->name ?? '' }} {{ $vehicle->model ?? '' }}
                                        </a>
                                    </td>
                                    <td>{{ $vehicle->client->name ?? '-' }}</td>
                                    <td>{{ $vehicle->sale_date }}</td>
                                    <td class="text-right">{{ number_format((float) ($vehicle->pvp ?? 0), 2, ',', '.') }} EUR</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Sem vendas registadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">Ultimas adjudicacoes</div>
                <div class="panel-body table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Cliente</th>
                                <th>Atualizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestAdjudications as $transfer)
                                @php($vehicle = $transfer->vehicle)
                                <tr>
                                    <td>
                                        @if($vehicle)
                                            <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}">
                                                {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matricula' }}
                                                {{ $vehicle->brand->name ?? '' }} {{ $vehicle->model ?? '' }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($vehicle && $vehicle->client)
                                            <a href="{{ route('admin.clients.edit', $vehicle->client->id) }}">{{ $vehicle->client->name }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ optional($transfer->created_at)->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Sem adjudicacoes registadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

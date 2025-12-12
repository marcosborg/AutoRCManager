@extends('layouts.admin')

@section('content')
    <div class="content">
        <div class="panel panel-default">
            <div class="panel-heading">
                Grupo: {{ $vehicleGroup->name }}
                @if($vehicleGroup->wholesale_pvp > 0)
                    <span class="label label-success" style="margin-left: 10px;">
                        PVP atacado: &euro; {{ number_format($vehicleGroup->wholesale_pvp, 2, ',', '.') }}
                    </span>
                @endif

                @can('vehicle_group_edit')
                    <a href="{{ route('admin.vehicle-groups.edit', $vehicleGroup->id) }}" class="btn btn-xs btn-info pull-right">
                        Editar grupo
                    </a>
                @endcan
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Clientes</h4>
                        @if($vehicleGroup->clients->isEmpty())
                            <p class="text-muted">Sem clientes associados.</p>
                        @else
                            @foreach($vehicleGroup->clients as $client)
                                <span class="label label-info" style="display: inline-block; margin-bottom: 5px; margin-right: 5px;">
                                    {{ $client->name }}
                                </span>
                            @endforeach
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h4>Viaturas</h4>
                        @if($vehicleGroup->vehicles->isEmpty())
                            <p class="text-muted">Nenhuma viatura no grupo.</p>
                        @else
                            @foreach($vehicleGroup->vehicles as $vehicle)
                                <a href="{{ route('admin.financial.index', $vehicle->id) }}" class="label label-primary" style="display: inline-block; margin-bottom: 5px; margin-right: 5px;">
                                    {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matricula' }}
                                    @if($vehicle->brand || $vehicle->model)
                                        <small>{{ $vehicle->brand->name ?? '' }} {{ $vehicle->model }}</small>
                                    @endif
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Aquisicao</strong>
                    </div>
                    <div class="panel-body">
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span><strong>Preco total de compra</strong></span>
                            <span><strong>€{{ number_format($financial['purchasePrice'], 2, ',', '.') }}</strong></span>
                        </div>

                        <hr>

                        @forelse ($operationsByDepartment['aquisition'] as $op)
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>
                                    {{ $op->account_item->name ?? 'Item' }}
                                    @if($op->vehicle)
                                        <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                    @endif
                                </span>
                                <span>€{{ number_format($op->total, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Sem pagamentos registados.</p>
                        @endforelse

                        <hr>

                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Total pago</strong></span>
                            <span><strong>€{{ number_format($financial['purchaseTotal'], 2, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Saldo por pagar</strong></span>
                            <span><strong>€{{ number_format($financial['purchaseBalance'], 2, ',', '.') }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Venda</strong>
                    </div>
                    <div class="panel-body">
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span><strong>Valor final alvo</strong></span>
                            <span><strong>€{{ number_format($financial['finalSalesTarget'], 2, ',', '.') }}</strong></span>
                        </div>

                        <hr>

                        @forelse ($operationsByDepartment['sale'] as $op)
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>
                                    {{ $op->account_item->name ?? 'Item' }}
                                    @if($op->vehicle)
                                        <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                    @endif
                                </span>
                                <span>€{{ number_format($op->total, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Nenhum recebimento registado.</p>
                        @endforelse

                        <hr>

                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Total recebido</strong></span>
                            <span><strong>€{{ number_format($financial['saleTotal'], 2, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Saldo por receber</strong></span>
                            <span><strong>€{{ number_format($financial['saleBalance'], 2, ',', '.') }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Reconciliação</strong>
                    </div>
                    <div class="panel-body">
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Investimento (compra)</span>
                            <span>€{{ number_format($financial['purchaseTotal'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Investimento (oficina)</span>
                            <span>€{{ number_format($financial['garageTotal'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Investimento (mão de obra)</span>
                            <span>€{{ number_format($financial['labourCost'], 2, ',', '.') }}</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span><strong>Total investido</strong></span>
                            <span><strong>€{{ number_format($financial['invested'], 2, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Valor final de venda (alvo)</span>
                            <span>€{{ number_format($financial['finalSalesTarget'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Total recebido</span>
                            <span>€{{ number_format($financial['saleTotal'], 2, ',', '.') }}</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span><strong>Lucro / prejuízo</strong></span>
                            <span>
                                <strong class="{{ $financial['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    €{{ number_format($financial['profit'], 2, ',', '.') }}
                                </strong>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><strong>ROI</strong></span>
                            <span><strong>{{ number_format($financial['roi'], 2, ',', '.') }}%</strong></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><em>Lucro teórico</em></span>
                            <span><em>€{{ number_format($financial['theoreticalProfit'], 2, ',', '.') }}</em></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Oficina</strong>
                    </div>
                    <div class="panel-body">
                        @forelse ($operationsByDepartment['garage'] as $op)
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>
                                    {{ $op->account_item->name ?? 'Item' }}
                                    @if($op->vehicle)
                                        <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                    @endif
                                </span>
                                <span>€{{ number_format($op->total, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Nenhuma operação registada.</p>
                        @endforelse

                        <hr>

                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Total oficina</strong></span>
                            <span><strong>€{{ number_format($financial['garageTotal'], 2, ',', '.') }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Mão de obra</strong>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Viatura</th>
                                    <th>Mecânico</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Minutos</th>
                                    <th>Custo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($timelogs as $log)
                                    <tr>
                                        <td>{{ $log->vehicle->license ?? 'Sem matricula' }}</td>
                                        <td>{{ $log->user?->name ?? 'Desconhecido' }}</td>
                                        <td>{{ $log->start_time }}</td>
                                        <td>{{ $log->end_time }}</td>
                                        <td>{{ $log->rounded_minutes }}</td>
                                        <td>€{{ number_format(($log->rounded_minutes / 60) * $hourPrice, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">Nenhum registo de mão de obra.</td>
                                    </tr>
                                @endforelse
                                <tr>
                                    <td colspan="4"><strong>Total</strong></td>
                                    <td><strong>{{ $financial['totalMinutes'] }} min</strong></td>
                                    <td><strong>€{{ number_format($financial['labourCost'], 2, ',', '.') }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Detalhe por viatura</strong>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Viatura</th>
                                        <th>Alvo venda</th>
                                        <th>Recebido</th>
                                        <th>Investido</th>
                                        <th>Lucro</th>
                                        <th>Saldo venda</th>
                                        <th>M.O. (min)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vehicleBreakdown as $row)
                                        <tr>
                                            <td>
                                                {{ $row['vehicle']->license ?? 'Sem matricula' }}
                                                <div class="text-muted small">
                                                    {{ $row['vehicle']->brand->name ?? '' }} {{ $row['vehicle']->model }}
                                                </div>
                                            </td>
                                            <td>€{{ number_format($row['sale_target'], 2, ',', '.') }}</td>
                                            <td>€{{ number_format($row['sale_total'], 2, ',', '.') }}</td>
                                            <td>€{{ number_format($row['invested'], 2, ',', '.') }}</td>
                                            <td class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                €{{ number_format($row['profit'], 2, ',', '.') }}
                                            </td>
                                            <td>€{{ number_format($row['sale_balance'], 2, ',', '.') }}</td>
                                            <td>{{ $row['minutes'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-muted">Sem viaturas para apresentar.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

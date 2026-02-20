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
                                <a href="{{ route('admin.vehicles.show', $vehicle->id) }}" class="label label-primary" style="display: inline-block; margin-bottom: 5px; margin-right: 5px;">
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
                            <span><strong>â‚¬{{ number_format($financial['purchasePrice'], 2, ',', '.') }}</strong></span>
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
                                <span>â‚¬{{ number_format($op->total, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Sem pagamentos registados.</p>
                        @endforelse

                        <hr>

                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Total pago</strong></span>
                            <span><strong>â‚¬{{ number_format($financial['purchaseTotal'], 2, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Saldo por pagar</strong></span>
                            <span><strong>â‚¬{{ number_format($financial['purchaseBalance'], 2, ',', '.') }}</strong></span>
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
                            <span><strong>â‚¬{{ number_format($financial['finalSalesTarget'], 2, ',', '.') }}</strong></span>
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
                                <span>â‚¬{{ number_format($op->total, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Nenhum recebimento registado.</p>
                        @endforelse

                        <hr>

                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Total recebido</strong></span>
                            <span><strong>â‚¬{{ number_format($financial['saleTotal'], 2, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Saldo por receber</strong></span>
                            <span><strong>â‚¬{{ number_format($financial['saleBalance'], 2, ',', '.') }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>ReconciliaÃ§Ã£o</strong>
                    </div>
                    <div class="panel-body">
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Investimento (compra)</span>
                            <span>â‚¬{{ number_format($financial['purchaseTotal'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Investimento (oficina)</span>
                            <span>â‚¬{{ number_format($financial['garageTotal'], 2, ',', '.') }}</span>
                        </div>
                        <hr>

                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span><strong>Total investido</strong></span>
                            <span><strong>â‚¬{{ number_format($financial['invested'], 2, ',', '.') }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Valor final de venda (alvo)</span>
                            <span>â‚¬{{ number_format($financial['finalSalesTarget'], 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>Total recebido</span>
                            <span>â‚¬{{ number_format($financial['saleTotal'], 2, ',', '.') }}</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span><strong>Lucro / prejuÃ­zo</strong></span>
                            <span>
                                <strong class="{{ $financial['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    â‚¬{{ number_format($financial['profit'], 2, ',', '.') }}
                                </strong>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><strong>ROI</strong></span>
                            <span><strong>{{ number_format($financial['roi'], 2, ',', '.') }}%</strong></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><em>Lucro teÃ³rico</em></span>
                            <span><em>â‚¬{{ number_format($financial['theoreticalProfit'], 2, ',', '.') }}</em></span>
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
                                <span>â‚¬{{ number_format($op->total, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Nenhuma operaÃ§Ã£o registada.</p>
                        @endforelse

                        <hr>

                        <div class="d-flex justify-content-between py-1">
                            <span><strong>Total oficina</strong></span>
                            <span><strong>â‚¬{{ number_format($financial['garageTotal'], 2, ',', '.') }}</strong></span>
                        </div>
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
                                            <td>â‚¬{{ number_format($row['sale_target'], 2, ',', '.') }}</td>
                                            <td>â‚¬{{ number_format($row['sale_total'], 2, ',', '.') }}</td>
                                            <td>â‚¬{{ number_format($row['invested'], 2, ',', '.') }}</td>
                                            <td class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                â‚¬{{ number_format($row['profit'], 2, ',', '.') }}
                                            </td>
                                            <td>â‚¬{{ number_format($row['sale_balance'], 2, ',', '.') }}</td>
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






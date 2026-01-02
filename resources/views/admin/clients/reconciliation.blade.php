@extends('layouts.admin')

@section('content')
    <div class="content">
        @php
            $formatMoney = function ($value) {
                return '&euro; ' . number_format((float) $value, 2, ',', '.');
            };
        @endphp

        <div class="panel panel-default">
            <div class="panel-heading">
                Contas do cliente: {{ $client->name }}

                <div class="pull-right">
                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn btn-xs btn-info">Editar cliente</a>
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-xs btn-default">Voltar</a>
                </div>
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    @if($clientViewMode)
                        <div class="col-md-4 col-md-offset-4">
                            <div class="well well-sm">
                                <div><strong>EM FALTA</strong></div>
                                <div class="lead">{!! $formatMoney($financial['saleBalance']) !!}</div>
                            </div>
                        </div>
                    @else
                        <div class="col-md-3">
                            <div class="well well-sm">
                                <div><strong>Compra prevista</strong></div>
                                <div class="lead">{!! $formatMoney($financial['purchasePrice']) !!}</div>
                                <small>Pago: {!! $formatMoney($financial['purchaseTotal']) !!}</small><br>
                                <small>Em falta: {!! $formatMoney($financial['purchaseBalance']) !!}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm">
                                <div><strong>Venda alvo</strong></div>
                                <div class="lead">{!! $formatMoney($financial['finalSalesTarget']) !!}</div>
                                <small>Recebido: {!! $formatMoney($financial['saleTotal']) !!}</small><br>
                                <small>Por receber: {!! $formatMoney($financial['saleBalance']) !!}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm">
                                <div><strong>Investido (pago)</strong></div>
                                <div class="lead">{!! $formatMoney($financial['invested']) !!}</div>
                                <small>Oficina: {!! $formatMoney($financial['garageTotal']) !!}</small><br>
                                <small>M&atilde;o de obra: {!! $formatMoney($financial['labourCost']) !!} ({{ $financial['totalMinutes'] }} min)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm">
                                <div><strong>Resultado</strong></div>
                                <div class="lead {{ $financial['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {!! $formatMoney($financial['profit']) !!}
                                </div>
                                <small>ROI: {{ number_format($financial['roi'], 2, ',', '.') }}%</small><br>
                                <small>Lucro te&oacute;rico: {!! $formatMoney($financial['theoreticalProfit']) !!}</small>
                            </div>
                        </div>
                    @endif
                </div>
                @if(! $clientViewMode)
                    <p class="text-muted">Custo/hora considerado: {!! $formatMoney($hourPrice) !!}</p>
                @endif
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                Grupos de viaturas deste cliente
            </div>
            <div class="panel-body">
                @if($groupBreakdown->isEmpty())
                    <p class="text-muted">Sem grupos associados ao cliente.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Grupo</th>
                                    <th class="text-center"># Viaturas</th>
                                    @if($clientViewMode)
                                        <th class="text-right">Em falta</th>
                                    @else
                                        <th class="text-right">Compra paga / falta</th>
                                        <th class="text-right">Venda recebida / por receber</th>
                                        <th class="text-right">Investido</th>
                                        <th class="text-right">Lucro</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupBreakdown as $row)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.vehicle-groups.show', $row['group']->id) }}">
                                                {{ $row['group']->name }}
                                            </a>
                                        </td>
                                        <td class="text-center">{{ $row['vehicles_count'] }}</td>
                                        @if($clientViewMode)
                                            <td class="text-right">
                                                <strong>{!! $formatMoney($row['financial']['saleBalance']) !!}</strong>
                                            </td>
                                        @else
                                            <td class="text-right">
                                                <strong>{!! $formatMoney($row['financial']['purchaseTotal']) !!}</strong><br>
                                                <small class="text-muted">Falta: {!! $formatMoney($row['financial']['purchaseBalance']) !!}</small>
                                            </td>
                                            <td class="text-right">
                                                <strong>{!! $formatMoney($row['financial']['saleTotal']) !!}</strong><br>
                                                <small class="text-muted">Falta: {!! $formatMoney($row['financial']['saleBalance']) !!}</small>
                                            </td>
                                            <td class="text-right">{!! $formatMoney($row['financial']['invested']) !!}</td>
                                            <td class="text-right">
                                                <span class="{{ $row['financial']['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {!! $formatMoney($row['financial']['profit']) !!}
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                Viaturas do cliente
            </div>
            <div class="panel-body">
                @if($vehicleBreakdown->isEmpty())
                    <p class="text-muted">Este cliente ainda n&atilde;o tem viaturas associadas.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Viatura</th>
                                    <th>Grupos</th>
                                    @if($clientViewMode)
                                        <th class="text-right">Em falta</th>
                                    @else
                                        <th class="text-right">Compra prev.</th>
                                        <th class="text-right">Pago</th>
                                        <th class="text-right">Em falta</th>
                                        <th class="text-right">Venda alvo</th>
                                        <th class="text-right">Recebido</th>
                                        <th class="text-right">Por receber</th>
                                        <th class="text-right">Investido</th>
                                        <th class="text-right">Lucro</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicleBreakdown as $row)
                                    @php
                                        $vehicle = $row['vehicle'];
                                    @endphp
                                    <tr>
                                        <td>
                                            @if(! $clientViewMode)
                                                <a href="{{ route('admin.financial.index', $vehicle->id) }}">
                                                    {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matricula' }}
                                                </a>
                                            @else
                                                {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matricula' }}
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $vehicle->brand->name ?? '' }} {{ $vehicle->model }}</small>
                                        </td>
                                        <td>
                                            @forelse($row['groups'] as $group)
                                                <span class="label label-default" style="display: inline-block; margin-bottom: 2px;">{{ $group }}</span>
                                            @empty
                                                <span class="text-muted">Sem grupo</span>
                                            @endforelse
                                        </td>
                                        @if($clientViewMode)
                                            <td class="text-right">{!! $formatMoney($row['sale_balance']) !!}</td>
                                        @else
                                            <td class="text-right">{!! $formatMoney($row['purchase_price']) !!}</td>
                                            <td class="text-right">{!! $formatMoney($row['purchase_total']) !!}</td>
                                            <td class="text-right">{!! $formatMoney($row['purchase_balance']) !!}</td>
                                            <td class="text-right">{!! $formatMoney($row['sale_target']) !!}</td>
                                            <td class="text-right">{!! $formatMoney($row['sale_total']) !!}</td>
                                            <td class="text-right">{!! $formatMoney($row['sale_balance']) !!}</td>
                                            <td class="text-right">{!! $formatMoney($row['invested']) !!}</td>
                                            <td class="text-right">
                                                <span class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {!! $formatMoney($row['profit']) !!}
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                Operacoes registadas
            </div>
            <div class="panel-body">
                <div class="row">
                    @if(! $clientViewMode)
                        <div class="col-md-4">
                            <h4>Compra</h4>
                            @if($operationsByDepartment['aquisition']->isEmpty())
                                <p class="text-muted">Sem pagamentos registados.</p>
                            @else
                                <ul class="list-unstyled">
                                    @foreach($operationsByDepartment['aquisition'] as $op)
                                        <li>
                                            <strong>{{ $op->account_item->name ?? 'Item' }}</strong>
                                            <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                            <span class="pull-right">{!! $formatMoney($op->total) !!}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h4>Oficina</h4>
                            @if($operationsByDepartment['garage']->isEmpty())
                                <p class="text-muted">Sem custos registados.</p>
                            @else
                                <ul class="list-unstyled">
                                    @foreach($operationsByDepartment['garage'] as $op)
                                        <li>
                                            <strong>{{ $op->account_item->name ?? 'Item' }}</strong>
                                            <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                            <span class="pull-right">{!! $formatMoney($op->total) !!}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h4>Venda</h4>
                            @if($operationsByDepartment['sale']->isEmpty())
                                <p class="text-muted">Sem recebimentos registados.</p>
                            @else
                                <ul class="list-unstyled">
                                    @foreach($operationsByDepartment['sale'] as $op)
                                        <li>
                                            <strong>{{ $op->account_item->name ?? 'Item' }}</strong>
                                            <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                            <span class="pull-right">{!! $formatMoney($op->total) !!}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="col-md-6 col-md-offset-3">
                            <h4>Venda</h4>
                            @if($operationsByDepartment['sale']->isEmpty())
                                <p class="text-muted">Sem recebimentos registados.</p>
                            @else
                                <ul class="list-unstyled">
                                    @foreach($operationsByDepartment['sale'] as $op)
                                        <li>
                                            <strong>{{ $op->account_item->name ?? 'Item' }}</strong>
                                            <small class="text-muted">({{ $op->vehicle->license ?? 'Sem matricula' }})</small>
                                            <span class="pull-right">{!! $formatMoney($op->total) !!}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

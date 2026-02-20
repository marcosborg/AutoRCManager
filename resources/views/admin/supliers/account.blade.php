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
                Conta corrente do fornecedor: {{ $suplier->name }}

                <div class="pull-right">
                    <a href="{{ route('admin.supliers.edit', $suplier->id) }}" class="btn btn-xs btn-info">Editar fornecedor</a>
                    <a href="{{ route('admin.supliers.index') }}" class="btn btn-xs btn-default">Voltar</a>
                </div>
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="well well-sm">
                            <div><strong>Viaturas associadas</strong></div>
                            <div class="lead">{{ $summary['vehicles'] ?? 0 }}</div>
                            <small class="text-muted">Inclui apenas viaturas com este fornecedor selecionado.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well well-sm">
                            <div><strong>Valor de aquisiÃ§Ã£o</strong></div>
                            <div class="lead">{!! $formatMoney($summary['purchase_total'] ?? 0) !!}</div>
                            <small class="text-muted">SomatÃ³rio de {{ trans('cruds.vehicle.fields.purchase_price') }}.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="well well-sm">
                            <div><strong>Pago / Em falta</strong></div>
                            <div class="lead">{!! $formatMoney($summary['paid_total'] ?? 0) !!}</div>
                            <small>Em falta: {!! $formatMoney($summary['balance_total'] ?? 0) !!}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                Viaturas do fornecedor
            </div>
            <div class="panel-body">
                @if($vehicleBreakdown->isEmpty())
                    <p class="text-muted">Ainda nÃ£o existem viaturas associadas a este fornecedor.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Viatura</th>
                                    <th>Estado</th>
                                    <th class="text-right">Valor aquisiÃ§Ã£o</th>
                                    <th class="text-right">Pago</th>
                                    <th class="text-right">Em falta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicleBreakdown as $row)
                                    @php $vehicle = $row['vehicle']; @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.vehicles.show', $vehicle->id) }}">
                                                {{ $vehicle->license ?? $vehicle->foreign_license ?? 'Sem matrÃ­cula' }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $vehicle->brand->name ?? '' }} {{ $vehicle->model }}</small>
                                        </td>
                                        <td>{{ $vehicle->general_state->name ?? 'â€”' }}</td>
                                        <td class="text-right">{!! $formatMoney($row['purchase_price']) !!}</td>
                                        <td class="text-right">{!! $formatMoney($row['paid']) !!}</td>
                                        <td class="text-right">
                                            <span class="{{ $row['balance'] <= 0 ? 'text-success' : 'text-danger' }}">
                                                {!! $formatMoney($row['balance']) !!}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection





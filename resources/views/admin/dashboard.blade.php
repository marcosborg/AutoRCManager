@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    KPIs e Visao Geral
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.dashboard') }}" style="margin-bottom: 20px;">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="from">Data inicio</label>
                                <input class="form-control date" type="text" name="from" id="from" value="{{ $fromDate->format(config('panel.date_format')) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="to">Data fim</label>
                                <input class="form-control date" type="text" name="to" id="to" value="{{ $toDate->format(config('panel.date_format')) }}">
                            </div>
                            <div class="col-md-3" style="margin-top: 25px;">
                                <button class="btn btn-primary" type="submit">{{ trans('global.search') }}</button>
                            </div>
                        </div>
                    </form>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Total custos</strong></div>
                                <div class="lead">€{{ number_format($totals['cost'], 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Total receitas</strong></div>
                                <div class="lead">€{{ number_format($totals['revenue'], 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Resultado</strong></div>
                                <div class="lead">€{{ number_format($totals['result'], 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Viaturas ativas</strong></div>
                                <div class="lead">{{ $totals['vehicles_active'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Viaturas movimentadas</strong></div>
                                <div class="lead">{{ $totals['vehicles_moved'] }}</div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Unidade</th>
                                <th class="text-right">Custos</th>
                                <th class="text-right">Receitas</th>
                                <th class="text-right">Resultado</th>
                                <th class="text-right">Viaturas</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($units as $unit)
                                <tr>
                                    <td>{{ $unit['unit_name'] }}</td>
                                    <td class="text-right">€{{ number_format($unit['total_cost'], 2, ',', '.') }}</td>
                                    <td class="text-right">€{{ number_format($unit['total_revenue'], 2, ',', '.') }}</td>
                                    <td class="text-right">€{{ number_format($unit['result'], 2, ',', '.') }}</td>
                                    <td class="text-right">{{ $unit['vehicle_count'] }}</td>
                                    <td class="text-muted">-</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">Sem dados para o periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <p class="text-muted">
                        Valores informativos. Nao incluem impostos, amortizacoes ou ajustes contabilisticos.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Relatorio por unidade operacional
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.reports.operational-units') }}" style="margin-bottom: 20px;">
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
                                @can('account_access')
                                    <a class="btn btn-success" href="{{ route('admin.reports.operational-units.export', ['from' => $fromDate->format(config('panel.date_format')), 'to' => $toDate->format(config('panel.date_format'))]) }}">
                                        Exportar CSV
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </form>

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Unidade</th>
                                <th class="text-right">Custos</th>
                                <th class="text-right">Receitas</th>
                                <th class="text-right">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($units as $unit)
                                <tr>
                                    <td>{{ $unit['unit_name'] }}</td>
                                    <td class="text-right">€{{ number_format($unit['total_cost'], 2, ',', '.') }}</td>
                                    <td class="text-right">€{{ number_format($unit['total_revenue'], 2, ',', '.') }}</td>
                                    <td class="text-right">€{{ number_format($unit['result'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Sem dados para o periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="text-right">€{{ number_format($totalCost, 2, ',', '.') }}</th>
                                <th class="text-right">€{{ number_format($totalRevenue, 2, ',', '.') }}</th>
                                <th class="text-right">€{{ number_format($totalResult, 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
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

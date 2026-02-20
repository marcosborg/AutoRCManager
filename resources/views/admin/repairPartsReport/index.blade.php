@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Relatorio de pecas de oficina
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.repair-parts-report.index') }}" class="row" style="margin-bottom:15px;">
                        <div class="col-md-2">
                            <label for="supplier">Fornecedor</label>
                            <input class="form-control" type="text" id="supplier" name="supplier" value="{{ request('supplier') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="invoice_number">N. fatura</label>
                            <input class="form-control" type="text" id="invoice_number" name="invoice_number" value="{{ request('invoice_number') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="name">Nome peca</label>
                            <input class="form-control" type="text" id="name" name="name" value="{{ request('name') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="vehicle">Matricula</label>
                            <input class="form-control" type="text" id="vehicle" name="vehicle" value="{{ request('vehicle') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_from">Data de</label>
                            <input class="form-control" type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to">Data ate</label>
                            <input class="form-control" type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-12" style="margin-top:10px;">
                            <button class="btn btn-primary" type="submit">Filtrar</button>
                            <a class="btn btn-default" href="{{ route('admin.repair-parts-report.index') }}">Limpar</a>
                        </div>
                    </form>

                    <div class="row" style="margin-bottom:15px;">
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Total linhas</strong></div>
                                <div class="lead">{{ $totalLines }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <div><strong>Total custo</strong></div>
                                <div class="lead">€{{ number_format($totalAmount, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    @if($supplierTotals->isNotEmpty())
                        <div class="table-responsive" style="margin-bottom:15px;">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th>Totais por fornecedor (top 20)</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($supplierTotals as $row)
                                        <tr>
                                            <td>{{ $row->supplier_label }}</td>
                                            <td class="text-right">€{{ number_format((float) $row->total_amount, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Fornecedor</th>
                                    <th>N. fatura</th>
                                    <th>Nome</th>
                                    <th>Valor</th>
                                    <th>Matricula</th>
                                    <th>Intervencao</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($parts as $part)
                                    <tr>
                                        <td>{{ optional($part->part_date)->format('Y-m-d') ?: '-' }}</td>
                                        <td>{{ $part->supplier ?: '-' }}</td>
                                        <td>{{ $part->invoice_number ?: '-' }}</td>
                                        <td>{{ $part->part_name ?: '-' }}</td>
                                        <td>€{{ number_format((float) $part->amount, 2, ',', '.') }}</td>
                                        <td>{{ $part->repair?->vehicle?->license ?: ($part->repair?->vehicle?->foreign_license ?: '-') }}</td>
                                        <td>
                                            @if($part->repair)
                                                <a href="{{ route('admin.repairs.edit', $part->repair->id) }}" class="btn btn-xs btn-primary">Abrir</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Sem registos para os filtros selecionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $parts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


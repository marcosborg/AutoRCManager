@extends('layouts.admin')
@section('content')
<div class="panel panel-default">
    <div class="panel-heading"><h3>Vendas — faturação por confirmar</h3></div>
    <div class="panel-body">
        <p>Consulta dos dados guardados na ficha. A marcação «Faturado» e a existência de uma fatura identificada são apresentadas separadamente. Não confirma a emissão fiscal nem o pagamento.</p>
        <p>
            <a class="btn {{ ($filters['scope'] ?? 'sold') === 'sold' ? 'btn-primary' : 'btn-default' }}" href="{{ route('admin.sales.index', ['review' => 'invoice', 'scope' => 'sold']) }}">Vendidos</a>
            <a class="btn {{ ($filters['scope'] ?? 'sold') === 'suspended' ? 'btn-primary' : 'btn-default' }}" href="{{ route('admin.sales.index', ['review' => 'invoice', 'scope' => 'suspended', 'reason' => 'all']) }}">Vendas suspensas — consulta separada</a>
        </p>
        @if(($filters['scope'] ?? 'sold') === 'suspended')
            <p><strong>Vendas suspensas:</strong> não são contadas como vendas concluídas nesta consulta.</p>
        @else
            <p>Inclui os estados Vendido Stand, Vendido Salvados, Vendido Oficina e Vendido A Comércio. Suspensões ativas ficam na consulta separada.</p>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="GET" action="{{ route('admin.sales.index') }}">
            <input type="hidden" name="review" value="invoice">
            <input type="hidden" name="scope" value="{{ $filters['scope'] ?? 'sold' }}">
            <div class="row">
                <div class="col-md-3 form-group"><label for="review-search">Matrícula ou cliente</label><input id="review-search" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100"></div>
                @if(($filters['scope'] ?? 'sold') === 'sold')
                    <div class="col-md-3 form-group"><label for="review-category">Categoria</label><select id="review-category" class="form-control" name="category"><option value="">Todas</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['category'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select></div>
                @endif
                <div class="col-md-3 form-group"><label for="review-from">Data da venda — desde</label><input id="review-from" type="date" class="form-control" name="from" value="{{ $filters['from'] ?? '' }}"></div>
                <div class="col-md-3 form-group"><label for="review-to">Data da venda — até</label><input id="review-to" type="date" class="form-control" name="to" value="{{ $filters['to'] ?? '' }}"></div>
            </div>
            <div class="form-group"><label for="review-reason">Mostrar</label><select id="review-reason" class="form-control" name="reason">
                @foreach(['pending' => 'Com faturação por confirmar', 'unmarked' => 'Não marcadas como faturadas', 'missing_file' => 'Marcadas como faturadas, sem fatura identificada', 'file_unmarked' => 'Com fatura identificada, sem marcação de faturado', 'all' => 'Todas no âmbito selecionado'] as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['reason'] ?? 'pending') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select></div>
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-default" href="{{ route('admin.sales.index', ['review' => 'invoice', 'scope' => $filters['scope'] ?? 'sold', 'reason' => ($filters['scope'] ?? 'sold') === 'suspended' ? 'all' : 'pending']) }}">Limpar filtros</a>
        </form>
        <p style="margin-top:15px">{{ $vehicles->total() }} viatura(s) encontradas. Sem data de venda: aparecem quando não é aplicado um período.</p>
        <div class="table-responsive"><table class="table table-striped table-bordered">
            <thead><tr><th>Matrícula / viatura</th><th>Cliente</th><th>Data da venda</th><th>Estado atual</th><th>Faturado na ficha</th><th>Fatura identificada</th><th>Conferência</th><th>Abrir</th></tr></thead>
            <tbody>
            @forelse($vehicles as $vehicle)
                <tr data-vehicle-id="{{ $vehicle->id }}">
                    <td>{{ $vehicle->license ?: $vehicle->foreign_license ?: 'Sem matrícula' }}<br><small>{{ $vehicle->model }}</small></td>
                    <td>{{ $vehicle->client->name ?? 'Por identificar' }}</td>
                    <td>{{ $vehicle->sale_date ?: 'Por confirmar' }}</td>
                    <td>{{ $vehicle->general_state->name ?? 'Por confirmar' }}</td>
                    <td>{{ $vehicle->is_invoiced ? 'Sim' : 'Não' }}</td>
                    <td>{{ $vehicle->sale_invoice_count ? 'Sim — '.$vehicle->sale_invoice_count.' anexo(s)' : 'Não identificada' }}</td>
                    <td>
                        @if(!$vehicle->is_invoiced)
                            Marcação de faturado por confirmar.
                        @endif
                        @if(!$vehicle->sale_invoice_count)
                            Fatura de venda por identificar ou anexar.
                        @endif
                        @if($vehicle->is_invoiced && $vehicle->sale_invoice_count)
                            Marcação e anexo presentes.
                        @endif
                    </td>
                    <td>
                        @can('vehicle_edit')
                            <a href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-documents">Abrir ficha</a>
                        @elsecan('vehicle_show')
                            <a href="{{ route('admin.vehicles.show', $vehicle) }}">Consultar ficha</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Sem viaturas para os filtros selecionados.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $vehicles->links() }}
        <p class="text-muted">Só contam anexos identificados como «Fatura de venda» e não anulados. Um anexo antigo por identificar não é tratado como prova de faturação. Esta página não altera estados, valores ou registos.</p>
    </div>
</div>
@endsection

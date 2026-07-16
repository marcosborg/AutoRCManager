@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Desempenho de leads por vendedor</div>
        <div class="panel-body">
            <div class="alert alert-info">Medição disponível desde {{ $measurementStart->format('d/m/Y H:i') }}. Atribuições anteriores não entram nos cálculos.</div>
            <form method="GET" action="{{ route('admin.leads.performance') }}" class="form-inline" style="margin-bottom:15px">
                <label>De <input class="form-control" type="date" name="date_start" value="{{ $filterDateStart->format('Y-m-d') }}"></label>
                <label>Até <input class="form-control" type="date" name="date_end" value="{{ $dateEnd->format('Y-m-d') }}"></label>
                <label>Vendedor <select class="form-control" name="seller_id"><option value="">Todos</option>@foreach($salespeople as $id => $name)<option value="{{ $id }}" {{ (int)$sellerId === (int)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></label>
                <label>Proveniência <select class="form-control" name="source"><option value="">Todas</option><option value="form" {{ $source === 'form' ? 'selected' : '' }}>Formulário</option><option value="whatsapp" {{ $source === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option></select></label>
                <label>Canal <select class="form-control" name="channel"><option value="">Todos</option><option value="call" {{ $channel === 'call' ? 'selected' : '' }}>Telefone</option><option value="whatsapp" {{ $channel === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option></select></label>
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a class="btn btn-danger" href="{{ route('admin.leads.performance.pdf', request()->query()) }}"><i class="fa fa-file-pdf-o"></i> Exportar PDF</a>
                <a class="btn btn-default" href="{{ route('admin.leads.performance') }}">Limpar</a>
            </form>
            <div class="table-responsive"><table class="table table-bordered table-striped">
                <thead><tr><th>Vendedor</th><th>Atribuídas</th><th>Abertas</th><th>Taxa abertura</th><th>Não abertas/expiradas</th><th>Aproveitadas</th><th>Taxa aproveitamento</th><th>Telefone</th><th>WhatsApp</th><th>Tempo até abrir</th><th>Tempo até contactar</th></tr></thead>
                <tbody>@forelse($ranking as $row)<tr>
                    <td>{{ $row['user_name'] }}</td>
                    @foreach(['assigned','opened'] as $key)<td><a href="{{ route('admin.leads.performance', array_merge(request()->query(), ['seller_id'=>$row['user_id'],'metric'=>$key])) }}">{{ $row[$key] }}</a></td>@endforeach
                    <td>{{ number_format($row['open_rate'], 1, ',', '.') }}%</td>
                    <td><a href="{{ route('admin.leads.performance', array_merge(request()->query(), ['seller_id'=>$row['user_id'],'metric'=>'unopened'])) }}">{{ $row['unopened'] }}</a></td>
                    <td><a href="{{ route('admin.leads.performance', array_merge(request()->query(), ['seller_id'=>$row['user_id'],'metric'=>'contacted'])) }}">{{ $row['contacted'] }}</a></td>
                    <td><strong>{{ number_format($row['contact_rate'], 1, ',', '.') }}%</strong></td>
                    <td><a href="{{ route('admin.leads.performance', array_merge(request()->query(), ['seller_id'=>$row['user_id'],'metric'=>'call'])) }}">{{ $row['calls'] }}</a></td>
                    <td><a href="{{ route('admin.leads.performance', array_merge(request()->query(), ['seller_id'=>$row['user_id'],'metric'=>'whatsapp'])) }}">{{ $row['whatsapps'] }}</a></td>
                    <td>{{ $row['avg_open_minutes'] !== null ? $row['avg_open_minutes'].' min' : '-' }}</td><td>{{ $row['avg_contact_minutes'] !== null ? $row['avg_contact_minutes'].' min' : '-' }}</td>
                </tr>@empty<tr><td colspan="11" class="text-muted">Sem oportunidades instrumentadas neste período.</td></tr>@endforelse</tbody>
            </table></div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">Histórico anterior à medição de contactos</div>
        <div class="panel-body">
            <p class="text-muted">É possível recuperar atribuições, aberturas e expirações. Os cliques em Telefone/WhatsApp não eram registados e, por isso, não existe taxa de aproveitamento histórica.</p>
            @if($channel)
                <div class="alert alert-warning">O histórico não pode ser filtrado por canal, porque os canais de contacto ainda não eram medidos.</div>
            @else
                <div class="table-responsive"><table class="table table-bordered table-striped">
                    <thead><tr><th>Vendedor</th><th>Atribuídas</th><th>Abertas</th><th>Taxa de abertura</th><th>Não abertas/expiradas</th><th>Aproveitamento</th></tr></thead>
                    <tbody>@forelse($legacyRanking as $row)<tr><td>{{ $row['user_name'] }}</td><td>{{ $row['assigned'] }}</td><td>{{ $row['opened'] }}</td><td>{{ number_format($row['open_rate'], 1, ',', '.') }}%</td><td>{{ $row['expired'] }}</td><td class="text-muted">Não mensurável</td></tr>@empty<tr><td colspan="6" class="text-muted">Sem dados históricos para estes filtros.</td></tr>@endforelse</tbody>
                </table></div>
            @endif
        </div>
    </div>
    @if($sellerId && $metric)
    <div class="panel panel-default"><div class="panel-heading">Detalhe: {{ $salespeople[$sellerId] ?? 'Vendedor' }} — {{ $metric }}</div><div class="panel-body table-responsive"><table class="table table-bordered table-condensed">
        <thead><tr><th>Lead</th><th>Proveniência</th><th>Atribuída</th><th>Aberta</th><th>Primeiro contacto</th><th>Canais</th><th>Cliques</th><th>Expirada</th><th>Estado</th></tr></thead>
        <tbody>@forelse($detail as $row)<tr><td><a href="{{ route('admin.leads.show', $row['lead_id']) }}">{{ $row['lead_name'] }}</a></td><td>{{ $row['source'] }}</td><td>{{ $row['assigned_at']->format('d/m/Y H:i') }}</td><td>{{ $row['opened_at']?->format('d/m/Y H:i') ?? '-' }}</td><td>{{ $row['first_contact_at']?->format('d/m/Y H:i') ?? '-' }}</td><td>{{ $row['channels'] ?: '-' }}</td><td>{{ $row['clicks'] }}</td><td>{{ $row['expired'] ? 'Sim' : 'Não' }}</td><td>{{ $row['status'] }}</td></tr>@empty<tr><td colspan="9" class="text-muted">Sem resultados.</td></tr>@endforelse</tbody>
    </table></div></div>
    @endif
    <a class="btn btn-default" href="{{ route('admin.leads.index') }}">Voltar às leads</a>
</div>
@endsection

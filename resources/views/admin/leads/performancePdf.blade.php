<!doctype html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 34px 36px 42px; }
    body { color:#253247; font-family:"DejaVu Sans",sans-serif; font-size:9px; line-height:1.35; }
    .header { border-bottom:3px solid #605ca8; margin-bottom:16px; padding-bottom:10px; }
    .brand { color:#605ca8; font-size:12px; font-weight:bold; letter-spacing:.5px; text-transform:uppercase; }
    h1 { color:#1f2937; font-size:22px; margin:4px 0 2px; }
    h2 { color:#374151; font-size:14px; margin:18px 0 7px; }
    .subtitle,.muted { color:#6b7280; }
    .filters { background:#f3f1fb; border:1px solid #ddd9f3; border-radius:4px; margin:10px 0 14px; padding:9px 11px; }
    .filter { display:inline-block; margin-right:22px; }
    .filter strong { color:#4b468c; }
    .notice { background:#eef8fc; border-left:4px solid #21a8d8; margin-bottom:12px; padding:8px 10px; }
    table { border-collapse:collapse; page-break-inside:auto; width:100%; }
    tr { page-break-inside:avoid; page-break-after:auto; }
    th { background:#605ca8; color:#fff; font-size:7.5px; padding:6px 4px; text-align:left; }
    td { border-bottom:1px solid #e5e7eb; padding:6px 4px; vertical-align:top; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    .number { text-align:right; white-space:nowrap; }
    .rate { color:#4b468c; font-weight:bold; }
    .empty { color:#6b7280; padding:12px; text-align:center; }
    .historical th { background:#52606d; }
    .footer-note { color:#6b7280; font-size:8px; margin-top:8px; }
    .detail { font-size:7.5px; }
</style>
</head>
<body>
<div class="header">
    <div class="brand">Auto RC Manager</div>
    <h1>Desempenho de leads por vendedor</h1>
    <div class="subtitle">Relatório gerado em {{ $generatedAt->format('d/m/Y H:i') }}</div>
</div>

<div class="filters">
    <span class="filter"><strong>Período:</strong> {{ $filterDateStart->format('d/m/Y') }} a {{ $dateEnd->format('d/m/Y') }}</span>
    <span class="filter"><strong>Vendedor:</strong> {{ $sellerId ? ($salespeople[$sellerId] ?? 'Selecionado') : 'Todos' }}</span>
    <span class="filter"><strong>Proveniência:</strong> {{ $source === 'whatsapp' ? 'WhatsApp' : ($source === 'form' ? 'Formulário' : 'Todas') }}</span>
    <span class="filter"><strong>Canal:</strong> {{ $channel === 'call' ? 'Telefone' : ($channel === 'whatsapp' ? 'WhatsApp' : 'Todos') }}</span>
</div>

<div class="notice">Medição completa disponível desde {{ $measurementStart->format('d/m/Y H:i') }}. A taxa de aproveitamento usa apenas oportunidades instrumentadas.</div>

<h2>Ranking de aproveitamento</h2>
<table>
    <thead><tr><th>Vendedor</th><th class="number">Atribuídas</th><th class="number">Abertas</th><th class="number">Taxa abertura</th><th class="number">Não abertas</th><th class="number">Aproveitadas</th><th class="number">Taxa aproveit.</th><th class="number">Telefone</th><th class="number">WhatsApp</th><th class="number">Tempo abrir</th><th class="number">Tempo contactar</th></tr></thead>
    <tbody>@forelse($ranking as $row)<tr><td>{{ $row['user_name'] }}</td><td class="number">{{ $row['assigned'] }}</td><td class="number">{{ $row['opened'] }}</td><td class="number">{{ number_format($row['open_rate'],1,',','.') }}%</td><td class="number">{{ $row['unopened'] }}</td><td class="number">{{ $row['contacted'] }}</td><td class="number rate">{{ number_format($row['contact_rate'],1,',','.') }}%</td><td class="number">{{ $row['calls'] }}</td><td class="number">{{ $row['whatsapps'] }}</td><td class="number">{{ $row['avg_open_minutes'] !== null ? $row['avg_open_minutes'].' min' : '-' }}</td><td class="number">{{ $row['avg_contact_minutes'] !== null ? $row['avg_contact_minutes'].' min' : '-' }}</td></tr>@empty<tr><td colspan="11" class="empty">Sem oportunidades instrumentadas neste período.</td></tr>@endforelse</tbody>
</table>

<h2>Histórico anterior à medição de contactos</h2>
<table class="historical">
    <thead><tr><th>Vendedor</th><th class="number">Atribuídas</th><th class="number">Abertas</th><th class="number">Taxa de abertura</th><th class="number">Não abertas/expiradas</th><th>Aproveitamento</th></tr></thead>
    <tbody>@forelse($legacyRanking as $row)<tr><td>{{ $row['user_name'] }}</td><td class="number">{{ $row['assigned'] }}</td><td class="number">{{ $row['opened'] }}</td><td class="number">{{ number_format($row['open_rate'],1,',','.') }}%</td><td class="number">{{ $row['expired'] }}</td><td class="muted">Não mensurável</td></tr>@empty<tr><td colspan="6" class="empty">{{ $channel ? 'Histórico indisponível com filtro de canal.' : 'Sem dados históricos para estes filtros.' }}</td></tr>@endforelse</tbody>
</table>
<div class="footer-note">Os contactos históricos não podem ser reconstruídos porque os cliques em Telefone e WhatsApp ainda não eram registados.</div>

@if($sellerId && $metric)
<h2>Detalhe do filtro: {{ $metric }}</h2>
<table class="detail"><thead><tr><th>Lead</th><th>Proveniência</th><th>Atribuída</th><th>Aberta</th><th>Primeiro contacto</th><th>Canais</th><th>Cliques</th><th>Expirada</th><th>Estado</th></tr></thead><tbody>@forelse($detail as $row)<tr><td>#{{ $row['lead_id'] }} {{ $row['lead_name'] }}</td><td>{{ $row['source'] }}</td><td>{{ $row['assigned_at']->format('d/m/Y H:i') }}</td><td>{{ $row['opened_at']?->format('d/m/Y H:i') ?? '-' }}</td><td>{{ $row['first_contact_at']?->format('d/m/Y H:i') ?? '-' }}</td><td>{{ $row['channels'] ?: '-' }}</td><td>{{ $row['clicks'] }}</td><td>{{ $row['expired'] ? 'Sim' : 'Não' }}</td><td>{{ $row['status'] }}</td></tr>@empty<tr><td colspan="9" class="empty">Sem resultados.</td></tr>@endforelse</tbody></table>
@endif
</body></html>

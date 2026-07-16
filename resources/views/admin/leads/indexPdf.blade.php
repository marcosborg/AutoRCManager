<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de leads</title>
    <style>
        @page { margin: 32px 34px 36px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #263244; font-family: 'DejaVu Sans', sans-serif; font-size: 9px; }
        .brand { color: #625bb1; font-size: 12px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin: 8px 0 2px; color: #202a3a; font-size: 22px; }
        .generated { color: #6c7686; font-size: 8.5px; }
        .rule { height: 3px; margin: 12px 0 13px; background: #625bb1; }
        .summary { width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 7px 0; }
        .summary td { width: 16.66%; padding: 9px 10px; border: 1px solid #dedcf2; border-radius: 4px; background: #f5f4fc; }
        .summary .label { color: #625bb1; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .summary .value { margin-top: 3px; color: #263244; font-size: 15px; font-weight: bold; }
        .filters { margin-bottom: 12px; padding: 8px 10px; border-left: 4px solid #28b2dc; background: #eef8fc; color: #34465b; }
        .filters strong { color: #17647b; }
        table.leads { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.leads thead { display: table-header-group; }
        table.leads tr { page-break-inside: avoid; }
        table.leads th { padding: 7px 6px; background: #625bb1; color: white; font-size: 7.5px; text-align: left; text-transform: uppercase; }
        table.leads td { padding: 7px 6px; border-bottom: 1px solid #dfe3e8; vertical-align: top; overflow-wrap: break-word; }
        table.leads tbody tr:nth-child(even) { background: #f7f8fa; }
        .id { width: 5%; } .date { width: 10%; } .origin { width: 8%; } .contact { width: 22%; }
        .interest { width: 22%; } .seller { width: 16%; } .state { width: 10%; }
        .primary { color: #202a3a; font-weight: bold; }
        .secondary { margin-top: 2px; color: #687385; font-size: 8px; line-height: 1.35; }
        .badge { display: inline-block; padding: 3px 6px; border-radius: 9px; background: #e9e7f8; color: #4e478e; font-size: 7px; font-weight: bold; }
        .empty { padding: 30px !important; color: #7a8493; text-align: center; }
        .note { margin-top: 10px; color: #778191; font-size: 7.5px; }
    </style>
</head>
<body>
    <div class="brand">Auto RC Manager</div>
    <h1>Relatório de leads</h1>
    <div class="generated">Gerado em {{ $generatedAt->format('d/m/Y H:i') }}</div>
    <div class="rule"></div>

    <table class="summary"><tr>
        <td><div class="label">Exportadas</div><div class="value">{{ $leads->count() }}@if($totalMatches > $leads->count()) / {{ $totalMatches }}@endif</div></td>
        @foreach(\App\Models\Lead::STATUS_SELECT as $status => $label)
            <td><div class="label">{{ $label }}</div><div class="value">{{ $statusSummary->get($status, 0) }}</div></td>
        @endforeach
    </tr></table>

    <div class="filters">
        <strong>Filtros aplicados:</strong>
        @if($filters)
            {{ collect($filters)->map(fn ($value, $key) => [
                'search' => 'Pesquisa', 'id' => 'ID', 'date' => 'Data', 'source' => 'Proveniência',
                'name' => 'Nome', 'phone' => 'Telefone', 'email' => 'Email', 'budget' => 'Orçamento',
                'vehicle' => 'Veículo', 'seller' => 'Vendedor', 'status' => 'Estado'
            ][$key].': '.($key === 'status' ? (\App\Models\Lead::STATUS_SELECT[$value] ?? $value) : ($key === 'source' ? ($value === 'form' ? 'Formulário' : 'WhatsApp') : $value)))->implode(' · ') }}
        @else
            Nenhum - são apresentados todos os registos visíveis ao utilizador.
        @endif
    </div>
    @if($totalMatches > $leads->count())
        <div class="filters"><strong>Relatório limitado:</strong> são apresentadas as {{ $exportLimit }} leads mais recentes de {{ $totalMatches }} resultados. Aplique filtros adicionais na lista para obter o conjunto completo pretendido.</div>
    @endif

    <table class="leads">
        <thead><tr>
            <th class="id">ID</th><th class="date">Data</th><th class="origin">Origem</th>
            <th class="contact">Lead e contacto</th><th class="interest">Interesse e orçamento</th>
            <th class="seller">Vendedor</th><th class="state">Estado</th>
        </tr></thead>
        <tbody>
        @forelse($leads as $lead)
            <tr>
                <td class="primary">#{{ $lead->id }}</td>
                <td>{{ optional($lead->created_at)->format('d/m/Y') }}<div class="secondary">{{ optional($lead->created_at)->format('H:i') }}</div></td>
                <td><span class="badge">{{ data_get($lead->raw_data, 'source') === 'ai_whatsapp' || $lead->form_id === 'ai_whatsapp' || str_starts_with((string) $lead->leadgen_id, 'ai_whatsapp:') ? 'WhatsApp' : 'Formulário' }}</span></td>
                <td><div class="primary">{{ $lead->full_name ?: trim(($lead->first_name ?? '').' '.($lead->last_name ?? '')) ?: '-' }}</div><div class="secondary">{{ $lead->phone ?: '-' }}<br>{{ $lead->email ?: '-' }}</div></td>
                <td><div class="primary">{{ $lead->vehicle_interest ?: '-' }}</div><div class="secondary">Orçamento: {{ $lead->budget ?: '-' }}</div></td>
                <td>{{ $lead->assigned_user?->name ?: 'Sem atribuição' }}</td>
                <td><span class="badge">{{ \App\Models\Lead::STATUS_SELECT[$lead->status] ?? $lead->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Não existem leads correspondentes aos filtros selecionados.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="note">Este relatório respeita o âmbito de acesso do utilizador e os filtros ativos na lista de leads.</div>
</body>
</html>

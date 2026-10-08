<details>
    <summary>Histórico das peças e serviços usados</summary>
    <p class="help-block">Últimas 50 operações registadas a partir desta melhoria.</p>
    @forelse($repairPartsHistory as $entry)
        @php($change = $entry->properties)
        <p><strong>{{ $entry->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }} — {{ $change['actor_name'] ?? 'Utilizador não identificado' }}</strong>
            · {{ $change['operation'] }} · Peça #{{ $entry->subject_id }}</p>
        @if($change['reason'] ?? null)
            <p><strong>Motivo:</strong> {{ $change['reason'] }}</p>
        @endif
        <table class="table table-condensed table-bordered">
            <thead><tr><th>Campo</th><th>Antes</th><th>Depois</th></tr></thead>
            <tbody>
            @foreach(\App\Services\RepairPartService::FIELDS as $field => $label)
                @if(($change['before'][$field] ?? null) !== ($change['after'][$field] ?? null))
                    <tr><td>{{ $label }}</td><td>{{ $change['before'][$field] ?? '—' }}</td><td>{{ $change['after'][$field] ?? '—' }}</td></tr>
                @endif
            @endforeach
            </tbody>
        </table>
    @empty
        <p>Ainda não há alterações registadas neste histórico.</p>
    @endforelse
</details>

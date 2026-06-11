<div class="panel panel-default">
    <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center;">
        <strong>Planificação de intervenções</strong>
        @can('workshop_planning_create')<a class="btn btn-xs btn-success" href="{{ route('admin.workshop-interventions.create', ['repair_id' => $repair->id]) }}">Planificar trabalho</a>@endcan
    </div>
    <div class="panel-body">
        <div class="table-responsive"><table class="table table-bordered table-condensed">
            <thead><tr><th>Período</th><th>Tipo</th><th>Trabalho</th><th>Mecânicos</th><th>Estado</th><th>Tempo</th><th></th></tr></thead>
            <tbody>
            @forelse($repair->workshopInterventions->sortBy('planned_start_date') as $item)
                @php
                    $openForMe = $item->workLogs->first(fn($log) => (int) $log->user_id === (int) auth()->id() && !$log->finished_at);
                    $assignedToMe = $item->mechanics->contains('id', auth()->id());
                    $minutes = $item->workLogs->sum(fn($log) => $log->duration_minutes ?? ($log->finished_at ? \Carbon\Carbon::parse($log->started_at)->diffInMinutes($log->finished_at) : \Carbon\Carbon::parse($log->started_at)->diffInMinutes(now())));
                @endphp
                <tr>
                    <td>{{ $item->planned_start_date->format('d/m/Y') }}{{ $item->planned_start_date->ne($item->planned_end_date) ? ' a '.$item->planned_end_date->format('d/m/Y') : '' }}</td>
                    <td>{{ $item->type?->name }}</td><td>{{ $item->title }}</td><td>{{ $item->mechanics->pluck('name')->join(', ') }}</td>
                    <td>{{ App\Models\WorkshopIntervention::STATUS_SELECT[$item->status] ?? $item->status }}</td><td>{{ $minutes }} min</td>
                    <td style="white-space:nowrap">
                        @can('workshop_planning_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.workshop-interventions.edit', $item) }}">Editar</a>@endcan
                        @can('workshop_task_execute')
                            @if($assignedToMe && !in_array($item->status, ['completed', 'cancelled'], true))
                                @if($openForMe)<form method="POST" action="{{ route('admin.workshop-interventions.finish', $item) }}" style="display:inline">@csrf<button class="btn btn-xs btn-warning">Terminar tempo</button></form>
                                @else<form method="POST" action="{{ route('admin.workshop-interventions.start', $item) }}" style="display:inline">@csrf<button class="btn btn-xs btn-primary">Iniciar</button></form>@endif
                                <form method="POST" action="{{ route('admin.workshop-interventions.complete', $item) }}" style="display:inline" onsubmit="return confirm('Concluir este trabalho para toda a equipa?')">@csrf<button class="btn btn-xs btn-success">Concluir</button></form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty<tr><td colspan="7" class="text-center text-muted">Sem trabalhos planeados.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</div>

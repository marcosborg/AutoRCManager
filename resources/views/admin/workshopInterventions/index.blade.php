@extends('layouts.admin')
@section('content')
<div class="content">
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px;">
        @can('workshop_planning_create')<a class="btn btn-success" href="{{ route('admin.workshop-interventions.create') }}">Planificar trabalho</a>@endcan
        @can('workshop_intervention_type_access')<a class="btn btn-default" href="{{ route('admin.workshop-intervention-types.index') }}">Tipos de intervenção</a>@endcan
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">Planificação de intervenções</div>
        <div class="panel-body">
            <form method="GET" class="row" style="margin-bottom:15px;">
                <div class="col-md-2 form-group"><label>De</label><input class="form-control" type="date" name="start_date" value="{{ $start }}"></div>
                <div class="col-md-2 form-group"><label>Até</label><input class="form-control" type="date" name="end_date" value="{{ $end }}"></div>
                <div class="col-md-2 form-group"><label>Mecânico</label><select class="form-control select2" name="mechanic_id"><option value="">Todos</option>@foreach($mechanics as $id => $name)<option value="{{ $id }}" {{ (string) request('mechanic_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label>Tipo</label><select class="form-control" name="type_id"><option value="">Todos</option>@foreach($types as $id => $name)<option value="{{ $id }}" {{ (string) request('type_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label>Estado</label><select class="form-control" name="status"><option value="">Todos</option>@foreach(App\Models\WorkshopIntervention::STATUS_SELECT as $key => $label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-default btn-block" type="submit">Filtrar</button></div>
            </form>
            <div class="table-responsive"><table class="table table-bordered table-striped">
                <thead><tr><th>Período</th><th>Viatura</th><th>Trabalho</th><th>Tipo</th><th>Mecânicos</th><th>Estado</th><th>Tempo</th><th></th></tr></thead>
                <tbody>
                @forelse($interventions as $item)
                    @php($minutes = $item->workLogs->sum(fn($log) => $log->duration_minutes ?? ($log->finished_at ? \Carbon\Carbon::parse($log->started_at)->diffInMinutes($log->finished_at) : \Carbon\Carbon::parse($log->started_at)->diffInMinutes(now()))))
                    <tr>
                        <td>{{ $item->planned_start_date->format('d/m/Y') }}{{ $item->planned_start_date->ne($item->planned_end_date) ? ' a '.$item->planned_end_date->format('d/m/Y') : '' }}</td>
                        <td><a href="{{ route('admin.repairs.edit', $item->repair_id) }}">{{ $item->repair?->vehicle?->license ?: $item->repair?->vehicle?->foreign_license ?: '#'.$item->repair_id }}</a></td>
                        <td><strong>{{ $item->title }}</strong>@if($item->description)<br><small>{{ Str::limit($item->description, 90) }}</small>@endif</td>
                        <td>{{ $item->type?->name }}</td>
                        <td>{{ $item->mechanics->pluck('name')->join(', ') }}</td>
                        <td>{{ App\Models\WorkshopIntervention::STATUS_SELECT[$item->status] ?? $item->status }}</td>
                        <td>{{ $minutes }} min</td>
                        <td style="white-space:nowrap">
                            @can('workshop_planning_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.workshop-interventions.edit', $item) }}">Editar</a>@endcan
                            @can('workshop_planning_delete')<form method="POST" action="{{ route('admin.workshop-interventions.destroy', $item) }}" style="display:inline" onsubmit="return confirm('Eliminar este trabalho?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Eliminar</button></form>@endcan
                        </td>
                    </tr>
                @empty<tr><td colspan="8" class="text-center text-muted">Sem trabalhos para o período selecionado.</td></tr>@endforelse
                </tbody>
            </table></div>
            {{ $interventions->links() }}
        </div>
    </div>
</div>
@endsection

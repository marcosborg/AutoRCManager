@extends('layouts.admin')

@section('content')
<div class="content">
    @can('oficina_expertise_process_create')
        <p>
            <a class="btn btn-success" href="{{ route('admin.oficina-expertise-processes.create') }}">Nova Peritagem de Oficina</a>
            <a class="btn btn-default" href="{{ route('admin.oficina-expertise-processes.index', array_merge(request()->except('page'), ['view' => request('view') === 'kanban' ? null : 'kanban'])) }}">
                {{ request('view') === 'kanban' ? 'Ver tabela' : 'Ver Kanban' }}
            </a>
        </p>
    @endcan

    <div class="panel panel-default">
        <div class="panel-heading">Peritagens de Oficina</div>
        <div class="panel-body">
            <form method="GET" class="row" style="margin-bottom:15px;">
                <input type="hidden" name="view" value="{{ request('view') }}">
                <div class="col-md-2">
                    <select class="form-control" name="status">
                        <option value="">Estado</option>
                        @foreach(\App\Models\OficinaExpertiseProcess::STATUS_SELECT as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control select2" name="created_by_id">
                        <option value="">Funcionário</option>
                        @foreach($users as $id => $label)
                            <option value="{{ $id }}" {{ (string) request('created_by_id') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" name="vehicle_search" value="{{ request('vehicle_search') }}" placeholder="Matrícula/processo"></div>
                <div class="col-md-2"><input class="form-control" name="client_search" value="{{ request('client_search') }}" placeholder="Cliente"></div>
                <div class="col-md-2"><input class="form-control" name="insurance_company" value="{{ request('insurance_company') }}" placeholder="Seguradora"></div>
                <div class="col-md-1"><button class="btn btn-default" type="submit">Filtrar</button></div>
            </form>

            @if(request('view') === 'kanban')
                <div class="row">
                    @foreach(\App\Models\OficinaExpertiseProcess::STATUS_SELECT as $status => $label)
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading"><strong>{{ $label }}</strong></div>
                                <div class="panel-body" style="min-height: 120px;">
                                    @forelse(($kanbanProcesses[$status] ?? collect()) as $process)
                                        <div class="well well-sm" style="{{ $process->is_alert ? 'border-left:4px solid #dd4b39;' : '' }}">
                                            <a href="{{ route('admin.oficina-expertise-processes.show', $process) }}"><strong>{{ $process->license_display }}</strong></a>
                                            <div>{{ $process->insurance_company ?: '-' }}</div>
                                            <div class="text-muted small">{{ $process->next_action }}</div>
                                            @if($process->is_alert)
                                                <div class="text-danger small">{{ $process->alertReason() }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-muted small">Sem processos.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Cliente</th>
                            <th>Seguradora</th>
                            <th>Processo/Sinistro</th>
                            <th>Entrada</th>
                            <th>Peritagem</th>
                            <th>Valor aprovado</th>
                            <th>Estado</th>
                            <th>Dias</th>
                            <th>Próxima ação</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($processes as $process)
                            <tr class="{{ $process->is_alert ? 'danger' : '' }}">
                                <td>{{ $process->license_display }}</td>
                                <td>{{ $process->vehicle->client->name ?? '-' }}</td>
                                <td>{{ $process->insurance_company ?: '-' }}</td>
                                <td>
                                    {{ $process->process_number ?: '-' }}
                                    @if($process->claim_number)
                                        <div class="text-muted small">Sinistro: {{ $process->claim_number }}</div>
                                    @endif
                                </td>
                                <td>{{ optional($process->entry_date)->format('Y-m-d') ?: '-' }}</td>
                                <td>{{ optional($process->scheduled_expertise_date)->format('Y-m-d') ?: '-' }}</td>
                                <td>{{ $process->approved_amount !== null ? number_format($process->approved_amount, 2, ',', '.') . ' €' : '-' }}</td>
                                <td>
                                    {{ $process->status_label }}
                                    @if($process->is_alert)
                                        <div class="text-danger small">{{ $process->alertReason() }}</div>
                                    @endif
                                </td>
                                <td>{{ $process->days_in_current_status }}</td>
                                <td>{{ $process->next_action }}</td>
                                <td>
                                    @can('oficina_expertise_process_show')
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.oficina-expertise-processes.show', $process) }}">Ver</a>
                                    @endcan
                                    @can('oficina_expertise_process_edit')
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.oficina-expertise-processes.edit', $process) }}">Editar</a>
                                    @endcan
                                    @can('oficina_expertise_process_delete')
                                        <form method="POST" action="{{ route('admin.oficina-expertise-processes.destroy', $process) }}" style="display:inline" onsubmit="return confirm('Eliminar esta peritagem?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-xs btn-danger" type="submit">Eliminar</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted">Sem peritagens de oficina.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $processes->links() }}
        </div>
    </div>
</div>
@endsection

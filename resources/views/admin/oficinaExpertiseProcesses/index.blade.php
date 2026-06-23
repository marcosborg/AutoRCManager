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
                <div class="row oficina-expertise-kanban">
                    @foreach(\App\Models\OficinaExpertiseProcess::STATUS_SELECT as $status => $label)
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading"><strong>{{ $label }}</strong></div>
                                <div class="panel-body kanban-column" data-status="{{ $status }}" style="min-height: 120px;">
                                    @forelse(($kanbanProcesses[$status] ?? collect()) as $process)
                                        <div class="well well-sm kanban-card" draggable="{{ Gate::allows('oficina_expertise_process_change_status') ? 'true' : 'false' }}" data-id="{{ $process->id }}" data-status="{{ $process->status }}" data-update-url="{{ route('admin.oficina-expertise-processes.update-status', $process) }}" style="cursor: {{ Gate::allows('oficina_expertise_process_change_status') ? 'move' : 'default' }}; {{ $process->is_alert ? 'border-left:4px solid #dd4b39;' : '' }}">
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
                                <td>{{ optional($process->scheduled_expertise_date)->format('Y-m-d H:i') ?: '-' }}</td>
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

@if(request('view') === 'kanban')
    <div class="modal fade" id="kanban-status-date-modal" tabindex="-1" role="dialog" aria-labelledby="kanban-status-date-title">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="kanban-status-date-title">Data do estado</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label id="kanban-status-date-label" for="kanban-status-date-input">Data</label>
                        <input class="form-control" type="datetime-local" id="kanban-status-date-input">
                        <span class="help-block">Esta é a data e hora em que o passo ficou agendado/realizado, não a data em que moveste o cartão.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="kanban-status-date-confirm">Guardar estado</button>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@section('scripts')
@parent
<script>
document.addEventListener('DOMContentLoaded', function () {
    var statusDateConfig = @json(collect(\App\Models\OficinaExpertiseProcess::STATUS_SELECT)->mapWithKeys(function ($label, $status) {
        return \App\Models\OficinaExpertiseProcess::dateFieldForStatus($status)
            ? [$status => ['label' => \App\Models\OficinaExpertiseProcess::dateLabelForStatus($status)]]
            : [];
    }));
    var draggedCard = null;
    var originalColumn = null;
    var originalNext = null;

    function requestStatusDate(status) {
        var config = statusDateConfig[status];
        if (!config) {
            return Promise.resolve(null);
        }

        return new Promise(function (resolve, reject) {
            var modal = $('#kanban-status-date-modal');
            var input = document.getElementById('kanban-status-date-input');
            document.getElementById('kanban-status-date-label').textContent = config.label;
            document.getElementById('kanban-status-date-title').textContent = config.label;
            input.value = '';

            modal.off('hidden.bs.modal.kanban-date');
            $('#kanban-status-date-confirm').off('click.kanban-date').on('click.kanban-date', function () {
                if (!input.value) {
                    input.focus();
                    return;
                }

                modal.off('hidden.bs.modal.kanban-date');
                modal.modal('hide');
                resolve(input.value);
            });

            modal.on('hidden.bs.modal.kanban-date', function () {
                reject(new Error('Alteração cancelada.'));
            });

            modal.modal('show');
        });
    }

    function restoreCard(card) {
        if (originalNext && originalNext.parentElement === originalColumn) {
            originalColumn.insertBefore(card, originalNext);
        } else if (originalColumn) {
            originalColumn.appendChild(card);
        }
    }

    document.querySelectorAll('.kanban-card[draggable="true"]').forEach(function (card) {
        card.addEventListener('dragstart', function (event) {
            draggedCard = card;
            originalColumn = card.parentElement;
            originalNext = card.nextElementSibling;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.id);
            card.style.opacity = '0.6';
        });

        card.addEventListener('dragend', function () {
            card.style.opacity = '';
        });
    });

    document.querySelectorAll('.kanban-column').forEach(function (column) {
        column.addEventListener('dragover', function (event) {
            if (!draggedCard) {
                return;
            }

            event.preventDefault();
            column.style.background = '#f7fbff';
        });

        column.addEventListener('dragleave', function () {
            column.style.background = '';
        });

        column.addEventListener('drop', function (event) {
            event.preventDefault();
            column.style.background = '';

            if (!draggedCard || draggedCard.dataset.status === column.dataset.status) {
                return;
            }

            var card = draggedCard;
            var targetStatus = column.dataset.status;

            requestStatusDate(targetStatus).then(function (statusDate) {
                column.appendChild(card);

                fetch(card.dataset.updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({status: targetStatus, date: statusDate})
                }).then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () {
                        return {message: 'Não foi possível alterar o estado.'};
                    }).then(function (payload) {
                        throw new Error(payload.message || 'Não foi possível alterar o estado.');
                    });
                }

                return response.json();
                }).then(function (payload) {
                    card.dataset.status = payload.status || targetStatus;
                }).catch(function (error) {
                    restoreCard(card);
                    alert(error.message);
                });
            }).catch(function () {
                restoreCard(card);
            });
        });
    });
});
</script>
@endsection

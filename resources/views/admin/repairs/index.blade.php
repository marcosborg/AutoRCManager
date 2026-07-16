@extends('layouts.admin')
@section('content')
<div class="content">
    @php
        $sortIcon = function ($column) use ($sort, $dir) {
            if ($sort !== $column) {
                return '';
            }

            return $dir === 'asc' ? ' ↑' : ' ↓';
        };

        $sortUrl = function ($column) use ($sort, $dir, $licenseFilter, $stateFilter, $openOnly) {
            $nextDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';

            return route('admin.repairs.index', [
                'license' => $licenseFilter !== '' ? $licenseFilter : null,
                'workshop_state' => $stateFilter !== null && $stateFilter !== '' ? $stateFilter : null,
                'open_only' => $openOnly ? 1 : null,
                'sort' => $column,
                'dir' => $nextDir,
            ]);
        };
    @endphp

    @if(!$workshopReadOnly)
    @can('repair_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.repairs.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.repair.title_singular') }}
                </a>
                <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                    {{ trans('global.app_csvImport') }}
                </button>
                @include('csvImport.modal', ['model' => 'Repair', 'route' => 'admin.repairs.parseCsvImport'])
                @can('workshop_state_access')
                    <a class="btn btn-default" href="{{ route('admin.workshop-states.index') }}">Estados da Oficina</a>
                @endcan
            </div>
        </div>
    @endcan
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-4 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-aqua"><i class="fa fa-car"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Viaturas enviadas</span>
                            <span class="info-box-number">{{ $workshopSummary['vehicles_sent'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-green"><i class="fa fa-wrench"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total de intervenções</span>
                            <span class="info-box-number">{{ $workshopSummary['total_interventions'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-yellow"><i class="fa fa-tools"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Viaturas atualmente em oficina</span>
                            <span class="info-box-number">{{ $workshopSummary['vehicles_currently_in_workshop'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    Filtros
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.repairs.index') }}" class="form-inline">
                        <div class="form-group" style="margin-right: 10px;">
                            <label for="license" style="margin-right: 6px;">Matricula</label>
                            <input
                                type="text"
                                class="form-control"
                                id="license"
                                name="license"
                                value="{{ $licenseFilter }}"
                                placeholder="Ex: 12-AB-34"
                            >
                        </div>

                        <div class="form-group" style="margin-right: 10px;">
                            <label for="workshop_state" style="margin-right: 6px;">Estado da Oficina</label>
                            <select class="form-control" id="workshop_state" name="workshop_state">
                                <option value="">Todos</option>
                                <option value="__null" {{ $stateFilter === '__null' ? 'selected' : '' }}>Sem estado</option>
                                @foreach($workshopStates as $state)
                                    <option value="{{ $state->id }}" {{ (string) $stateFilter === (string) $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}{{ $state->is_active ? '' : ' (inativo)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="checkbox" style="margin-right: 10px;">
                            <label>
                                <input type="checkbox" name="open_only" value="1" {{ $openOnly ? 'checked' : '' }}>
                                So com abertas
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('admin.repairs.index') }}" class="btn btn-default">Limpar</a>
                    </form>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    Viaturas na oficina
                    <span class="badge" style="margin-left: 8px;">{{ $groupedRepairs->count() }}</span>
                </div>
                <div class="panel-body">
                    @if($groupedRepairs->isEmpty())
                        <p class="text-muted">Não existem viaturas na oficina com estes filtros.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="{{ $sortUrl('license') }}">
                                                Viatura{!! $sortIcon('license') !!}
                                            </a>
                                        </th>
                                        <th>Marca / Modelo</th>
                                        <th>Estado geral</th>
                                        <th>
                                            <a href="{{ $sortUrl('latest') }}">
                                                Ultima intervencao{!! $sortIcon('latest') !!}
                                            </a>
                                        </th>
                                        <th>Estado da Oficina</th>
                                        <th>Ações necessárias</th>
                                        <th>Total intervencoes</th>
                                        <th>
                                            <a href="{{ $sortUrl('open_count') }}">
                                                Abertas{!! $sortIcon('open_count') !!}
                                            </a>
                                        </th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupedRepairs as $row)
                                        @php
                                            $vehicle = $row['vehicle'];
                                            $latest = $row['latest'];
                                            $open = $row['open'];
                                            $license = $vehicle->license ?? $vehicle->foreign_license ?? ('#' . $vehicle->id);
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $license }}</strong>
                                            </td>
                                            <td>
                                                {{ $vehicle->brand->name ?? '-' }} {{ $vehicle->model ?? '' }}
                                            </td>
                                            <td>
                                                {{ $vehicle->general_state->name ?? '-' }}
                                            </td>
                                            <td>
                                                @if($latest)
                                                    <span>#{{ $latest->id }}</span><br>
                                                    <small class="text-muted">{{ optional($latest->created_at)->format('Y-m-d H:i') }}</small>
                                                @else
                                                    <span class="text-muted">Sem intervenções</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$workshopReadOnly)
                                                @can('workshop_state_edit')
                                                    <form method="POST" action="{{ route('admin.vehicles.workshop-state.update', $vehicle) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select class="form-control input-sm" name="workshop_state_id" onchange="this.form.submit()">
                                                            @foreach($workshopStates as $workshopState)
                                                                <option
                                                                    value="{{ $workshopState->id }}"
                                                                    {{ (int) $vehicle->workshop_state_id === $workshopState->id ? 'selected' : '' }}
                                                                    {{ !$workshopState->is_active && (int) $vehicle->workshop_state_id !== $workshopState->id ? 'disabled' : '' }}
                                                                >
                                                                    {{ $workshopState->name }}{{ $workshopState->is_active ? '' : ' (inativo)' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                @else
                                                    {{ $vehicle->workshop_state->name ?? 'Sem estado' }}
                                                @endcan
                                                @else
                                                    {{ $vehicle->workshop_state->name ?? 'Sem estado' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$vehicle->key)
                                                    <span class="label label-danger" data-second-key-warning="{{ $vehicle->id }}">
                                                        <i class="fa fa-key"></i> Fazer segunda chave
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge">{{ $row['count'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $row['open_count'] > 0 ? 'bg-yellow' : 'bg-green' }}">{{ $row['open_count'] }}</span>
                                            </td>
                                            <td>
                                                @if($workshopReadOnly)
                                                    <span class="text-muted">Apenas consulta</span>
                                                @else
                                                @can('vehicle_edit')
                                                    <a class="btn btn-xs btn-default" href="{{ route('admin.vehicles.edit', $vehicle) }}">
                                                        Abrir viatura
                                                    </a>
                                                @elsecan('vehicle_show')
                                                    <a class="btn btn-xs btn-default" href="{{ route('admin.vehicles.show', $vehicle) }}">
                                                        Ver viatura
                                                    </a>
                                                @endcan
                                                @if($latest)
                                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.repairs.edit', $latest) }}">Abrir última</a>
                                                @endif
                                                @if($open && $open->id !== $latest->id)
                                                    <a class="btn btn-xs btn-info" href="{{ route('admin.repairs.edit', $open->id) }}">
                                                        Abrir em curso
                                                    </a>
                                                @endif
                                                @can('repair_access')
                                                    <form method="POST" action="{{ route('admin.vehicles.workshop.destroy', $vehicle) }}" style="display:inline;" onsubmit="return confirm('Retirar esta viatura da oficina e repor o estado anterior?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-xs btn-danger" type="submit">Eliminar</button>
                                                    </form>
                                                @endcan
                                                @can('repair_create')
                                                    @if(!$open)
                                                        <form method="POST" action="{{ route('admin.vehicles.start-intervention', $vehicle) }}" style="display:inline;">
                                                            @csrf
                                                            <button class="btn btn-xs btn-success" type="submit">Iniciar intervenção</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

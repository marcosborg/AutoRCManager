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
                'state' => $stateFilter !== null && $stateFilter !== '' ? $stateFilter : null,
                'open_only' => $openOnly ? 1 : null,
                'sort' => $column,
                'dir' => $nextDir,
            ]);
        };
    @endphp

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
            </div>
        </div>
    @endcan

    <div class="row">
        <div class="col-lg-12">
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
                            <label for="state" style="margin-right: 6px;">Estado</label>
                            <select class="form-control" id="state" name="state">
                                <option value="">Todos</option>
                                <option value="__null" {{ $stateFilter === '__null' ? 'selected' : '' }}>Sem estado</option>
                                @foreach($repairStates as $state)
                                    <option value="{{ $state->id }}" {{ (string) $stateFilter === (string) $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
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
                    Intervencoes por viatura
                    <span class="badge" style="margin-left: 8px;">{{ $groupedRepairs->count() }}</span>
                </div>
                <div class="panel-body">
                    @if($groupedRepairs->isEmpty())
                        <p class="text-muted">Ainda nao existem intervencoes registadas.</p>
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
                                        <th>
                                            <a href="{{ $sortUrl('latest') }}">
                                                Ultima intervencao{!! $sortIcon('latest') !!}
                                            </a>
                                        </th>
                                        <th>Estado atual</th>
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
                                                <span>#{{ $latest->id }}</span><br>
                                                <small class="text-muted">{{ optional($latest->created_at)->format('Y-m-d H:i') }}</small>
                                            </td>
                                            <td>
                                                @if($open)
                                                    <span class="label label-warning">Intervencao aberta</span>
                                                @else
                                                    <span class="label label-success">Sem abertas</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge">{{ $row['count'] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $row['open_count'] > 0 ? 'bg-yellow' : 'bg-green' }}">{{ $row['open_count'] }}</span>
                                            </td>
                                            <td>
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.repairs.edit', $latest->id) }}">
                                                    Abrir ultima
                                                </a>
                                                @if($open && $open->id !== $latest->id)
                                                    <a class="btn btn-xs btn-info" href="{{ route('admin.repairs.edit', $open->id) }}">
                                                        Abrir em curso
                                                    </a>
                                                @endif
                                                @can('repair_create')
                                                    @if(!$open)
                                                        <form method="POST" action="{{ route('admin.repairs.newIntervention', $latest->id) }}" style="display:inline;">
                                                            @csrf
                                                            <button class="btn btn-xs btn-success" type="submit">Nova intervencao</button>
                                                        </form>
                                                    @endif
                                                @endcan
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

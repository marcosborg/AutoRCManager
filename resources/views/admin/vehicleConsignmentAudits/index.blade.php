@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Histórico de alterações às consignações</div>
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.vehicle-consignments.history') }}" class="form-inline" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label for="license">Matrícula</label>
                    <input class="form-control" id="license" name="license" value="{{ $license }}" placeholder="Ex.: 12-AB-34">
                </div>
                <div class="form-group">
                    <label for="occurrence_date">Data da ocorrência</label>
                    <input class="form-control" type="date" id="occurrence_date" name="occurrence_date" value="{{ $occurrenceDate }}">
                    @if($errors->has('occurrence_date'))<span class="help-block text-danger">{{ $errors->first('occurrence_date') }}</span>@endif
                </div>
                <button class="btn btn-primary" type="submit">Pesquisar</button>
                <a class="btn btn-default" href="{{ route('admin.vehicle-consignments.history') }}">Limpar</a>
                <a class="btn btn-default" href="{{ route('admin.vehicle-consignments.index') }}">Voltar às consignações</a>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ação</th>
                            <th>Matrícula</th>
                            <th>Consignação</th>
                            <th>Utilizador</th>
                            <th>IP</th>
                            <th>Alterações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            @php
                                $before = $audit->before ?? [];
                                $after = $audit->after ?? [];
                                $keys = collect(array_unique(array_merge(array_keys($before), array_keys($after))))
                                    ->reject(fn ($key) => in_array($key, ['created_at', 'updated_at'], true));
                                $changes = $keys->filter(fn ($key) => ($before[$key] ?? null) != ($after[$key] ?? null));
                                $actionLabels = ['created' => 'Criada', 'updated' => 'Editada', 'deleted' => 'Eliminada'];
                            @endphp
                            <tr>
                                <td style="white-space:nowrap;">{{ optional($audit->created_at)->format('Y-m-d H:i:s') }}</td>
                                <td><strong>{{ $actionLabels[$audit->action] ?? $audit->action }}</strong></td>
                                <td>
                                    @if($audit->vehicle_license_before && $audit->vehicle_license_before !== $audit->vehicle_license_after)
                                        {{ $audit->vehicle_license_before }} → {{ $audit->vehicle_license_after ?: '-' }}
                                    @else
                                        {{ $audit->vehicle_license_after ?: $audit->vehicle_license_before ?: '-' }}
                                    @endif
                                </td>
                                <td>#{{ $audit->consignment_id }}</td>
                                <td>{{ $audit->user_name ?: 'Sistema' }} @if($audit->user_id)(#{{ $audit->user_id }})@endif</td>
                                <td>{{ $audit->ip_address ?: '-' }}</td>
                                <td>
                                    @forelse($changes as $key)
                                        <div><strong>{{ $key }}:</strong> {{ is_scalar($before[$key] ?? null) ? ($before[$key] ?? '-') : json_encode($before[$key] ?? null) }} → {{ is_scalar($after[$key] ?? null) ? ($after[$key] ?? '-') : json_encode($after[$key] ?? null) }}</div>
                                    @empty
                                        <span class="text-muted">Sem diferenças de dados</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Sem alterações para apresentar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $audits->links() }}
        </div>
    </div>
</div>
@endsection

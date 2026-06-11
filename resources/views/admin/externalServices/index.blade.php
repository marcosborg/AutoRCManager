@extends('layouts.admin')
@section('content')
<div class="content">
    @can('external_service_create')
        <p><a class="btn btn-success" href="{{ route('admin.external-services.create') }}">Novo serviço externo</a></p>
    @endcan
    <div class="panel panel-default">
        <div class="panel-heading">Serviços Externos</div>
        <div class="panel-body">
            <form method="GET" class="row" style="margin-bottom:15px;">
                <div class="col-md-3"><select class="form-control" name="status"><option value="">Estado</option>@foreach(App\Models\ExternalService::STATUS_SELECT as $key => $label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><select class="form-control select2" name="suplier_id"><option value="">Fornecedor</option>@foreach($supliers as $id => $label)<option value="{{ $id }}" {{ (string) request('suplier_id') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-4"><input class="form-control" name="vehicle_search" value="{{ request('vehicle_search') }}" placeholder="Matrícula/modelo"></div>
                <div class="col-md-2"><button class="btn btn-default" type="submit">Filtrar</button></div>
            </form>
            <div class="table-responsive"><table class="table table-bordered table-striped">
                <thead><tr><th>ID</th><th>Viatura</th><th>Serviço</th><th>Fornecedor</th><th>Estado</th><th>Prevista</th><th>Valor</th><th></th></tr></thead>
                <tbody>@forelse($externalServices as $service)<tr>
                    <td>#{{ $service->id }}</td>
                    <td>{{ $service->vehicle->license ?? $service->vehicle->foreign_license ?? '-' }}</td>
                    <td>{{ $service->description }}</td><td>{{ $service->suplier->name ?? '-' }}</td>
                    <td>{{ App\Models\ExternalService::STATUS_SELECT[$service->status] ?? $service->status }}</td>
                    <td>{{ optional($service->expected_date)->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $service->amount !== null ? number_format($service->amount, 2, ',', '.').' €' : '-' }}</td>
                    <td>@can('external_service_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.external-services.edit', $service) }}">Editar</a>@endcan @can('external_service_delete')<form method="POST" action="{{ route('admin.external-services.destroy', $service) }}" style="display:inline" onsubmit="return confirm('Eliminar este serviço externo?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Eliminar</button></form>@endcan</td>
                </tr>@empty<tr><td colspan="8" class="text-center text-muted">Sem serviços externos.</td></tr>@endforelse</tbody>
            </table></div>
            {{ $externalServices->links() }}
        </div>
    </div>
</div>
@endsection

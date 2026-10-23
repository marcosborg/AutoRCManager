@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Viaturas</div><div class="panel-body">
<form method="GET"><div class="form-group"><label for="q">Pesquisar matrícula ou modelo</label><input class="form-control" id="q" name="q" value="{{ $term }}"></div><button class="btn btn-default">Pesquisar</button></form>
<table class="table table-striped"><thead><tr><th>Matrícula</th><th>Marca / modelo</th><th>Estado da oficina</th><th></th></tr></thead><tbody>
@foreach($vehicles as $vehicle)<tr><td>{{ $vehicle->license ?: $vehicle->foreign_license }}</td><td>{{ $vehicle->brand?->name }} {{ $vehicle->model }}</td><td>{{ $vehicle->workshop_state?->name ?: 'Por definir' }}</td><td>@can('vehicle_edit')<a class="btn btn-xs btn-primary" href="{{ route('admin.vehicles.edit', $vehicle->id) }}">Editar</a>@elsecan('vehicle_show')<a class="btn btn-xs btn-primary" href="{{ route('admin.vehicles.show', $vehicle->id) }}">Ver</a>@endcan</td></tr>@endforeach
</tbody></table>{{ $vehicles->links() }}
</div></div></div>
@endsection

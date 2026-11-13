@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Peças de desmantelamento</div><div class="panel-body">
@can('repair_edit')<p><a class="btn btn-success" href="{{ route('admin.dismantled-parts.create') }}">Registar peça</a></p>@endcan
<form method="GET" class="form-inline"><label for="search">Pesquisar referência, peça ou matrícula</label> <input class="form-control" id="search" name="search" value="{{ request('search') }}">
<label for="filter_condition">Condição</label><select class="form-control" id="filter_condition" name="condition"><option value="">Todas</option>@foreach(App\Models\DismantledPart::CONDITIONS as $key=>$label)<option value="{{ $key }}" {{ request('condition')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select>
<label for="filter_location">Localização</label><input class="form-control" id="filter_location" name="location" value="{{ request('location') }}">
<button class="btn btn-primary">Pesquisar</button> <a href="{{ route('admin.dismantled-parts.index') }}">Limpar</a></form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Viatura de origem</th><th>Referência</th><th>Peça</th><th>Condição</th><th>Localização</th><th>Valor (€)</th><th>Notas</th><th>Registada por</th><th>Ações</th></tr></thead><tbody>
@forelse($parts as $part)<tr><td>{{ $part->id }}</td><td>{{ $part->vehicle?->license ?: $part->vehicle?->foreign_license ?: '#'.$part->vehicle_id }}</td><td>{{ $part->reference ?: '—' }}</td><td>{{ $part->description }}</td><td>{{ App\Models\DismantledPart::CONDITIONS[$part->condition] ?? $part->condition }}</td><td>{{ $part->location }}</td><td>{{ $part->value === null ? 'Por definir' : number_format((float)$part->value,2,',','.') }}</td><td style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $part->notes }}</td><td>{{ $part->createdBy?->name }} · {{ $part->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}</td><td style="white-space:nowrap"><a class="btn btn-xs btn-default" href="{{ route('admin.dismantled-parts.history',$part) }}" aria-label="Ver histórico da peça #{{ $part->id }}">Histórico</a> @can('repair_edit')<a class="btn btn-xs btn-primary" href="{{ route('admin.dismantled-parts.edit',$part) }}" aria-label="Corrigir ficha da peça #{{ $part->id }}">Corrigir ficha</a>@endcan</td></tr>@empty<tr><td colspan="10">Ainda não existem peças registadas.</td></tr>@endforelse
</tbody></table></div>{{ $parts->links() }}
</div></div></div>
@endsection

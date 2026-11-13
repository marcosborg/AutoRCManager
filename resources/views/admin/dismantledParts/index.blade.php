@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Peças de desmantelamento</div><div class="panel-body">
@can('repair_edit')<p><a class="btn btn-success" href="{{ route('admin.dismantled-parts.create') }}">Registar peça</a></p>@endcan
<form method="GET" class="form-inline"><label for="search">Pesquisar referência, peça, localização ou matrícula</label> <input class="form-control" id="search" name="search" value="{{ request('search') }}"> <button class="btn btn-primary">Pesquisar</button> <a href="{{ route('admin.dismantled-parts.index') }}">Limpar</a></form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Viatura de origem</th><th>Referência</th><th>Peça</th><th>Condição</th><th>Localização</th><th>Valor (€)</th><th>Notas</th><th>Registada por</th></tr></thead><tbody>
@forelse($parts as $part)<tr><td>{{ $part->id }}</td><td>{{ $part->vehicle?->license ?: $part->vehicle?->foreign_license ?: '#'.$part->vehicle_id }}</td><td>{{ $part->reference }}</td><td>{{ $part->description }}</td><td>{{ App\Models\DismantledPart::CONDITIONS[$part->condition] }}</td><td>{{ $part->location }}</td><td>{{ $part->value === null ? 'Por definir' : number_format((float)$part->value,2,',','.') }}</td><td>{{ $part->notes }}</td><td>{{ $part->createdBy?->name }} · {{ $part->created_at->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="9">Ainda não existem peças registadas.</td></tr>@endforelse
</tbody></table></div>{{ $parts->links() }}
@can('repair_edit')<div class="panel panel-default"><div class="panel-heading">Corrigir ficha existente</div><div class="panel-body">@foreach($parts as $part)<a class="btn btn-default" style="margin:3px" href="{{ route('admin.dismantled-parts.edit',$part) }}">#{{ $part->id }} · {{ $part->description }}</a>@endforeach</div></div>@endcan
<p>Histórico das peças desta página:</p>@foreach($parts as $part)<a class="btn btn-default" style="margin:3px" href="{{ route('admin.dismantled-parts.history',$part) }}">Histórico #{{ $part->id }}</a>@endforeach
</div></div></div>
@endsection

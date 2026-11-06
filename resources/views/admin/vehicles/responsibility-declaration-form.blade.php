@extends('layouts.admin')
@section('content')
<div class="panel panel-default"><div class="panel-heading">Termo de responsabilidade — {{ $vehicle->license ?: $vehicle->foreign_license }}</div><div class="panel-body">
<p>Confira os dados e reveja o termo antes de imprimir. Os campos opcionais vazios ficam em branco para preenchimento no impresso.</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.vehicles.documents.responsibility.prepare', $vehicle) }}">@csrf
<div class="row">@foreach($fields as $key => $label)
@php($required = in_array($key, ['name', 'license']))
<div class="col-md-6 form-group"><label for="responsibility-{{ $key }}">{{ $label }}{{ $required ? '' : ' (opcional)' }}</label><input class="form-control" id="responsibility-{{ $key }}" name="{{ $key }}" maxlength="100" value="{{ old($key, $data[$key] ?? '') }}" @required($required)></div>
@endforeach</div>
<h4>Levantamento e entrega</h4>
<div class="row">@foreach($dates as $key => $label)
<div class="col-md-6 form-group"><label for="responsibility-{{ $key }}">{{ $label }} (opcional)</label><input type="{{ str_ends_with($key, '_time') ? 'time' : 'date' }}" class="form-control" id="responsibility-{{ $key }}" name="{{ $key }}" value="{{ old($key, $data[$key] ?? '') }}"></div>
@endforeach</div>
<p class="help-block">Descarregue o PDF antes de sair. O cliente e o stand assinam no impresso.</p>
<button class="btn btn-primary">Rever termo de responsabilidade</button> <a class="btn btn-default" href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-preparation-sale">Voltar à viatura</a>
</form></div></div>
@endsection

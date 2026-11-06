@extends('layouts.admin')
@section('content')
<div class="panel panel-default"><div class="panel-heading">Declaração de garantia automóvel — {{ $vehicle->license ?: $vehicle->foreign_license }}</div><div class="panel-body">
<p>Confira os dados do cliente e da viatura antes de imprimir. Preencha apenas as datas e os quilómetros acordados; os campos opcionais vazios ficam em branco no impresso.</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.vehicles.documents.warranty.prepare', $vehicle) }}">@csrf
<div class="row">@foreach($fields as $key => $label)
<div class="col-md-6 form-group"><label for="warranty-{{ $key }}">{{ $label }}{{ in_array($key, ['identity', 'vin']) ? ' (opcional)' : '' }}</label><input class="form-control" id="warranty-{{ $key }}" name="{{ $key }}" maxlength="100" value="{{ old($key, $data[$key] ?? '') }}" @required(!in_array($key, ['identity', 'vin']))></div>
@endforeach</div>
<div class="row">@foreach(['starts_on' => 'Data de início', 'ends_on' => 'Data de conclusão'] as $key => $label)
<div class="col-md-6 form-group"><label for="warranty-{{ $key }}">{{ $label }} (opcional)</label><input type="date" class="form-control" id="warranty-{{ $key }}" name="{{ $key }}" value="{{ old($key, $data[$key] ?? '') }}"></div>
@endforeach</div>
<div class="row">@foreach(['kilometers' => 'Quilómetros da viatura', 'covered_kilometers' => 'Quilómetros indicados para o fim da garantia'] as $key => $label)
<div class="col-md-6 form-group"><label for="warranty-{{ $key }}">{{ $label }} (opcional)</label><input type="number" min="{{ $key === 'kilometers' ? 0 : 1 }}" max="9999999" class="form-control" id="warranty-{{ $key }}" name="{{ $key }}" value="{{ old($key, $data[$key] ?? '') }}"></div>
@endforeach</div>
<details><summary>Consultar condições e exclusões do impresso</summary>@include('admin.vehicles.partials.warranty-terms')</details>
<p style="margin-top:15px"><label><input required type="checkbox" name="confirmed" value="1"> Conferi os dados e confirmo as condições da garantia a emitir.</label></p>
<p class="help-block">O rascunho não fica guardado. Descarregue o PDF antes de sair.</p>
<button class="btn btn-primary">Rever declaração de garantia</button> <a class="btn btn-default" href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-preparation-sale">Voltar à viatura</a>
</form></div></div>
@endsection

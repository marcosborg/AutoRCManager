@extends('layouts.admin')
@section('content')
<div class="panel panel-default"><div class="panel-heading">Declaração de circulação — {{ $vehicle->license ?: $vehicle->foreign_license }}</div><div class="panel-body">
<p>Confira os dados antes de preparar a declaração. Os campos opcionais vazios ficam em branco para preenchimento no impresso.</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.vehicles.documents.circulation.prepare', $vehicle) }}">@csrf
<div class="row">@foreach($fields as $key => $label)
@php($required = in_array($key, ['name', 'brand', 'model', 'license']))
<div class="col-md-6 form-group"><label for="circulation-{{ $key }}">{{ $label }}{{ $required ? '' : ' (opcional)' }}</label><input class="form-control" id="circulation-{{ $key }}" name="{{ $key }}" maxlength="100" value="{{ old($key, $data[$key] ?? '') }}" @required($required)></div>
@endforeach</div>
<div class="form-group"><label for="circulation-date">Data da venda</label><input required type="date" class="form-control" id="circulation-date" name="sold_on" value="{{ old('sold_on', $data['sold_on'] ?? '') }}"></div>
<p>Esta declaração é emitida pela Geração Determinada Automóveis, Lda., CAR7, e indica que a documentação está na conservatória para transferência de propriedade.</p>
<p><label><input required type="checkbox" name="confirmed" value="1"> Conferi os dados e confirmo que a venda e a situação da documentação correspondem ao que consta nesta declaração.</label></p>
<p class="help-block">Descarregue o PDF antes de sair. A assinatura é feita no impresso.</p>
<button class="btn btn-primary">Rever declaração de circulação</button> <a class="btn btn-default" href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-preparation-sale">Voltar à viatura</a>
</form></div></div>
@endsection

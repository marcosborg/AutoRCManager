@extends('layouts.admin')
@section('content')
<div class="panel panel-default">
<div class="panel-heading">Declaração de entrega de veículo — Retoma {{ $tradeIn->license }}</div>
<div class="panel-body">
<p>Confira os dados do proprietário e da viatura recebida de retoma. Os campos opcionais vazios ficam em branco para preencher no impresso.</p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.vehicle-trade-ins.delivery.prepare', $tradeIn) }}">
@csrf
<div class="row">
@foreach($fields as $key => $label)
@php($required = in_array($key, ['name', 'license']))
<div class="col-md-6 form-group"><label for="delivery-{{ $key }}">{{ $label }}{{ $required ? '' : ' (opcional)' }}</label>
<input class="form-control" id="delivery-{{ $key }}" name="{{ $key }}" maxlength="150" value="{{ old($key, $data[$key] ?? '') }}" @required($required)></div>
@endforeach
<div class="col-md-6 form-group"><label for="delivery-kilometers">Quilómetros à entrada (opcional)</label><input class="form-control" type="number" min="0" max="9999999" step="1" id="delivery-kilometers" name="kilometers" value="{{ old('kilometers', $data['kilometers'] ?? '') }}"></div>
<div class="col-md-6 form-group"><label for="delivery-date">Data de entrega (opcional)</label><input class="form-control" type="date" id="delivery-date" name="delivery_date" value="{{ old('delivery_date', $data['delivery_date'] ?? '') }}"></div>
</div>
<div class="form-group"><label for="delivery-faults">Avarias e danos informados pelo cliente (opcional)</label><textarea class="form-control" rows="4" maxlength="1200" id="delivery-faults" name="faults">{{ old('faults', $data['faults'] ?? '') }}</textarea></div>
<p class="help-block">Descarregue o PDF antes de sair. O proprietário e o stand assinam no impresso.</p>
<button class="btn btn-primary" type="submit">Rever declaração de entrega</button>
@if($tradeIn->sold_vehicle_id)<a class="btn btn-default" href="{{ route('admin.vehicles.edit', $tradeIn->sold_vehicle_id) }}#vehicle-preparation-sale">Voltar à venda</a>@endif
</form></div></div>
@endsection

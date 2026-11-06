@extends('layouts.admin')
@section('content')
<div class="panel panel-default"><div class="panel-heading">Declaração de compra e venda — {{ $vehicle->license ?: $vehicle->foreign_license }}</div><div class="panel-body">
<p>Preencha e confira os dados antes de imprimir.</p>
<p>Empresa vendedora: <strong>Geração Determinada – Automóveis, Lda.</strong></p>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.vehicles.documents.sale.prepare', $vehicle) }}">@csrf
<h4>Cliente, viatura e retoma</h4><div class="row">
@foreach($fields as $key => $label)
<div class="col-md-6 form-group"><label for="sale-{{ $key }}">{{ $label }}</label><input class="form-control" id="sale-{{ $key }}" name="{{ $key }}" value="{{ old($key, $data[$key] ?? '') }}" maxlength="{{ $key === 'address' ? 250 : 100 }}"></div>
@endforeach
</div><h4>Valores acordados</h4><p>Preencha os montantes confirmados. Use 0 quando não se aplicar. Entrada e sinal são campos distintos; não são somados nem deduzidos automaticamente.</p><div class="row">
@foreach($amounts as $key => $label)
<div class="col-md-4 form-group"><label for="sale-{{ $key }}">{{ $label }} (€)</label><input required class="form-control" id="sale-{{ $key }}" name="{{ $key }}" inputmode="decimal" value="{{ old($key, $data[$key] ?? '') }}"></div>
@endforeach
</div><div class="row"><div class="col-md-4 form-group"><label for="sale-guarantee">Garantia</label><select required class="form-control" id="sale-guarantee" name="guarantee"><option value="">Escolher</option><option value="yes" @selected(old('guarantee', $data['guarantee'] ?? '') === 'yes')>Sim</option><option value="no" @selected(old('guarantee', $data['guarantee'] ?? '') === 'no')>Não</option></select></div>
<div class="col-md-4 form-group"><label for="sale-months">Meses de garantia (deixe vazio se Não)</label><input type="number" min="1" max="120" class="form-control" id="sale-months" name="months" value="{{ old('months', $data['months'] ?? '') }}"></div>
<div class="col-md-4 form-group"><label for="sale-date">Data</label><input required type="date" class="form-control" id="sale-date" name="document_date" value="{{ old('document_date', $data['document_date'] ?? '') }}"></div></div>
<p><label><input required type="checkbox" name="company_confirmed" value="1"> Confirmo que esta venda é da Geração Determinada – Automóveis, Lda.</label></p>
<p><label><input required type="checkbox" name="confirmed" value="1"> Conferi os dados e os valores com o cliente.</label></p>
<p class="help-block"><small>O rascunho não fica guardado. Descarregue o PDF antes de sair.</small></p>
<button class="btn btn-primary">Rever declaração</button> <a class="btn btn-default" href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-documents">Voltar à viatura</a>
</form></div></div>
@endsection

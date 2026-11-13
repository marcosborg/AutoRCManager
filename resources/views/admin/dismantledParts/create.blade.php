@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Registar peça de desmantelamento</div><div class="panel-body">
<p>Este registo não altera custos da viatura nem cria pagamentos.</p>
@foreach($errors->all() as $error)<p class="text-danger">{{ $error }}</p>@endforeach
<form method="POST" action="{{ route('admin.dismantled-parts.store') }}">@csrf
<input type="hidden" name="submission_token" value="{{ old('submission_token',(string)Illuminate\Support\Str::uuid()) }}">
<div class="form-group"><label for="vehicle_id">Número da ficha da viatura de origem</label><input class="form-control" id="vehicle_id" name="vehicle_id" type="number" min="1" required value="{{ old('vehicle_id') }}"></div>
@foreach(['reference'=>'Referência','description'=>'Descrição da peça','location'=>'Localização'] as $field=>$label)<div class="form-group"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" maxlength="255" {{ $field==='reference'?'':'required' }} value="{{ old($field) }}"></div>@endforeach
<div class="form-group"><label for="condition">Condição</label><select class="form-control" id="condition" name="condition">@foreach(App\Models\DismantledPart::CONDITIONS as $key=>$label)<option value="{{ $key }}" {{ old('condition','untested')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="form-group"><label for="value">Valor (€), facultativo</label><input class="form-control" id="value" name="value" type="number" step="0.01" min="0" value="{{ old('value') }}"></div>
<div class="form-group"><label for="notes">Notas</label><textarea class="form-control" id="notes" name="notes" maxlength="5000">{{ old('notes') }}</textarea></div>
<button class="btn btn-primary">Registar peça</button> <a href="{{ route('admin.dismantled-parts.index') }}">Cancelar</a>
</form></div></div></div>
@endsection

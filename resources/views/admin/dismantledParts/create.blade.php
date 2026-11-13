@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Registar peça de desmantelamento</div><div class="panel-body">
<p>Este registo não altera custos da viatura nem cria pagamentos.</p>
<form method="GET" class="form-inline" style="margin-bottom:15px"><label for="vehicle_search">Procurar viatura de origem por matrícula</label> <input class="form-control" id="vehicle_search" name="vehicle_search" value="{{ $search }}"> <button class="btn btn-default">Procurar viatura</button></form>
<p class="text-muted">São apresentadas até 25 viaturas. Confirme a matrícula e a ficha antes de registar a peça.</p>
@foreach($errors->all() as $error)<p class="text-danger">{{ $error }}</p>@endforeach
<form method="POST" action="{{ route('admin.dismantled-parts.store') }}">@csrf
<input type="hidden" name="submission_token" value="{{ old('submission_token',(string)Illuminate\Support\Str::uuid()) }}">
<div class="form-group"><label for="vehicle_id">Viatura de origem</label><select class="form-control" id="vehicle_id" name="vehicle_id" required><option value="">Escolha a viatura</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" {{ (string)old('vehicle_id')===(string)$vehicle->id?'selected':'' }}>#{{ $vehicle->id }} · {{ $vehicle->license ?: $vehicle->foreign_license ?: 'Sem matrícula' }} · {{ $vehicle->model }}</option>@endforeach</select></div>
@foreach(['reference'=>'Referência','description'=>'Descrição da peça','location'=>'Localização'] as $field=>$label)<div class="form-group"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" maxlength="255" {{ $field==='reference'?'':'required' }} value="{{ old($field) }}"></div>@endforeach
<div class="form-group"><label for="condition">Condição</label><select class="form-control" id="condition" name="condition">@foreach(App\Models\DismantledPart::CONDITIONS as $key=>$label)<option value="{{ $key }}" {{ old('condition','untested')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="form-group"><label for="value">Valor (€), facultativo</label><input class="form-control" id="value" name="value" type="number" step="0.01" min="0" value="{{ old('value') }}"></div>
<div class="form-group"><label for="notes">Notas</label><textarea class="form-control" id="notes" name="notes" maxlength="5000">{{ old('notes') }}</textarea></div>
<button class="btn btn-primary">Registar peça</button> <a href="{{ route('admin.dismantled-parts.index') }}">Cancelar</a>
</form></div></div></div>
@endsection

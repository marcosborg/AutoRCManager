@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Corrigir peça #{{ $part->id }}</div><div class="panel-body">
<p>Origem: ficha #{{ $part->vehicle_id }} · {{ $part->vehicle?->license ?: $part->vehicle?->foreign_license }}. A origem e o autor do registo são preservados.</p>
@foreach($errors->all() as $error)<p class="text-danger">{{ $error }}</p>@endforeach
<form method="POST" action="{{ route('admin.dismantled-parts.update',$part) }}">@csrf @method('PATCH')
<input type="hidden" name="revision" value="{{ $part->revisionToken() }}">
@foreach(['reference'=>'Referência (facultativa)','description'=>'Descrição da peça','location'=>'Localização'] as $field=>$label)<div class="form-group"><label for="part-{{ $field }}">{{ $label }}</label><input class="form-control" id="part-{{ $field }}" name="{{ $field }}" maxlength="255" {{ $field==='reference'?'':'required' }} value="{{ old($field,$part->$field) }}"></div>@endforeach
<div class="form-group"><label for="part-condition">Condição</label><select class="form-control" id="part-condition" name="condition">@foreach(App\Models\DismantledPart::CONDITIONS as $key=>$label)<option value="{{ $key }}" {{ old('condition',$part->condition)===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="form-group"><label for="part-value">Valor da peça (€), facultativo</label><input class="form-control" id="part-value" name="value" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('value',$part->value) }}"><p class="help-block">Deixe em branco se o valor ainda não estiver definido. Corrigir este valor não cria pagamentos nem altera custos da viatura.</p></div>
<div class="form-group"><label for="part-notes">Notas</label><textarea class="form-control" id="part-notes" name="notes" maxlength="5000" rows="4">{{ old('notes',$part->notes) }}</textarea></div>
<button class="btn btn-primary">Guardar correção</button> <a href="{{ route('admin.dismantled-parts.index') }}">Cancelar</a>
</form></div></div></div>
@endsection

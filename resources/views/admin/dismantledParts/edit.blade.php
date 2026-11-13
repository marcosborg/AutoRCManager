@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Corrigir peça #{{ $part->id }}</div><div class="panel-body">
<p>Origem: ficha #{{ $part->vehicle_id }} · {{ $part->vehicle?->license ?: $part->vehicle?->foreign_license }}. A origem e o autor do registo são preservados.</p>
@foreach($errors->all() as $error)<p class="text-danger">{{ $error }}</p>@endforeach
<form method="POST" action="{{ route('admin.dismantled-parts.update',$part) }}">@csrf @method('PATCH')
<input type="hidden" name="revision" value="{{ $part->revisionToken() }}">
@foreach(['reference'=>'Referência','description'=>'Descrição','location'=>'Localização'] as $field=>$label)<div class="form-group"><label>{{ $label }}</label><input class="form-control" name="{{ $field }}" maxlength="255" {{ $field==='reference'?'':'required' }} value="{{ old($field,$part->$field) }}"></div>@endforeach
<div class="form-group"><label>Condição</label><select class="form-control" name="condition">@foreach(App\Models\DismantledPart::CONDITIONS as $key=>$label)<option value="{{ $key }}" {{ old('condition',$part->condition)===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
<div class="form-group"><label>Valor (€), facultativo</label><input class="form-control" name="value" type="number" min="0" step="0.01" value="{{ old('value',$part->value) }}"></div>
<div class="form-group"><label>Notas</label><textarea class="form-control" name="notes" maxlength="5000">{{ old('notes',$part->notes) }}</textarea></div>
<button class="btn btn-primary">Guardar correção</button> <a href="{{ route('admin.dismantled-parts.index') }}">Cancelar</a>
</form></div></div></div>
@endsection

@extends('layouts.admin')
@section('content')
<form method="POST" action="{{ route('admin.painting-jobs.update', $job) }}">@csrf @method('PUT')
<div class="card"><div class="card-header">Editar ficha de pintura #{{ $job->id }}</div><div class="card-body">
    <div class="row"><div class="col-md-3"><strong>Cliente/contacto</strong><p>{{ $job->client_contact ?: '—' }}</p></div><div class="col-md-3"><strong>Marca/modelo</strong><p>{{ $job->brand_model ?: '—' }}</p></div><div class="col-md-2"><strong>Matrícula</strong><p>{{ $job->license ?: '—' }}</p></div>
        <div class="col-md-2 form-group"><label>Entrada</label><input class="form-control" type="date" name="entry_date" value="{{ old('entry_date', optional($job->entry_date)->format('Y-m-d')) }}" required></div><div class="col-md-2 form-group"><label>Saída</label><input class="form-control" type="date" name="exit_date" value="{{ old('exit_date', optional($job->exit_date)->format('Y-m-d')) }}"></div></div>
    <div class="form-group"><label>Pintor / operador</label><select class="form-control" name="painter_id" required>@foreach($painters as $id => $name)<option value="{{ $id }}" {{ (string) old('painter_id', $job->painter_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
</div></div>

<div class="card"><div class="card-header">Danos a tratar</div><div class="card-body"><div class="row">
@php($damageMap = $job->damages->pluck('intensity', 'zone'))
@foreach($zones as $zone => $label)<div class="col-md-4 form-group"><label>{{ $label }}</label><select class="form-control" name="damages[{{ $zone }}]"><option value="">Sem dano</option>@foreach($intensities as $key => $text)<option value="{{ $key }}" {{ old("damages.$zone", $damageMap[$zone] ?? '') === $key ? 'selected' : '' }}>{{ $text }}</option>@endforeach</select></div>@endforeach
</div></div></div>

<div class="card"><div class="card-header">Materiais <button type="button" id="add-material" class="btn btn-xs btn-default pull-right">Adicionar linha</button></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered" id="materials-table"><thead><tr><th>Material</th><th>Referência</th><th>Quantidade</th><th>Data</th><th>Horas</th><th></th></tr></thead><tbody>
@foreach($job->materials as $i => $material)<tr><td><input class="form-control" name="materials[{{ $i }}][material_type]" value="{{ old("materials.$i.material_type", $material->material_type) }}" required></td><td><input class="form-control" name="materials[{{ $i }}][reference]" value="{{ old("materials.$i.reference", $material->reference) }}"></td><td><input class="form-control" type="number" step="0.01" min="0" name="materials[{{ $i }}][quantity]" value="{{ old("materials.$i.quantity", $material->quantity) }}"></td><td><input class="form-control" type="date" name="materials[{{ $i }}][used_date]" value="{{ old("materials.$i.used_date", optional($material->used_date)->format('Y-m-d')) }}"></td><td><input class="form-control" type="number" step="0.01" min="0" name="materials[{{ $i }}][hours]" value="{{ old("materials.$i.hours", $material->hours) }}"></td><td><button type="button" class="btn btn-xs btn-danger remove-material">×</button></td></tr>@endforeach
</tbody></table></div></div></div>

<div class="card"><div class="card-header">Outros trabalhos</div><div class="card-body"><div class="row">
@foreach(['optics'=>'Óticas','black_parts'=>'Pretos','wheels'=>'Jantes','other_work'=>'Outros'] as $field => $label)<div class="col-md-6 form-group"><label>{{ $label }}</label><textarea class="form-control" name="{{ $field }}">{{ old($field, $job->$field) }}</textarea></div>@endforeach
</div><div class="form-group"><label>Observações</label><textarea class="form-control" name="notes">{{ old('notes', $job->notes) }}</textarea></div></div></div>
<button class="btn btn-success">Guardar</button> <a class="btn btn-default" href="{{ route('admin.painting-jobs.show', $job) }}">Cancelar</a>
</form>
@endsection
@section('scripts')
@parent
<script>document.addEventListener('DOMContentLoaded',function(){var table=document.querySelector('#materials-table tbody');document.getElementById('add-material').addEventListener('click',function(){var i=table.children.length,row=document.createElement('tr');row.innerHTML='<td><input class="form-control" name="materials['+i+'][material_type]" required></td><td><input class="form-control" name="materials['+i+'][reference]"></td><td><input class="form-control" type="number" step="0.01" min="0" name="materials['+i+'][quantity]"></td><td><input class="form-control" type="date" name="materials['+i+'][used_date]"></td><td><input class="form-control" type="number" step="0.01" min="0" name="materials['+i+'][hours]"></td><td><button type="button" class="btn btn-xs btn-danger remove-material">×</button></td>';table.appendChild(row)});table.addEventListener('click',function(e){if(e.target.classList.contains('remove-material'))e.target.closest('tr').remove()})});</script>
@endsection

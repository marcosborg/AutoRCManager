@extends('layouts.admin')
@section('content')
<div class="card"><div class="card-header">Nova ficha de pintura</div><div class="card-body">
    <form method="POST" action="{{ route('admin.painting-jobs.store') }}">@csrf
        <div class="row">
            <div class="col-md-5 form-group"><label>Viatura *</label><select class="form-control select2" name="vehicle_id" required><option value="">Selecionar</option>@foreach($vehicles as $id => $label)<option value="{{ $id }}" {{ old('vehicle_id') == $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 form-group"><label>Pintor *</label><select class="form-control" name="painter_id" required><option value="">Selecionar</option>@foreach($painters as $id => $name)<option value="{{ $id }}" {{ old('painter_id') == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Data de entrada *</label><input class="form-control" type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required></div>
        </div>
        @if($painters->isEmpty())<div class="alert alert-warning">Crie ou atribua primeiro o role <strong>Pintor</strong> a um utilizador.</div>@endif
        <div class="form-group"><label>Observações</label><textarea class="form-control" name="notes">{{ old('notes') }}</textarea></div>
        <button class="btn btn-success" {{ $painters->isEmpty() ? 'disabled' : '' }}>Criar ficha</button> <a class="btn btn-default" href="{{ route('admin.painting-jobs.index') }}">Cancelar</a>
    </form>
</div></div>
@endsection

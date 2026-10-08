@extends('layouts.admin')
@section('content')
<div class="content"><div class="row"><div class="col-lg-10 col-lg-offset-1">
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Notas internas da viatura</strong></div>
        <div class="panel-body">
            <h3>{{ $vehicle->license ?: $vehicle->foreign_license ?: 'Viatura #'.$vehicle->id }} <small>{{ $vehicle->model }}</small></h3>
            <p>Recados e informações partilhados pela equipa sobre esta viatura.</p>
            <p><a class="btn btn-default" href="{{ Gate::allows('vehicle_edit') ? route('admin.vehicles.edit', $vehicle) : route('admin.vehicles.show', $vehicle) }}">Voltar à ficha da viatura</a></p>
            @can('vehicle_edit')
                <form method="POST" action="{{ route('admin.vehicles.notes.store', $vehicle) }}">
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) Illuminate\Support\Str::uuid()) }}">
                    <div class="form-group {{ $errors->any() ? 'has-error' : '' }}">
                        <label for="vehicle-note-body">Nova nota interna</label>
                        <textarea id="vehicle-note-body" class="form-control" name="body" rows="3" maxlength="5000" required placeholder="Escreva uma informação ou recado para a equipa…">{{ old('body') }}</textarea>
                        @foreach($errors->all() as $error)<p class="help-block">{{ $error }}</p>@endforeach
                    </div>
                    <button class="btn btn-primary" type="submit">Adicionar nota</button>
                </form>
            @endcan
        </div>
    </div>
    <h4>Conversa da viatura <small>Mais recentes primeiro · {{ $notes->total() }} {{ $notes->total() === 1 ? 'nota' : 'notas' }}</small></h4>
    @forelse($notes as $note)
        <article class="panel panel-default">
            <div class="panel-heading"><strong>{{ $note->author_name }}</strong> <span class="text-muted">· {{ $note->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}</span></div>
            <div class="panel-body" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $note->body }}</div>
        </article>
    @empty
        <div class="alert alert-info">Ainda não existem notas internas para esta viatura.</div>
    @endforelse
    {{ $notes->links() }}
</div></div></div>
@endsection

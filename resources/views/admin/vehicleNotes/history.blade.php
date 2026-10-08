@extends('layouts.admin')
@section('content')
<div class="content"><div class="row"><div class="col-lg-10 col-lg-offset-1">
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Nota interna · {{ $vehicle->license ?: $vehicle->foreign_license ?: 'Viatura #'.$vehicle->id }}</strong></div>
        <div class="panel-body">
            <p><a href="{{ route('admin.vehicles.notes.index', $vehicle) }}">Voltar à conversa da viatura</a></p>
            <p>Escrita por <strong>{{ $note->author_name }}</strong> em {{ $note->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}.</p>
            @can('vehicle_edit')
                <form method="POST" action="{{ route('admin.vehicles.notes.update', [$vehicle, $note]) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="revision" value="{{ old('revision', $note->revisionToken()) }}">
                    <div class="form-group {{ $errors->any() ? 'has-error' : '' }}">
                        <label for="note-correction">Corrigir nota</label>
                        <textarea id="note-correction" class="form-control" name="body" rows="4" maxlength="5000" required>{{ old('body', $note->body) }}</textarea>
                        @foreach($errors->all() as $error)<p class="help-block">{{ $error }}</p>@endforeach
                    </div>
                    <p class="help-block">O texto anterior e quem fez a correção ficam guardados no histórico.</p>
                    <button class="btn btn-primary" type="submit">Guardar correção</button>
                </form>
            @else
                <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $note->body }}</div>
            @endcan
        </div>
    </div>
    <h4>Histórico de correções</h4>
    @forelse($revisions as $revision)
        <article class="panel panel-default">
            <div class="panel-heading"><strong>{{ $revision->properties['editor_name'] }}</strong> · {{ $revision->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}</div>
            <div class="panel-body"><strong>Texto anterior</strong><p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $revision->properties['before'] }}</p><hr><strong>Texto após a correção</strong><p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $revision->properties['after'] }}</p></div>
        </article>
    @empty
        <p class="text-muted">Esta nota ainda não foi corrigida.</p>
    @endforelse
    {{ $revisions->links() }}
</div></div></div>
@endsection

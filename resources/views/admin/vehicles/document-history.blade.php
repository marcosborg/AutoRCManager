@extends('layouts.admin')
@section('content')
<div class="content">
    <p><a href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-documents">← Voltar aos documentos da viatura</a></p>
    <div class="panel panel-default">
        <div class="panel-heading"><h1 class="panel-title">Histórico do anexo</h1></div>
        <div class="panel-body">
            <h4>{{ $vehicle->license ?: $vehicle->foreign_license }} · {{ $vehicle->model }}</h4>
            <p><a href="{{ $file->getUrl() }}" target="_blank" rel="noopener noreferrer">Abrir anexo: {{ $file->file_name }}</a></p>
            <p>Tipo: <strong>{{ \App\Support\VehicleDocuments::TYPES[\App\Support\VehicleDocuments::type($file)] }}</strong></p>
            @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            @if(\App\Support\VehicleDocuments::annulled($file))
                <div class="alert alert-warning"><strong>Anulado</strong> — {{ $file->getCustomProperty('document_annulment.reason') }}<br>Este ficheiro foi preservado para consulta, mas já não serve como cópia válida para concluir um acompanhamento.</div>
            @else
                @can('vehicle_edit')
                <details><summary>Anular este anexo</summary>
                    <p>Use quando o ficheiro está errado ou duplicado. O ficheiro e o histórico ficam guardados. Se justificava um acompanhamento concluído, esse acompanhamento fica por rever.</p>
                    <form method="POST" action="{{ route('admin.vehicles.documents.annul', [$vehicle, $file->id]) }}">
                        @csrf
                        <input type="hidden" name="revision" value="{{ \App\Support\VehicleDocuments::revision($file) }}">
                        <div class="form-group"><label for="reason">Motivo da anulação</label><textarea id="reason" name="reason" class="form-control" required maxlength="500" rows="2">{{ old('reason') }}</textarea></div>
                        <button class="btn btn-warning">Anular anexo e guardar motivo</button>
                    </form>
                </details>
                @endcan
            @endif
            <hr><h4>Alterações registadas</h4>
            @forelse($history as $entry)
                <div class="well well-sm">
                    <strong>{{ $entry->created_at->format('d/m/Y H:i') }} · {{ $entry->properties['user_name'] ?? $authors[$entry->user_id] ?? 'Utilizador indisponível' }}</strong><br>
                    {{ \App\Support\VehicleDocuments::TYPES[$entry->properties['before'] ?? 'unknown'] ?? 'Por identificar' }} → {{ ($entry->properties['after'] ?? '') === 'annulled' ? 'Anulado' : (\App\Support\VehicleDocuments::TYPES[$entry->properties['after'] ?? 'unknown'] ?? 'Por identificar') }}<br>
                    Motivo: {{ $entry->properties['reason'] ?? 'Não registado na versão anterior' }}
                </div>
            @empty
                <p>Ainda não há correções nem anulações registadas para este anexo.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

<div class="well well-sm" style="margin-top:8px">
    <p>Tipo atual: <strong>{{ \App\Support\VehicleDocuments::TYPES[\App\Support\VehicleDocuments::type($file)] }}</strong>
        · <a href="{{ route('admin.vehicles.documents.history', [$vehicle, $file->id]) }}" target="_blank" rel="noopener noreferrer">Ver alterações do anexo</a></p>
    @if(\App\Support\VehicleDocuments::annulled($file))
        <p>Anulado: {{ $file->getCustomProperty('document_annulment.reason') }}. O ficheiro continua disponível para consulta.</p>
    @else
        @can('vehicle_edit')
        <details><summary>Corrigir tipo do anexo</summary>
            <form method="POST" action="{{ route('admin.vehicles.documents.type', [$vehicle, $file->id]) }}" style="margin-top:10px">
                @csrf @method('PATCH')
                <input type="hidden" name="return_document" value="{{ $document }}">
                <input type="hidden" name="revision" value="{{ \App\Support\VehicleDocuments::revision($file) }}">
                <div class="form-group"><label for="history-type-{{ $entryIndex }}">Tipo correto</label>
                    <select id="history-type-{{ $entryIndex }}" name="type" class="form-control">
                        @foreach(\App\Support\VehicleDocuments::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(\App\Support\VehicleDocuments::type($file) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label for="history-correction-{{ $entryIndex }}">Motivo da correção</label><input id="history-correction-{{ $entryIndex }}" name="reason" class="form-control" required maxlength="500"></div>
                <button class="btn btn-primary">Guardar correção</button>
            </form>
        </details>
        <details style="margin-top:10px"><summary>Anular anexo</summary>
            <p>Use se o ficheiro está errado ou duplicado. O ficheiro e o histórico ficam guardados. Se era a cópia que concluía um acompanhamento, esse acompanhamento fica por rever.</p>
            <form method="POST" action="{{ route('admin.vehicles.documents.annul', [$vehicle, $file->id]) }}">
                @csrf
                <input type="hidden" name="return_document" value="{{ $document }}">
                <input type="hidden" name="revision" value="{{ \App\Support\VehicleDocuments::revision($file) }}">
                <div class="form-group"><label for="history-annul-{{ $entryIndex }}">Motivo da anulação</label><input id="history-annul-{{ $entryIndex }}" name="reason" class="form-control" required maxlength="500"></div>
                <button class="btn btn-danger">Anular anexo e guardar motivo</button>
            </form>
        </details>
        @endcan
    @endif
</div>

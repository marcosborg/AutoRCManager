<div id="document-classification" class="panel panel-default">
    <div class="panel-heading"><strong>Identificar os ficheiros guardados</strong></div>
    <div class="panel-body">
        <p>Abra o ficheiro, escolha o que contém e carregue em <strong>Guardar tipo</strong> ou no botão geral <strong>Gravar</strong> da ficha. Pode corrigir a escolha ou voltar a “Por identificar”. Os ficheiros ficam no mesmo grupo.</p>
        <p class="help-block">Os novos anexos aparecem nesta lista depois de gravar a ficha. Identificar um ficheiro não marca automaticamente o documento como recebido.</p>
        @if(\App\Support\VehicleDocuments::files($vehicle)->isEmpty())
            <p>Ainda não existem ficheiros guardados nesta área.</p>
        @else
            <div class="table-responsive">
                <table class="table table-condensed">
                    <thead><tr><th scope="col">Ficheiro / grupo atual</th><th scope="col">Tipo de documento</th><th scope="col">Ação</th></tr></thead>
                    <tbody>
                        @foreach(\App\Support\VehicleDocuments::files($vehicle) as $file)
                            <tr data-document-file="{{ $file->id }}" data-saved-type="{{ \App\Support\VehicleDocuments::type($file) }}" data-revision="{{ \App\Support\VehicleDocuments::revision($file) }}">
                                <td style="overflow-wrap: anywhere;">
                                    <a href="{{ $file->getUrl() }}" target="_blank" rel="noopener noreferrer">Abrir ficheiro: {{ $file->file_name }}</a>
                                    <small class="help-block">{{ ['documents' => 'DUA / Inspeção', 'additional_documents' => 'Outros documentos', 'dav' => 'DAV'][$file->collection_name] }}</small>
                                </td>
                                <td>
                                    <label class="sr-only" for="document-type-{{ $file->id }}">Tipo de {{ $file->file_name }}</label>
                                    <select id="document-type-{{ $file->id }}" class="form-control js-document-type" style="min-width: 190px;">
                                        @foreach(\App\Support\VehicleDocuments::TYPES as $value => $label)
                                            <option value="{{ $value }}" {{ \App\Support\VehicleDocuments::type($file) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-default js-save-document-type" data-url="{{ route('admin.vehicles.documents.type', [$vehicle, $file->id]) }}">Guardar tipo</button>
                                    <span class="js-document-type-status help-block" role="status" aria-live="polite"></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

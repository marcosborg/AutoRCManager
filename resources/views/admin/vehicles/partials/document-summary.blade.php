@php
    $documentChecks = \App\Support\VehicleDocuments::checks();
    $unidentifiedGroups = [
        'DAV' => $vehicle->getMedia('dav')->filter(fn ($file) => !\App\Support\VehicleDocuments::annulled($file) && \App\Support\VehicleDocuments::type($file) === 'unknown')->count(),
        'DUA / Inspeção' => $vehicle->getMedia('documents')->filter(fn ($file) => !\App\Support\VehicleDocuments::annulled($file) && \App\Support\VehicleDocuments::type($file) === 'unknown')->count(),
        'Outros documentos' => $vehicle->getMedia('additional_documents')->filter(fn ($file) => !\App\Support\VehicleDocuments::annulled($file) && \App\Support\VehicleDocuments::type($file) === 'unknown')->count(),
    ];
    $davCount = \App\Support\VehicleDocuments::count($vehicle, 'dav');
@endphp
<section class="panel panel-default" aria-labelledby="document-summary-title" id="document-summary">
    <div class="panel-heading"><h5 id="document-summary-title" style="margin: 0;">Resumo da documentação guardada</h5></div>
    <div class="panel-body">
        <p>Uma confirmação na ficha pode corresponder a um documento em papel. Não significa que exista uma cópia digital. Este resumo é atualizado depois de gravar.</p>
        <p>Use “Não se aplica” apenas quando confirmou que o documento não é necessário nesta viatura. Se chegar mais tarde, mantenha-o por conferir. Uma pró-forma não comprova a fatura definitiva.</p>
        <p class="js-applicability-status" role="status" aria-live="polite"></p>
        <div class="table-responsive">
            <table class="table table-condensed" style="margin-bottom: 0;">
                <thead><tr><th scope="col">Documento</th><th scope="col">Confirmação na ficha</th><th scope="col">Cópia digital</th><th scope="col">Aplicabilidade</th></tr></thead>
                <tbody>
                    @foreach($documentChecks as $field => $label)
                        <tr data-document="{{ $field }}">
                            <th scope="row">{{ $label }}</th>
                            <td>{{ (int) $vehicle->{$field} === 1 ? 'Recebido segundo a ficha' : 'Por confirmar' }}</td>
                            <td>{{ ($count = \App\Support\VehicleDocuments::count($vehicle, $field === 'vehicle_ownership_title' ? 'dua' : $field)) ? 'Ficheiro identificado: '.$count : 'Sem ficheiro identificado' }}</td>
                            @include('admin.vehicles.partials.document-applicability', ['document' => $field])
                        </tr>
                    @endforeach
                    <tr data-document="inspection"><th scope="row">Inspeção</th><td>Por confirmar — sem confirmação própria na ficha</td><td>{{ ($count = \App\Support\VehicleDocuments::count($vehicle, 'inspection')) ? 'Ficheiro identificado: '.$count : 'Sem ficheiro identificado' }}</td>@include('admin.vehicles.partials.document-applicability', ['document' => 'inspection'])</tr>
                    <tr data-document="dav">
                        <th scope="row">DAV</th>
                        <td>Sem confirmação própria na ficha</td>
                        <td>{{ $davCount ? 'Ficheiro identificado: '.$davCount : 'Por confirmar — sem ficheiro identificado' }}</td>
                        @include('admin.vehicles.partials.document-applicability', ['document' => 'dav'])
                    </tr>
                    <tr data-document="purchase_invoice"><th scope="row">Fatura de compra</th><td>Sem confirmação própria na ficha</td>
                        <td>{{ ($count = \App\Support\VehicleDocuments::count($vehicle, 'purchase_invoice')) ? 'Ficheiro identificado: '.$count : 'Sem ficheiro identificado' }}</td>
                        @include('admin.vehicles.partials.document-applicability', ['document' => 'purchase_invoice'])
                    </tr>
                    @if($vehicle->documentFollowups->contains('document', 'signed_purchase_form') || \App\Support\VehicleDocuments::count($vehicle, 'signed_purchase_form'))
                    <tr data-document="signed_purchase_form"><th scope="row">Impresso de compra assinado</th><td>Conferir assinatura na cópia digital</td>
                        <td>{{ ($count = \App\Support\VehicleDocuments::count($vehicle, 'signed_purchase_form')) ? 'Ficheiro identificado: '.$count : 'Sem ficheiro identificado' }}</td>
                        @include('admin.vehicles.partials.document-applicability', ['document' => 'signed_purchase_form'])
                    </tr>
                    @endif
                    @if($count = \App\Support\VehicleDocuments::count($vehicle, 'other'))
                        <tr><th scope="row">Outro documento</th><td>Sem confirmação própria na ficha</td><td>Ficheiro identificado: {{ $count }}</td><td>Conferir o conteúdo</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <p class="help-block">“Ficheiro identificado” indica o tipo escolhido ou o grupo DAV, quando ainda não foi classificado; o conteúdo e a validade devem ser conferidos ao abrir o ficheiro.</p>
        @if(array_sum($unidentifiedGroups) > 0)
            <div class="alert alert-info" style="margin-bottom: 0;">
                <strong>Ficheiros disponíveis por identificar</strong>
                <ul>
                    @foreach($unidentifiedGroups as $group => $count)
                        @if($count > 0)<li>{{ $group }}: {{ $count }}</li>@endif
                    @endforeach
                </ul>
                Por confirmar: estes anexos estão disponíveis nos grupos abaixo. Um ficheiro em “DUA / Inspeção” não confirma que existam os dois documentos. Pode indicar o tipo em “Identificar os ficheiros guardados”, abaixo. O nome do ficheiro não determina o tipo.
            </div>
        @endif
    </div>
</section>

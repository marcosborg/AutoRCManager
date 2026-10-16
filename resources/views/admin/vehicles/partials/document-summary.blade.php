@php
    $documentChecks = [
        'purchase_and_sale_agreement' => trans('cruds.vehicle.fields.purchase_and_sale_agreement'),
        'copy_of_the_citizen_card' => trans('cruds.vehicle.fields.copy_of_the_citizen_card'),
        'tax_identification_card' => trans('cruds.vehicle.fields.tax_identification_card'),
        'copy_of_the_stamp_duty_receipt' => trans('cruds.vehicle.fields.copy_of_the_stamp_duty_receipt'),
        'vehicle_ownership_title' => 'DUA',
        'release_of_reservation_or_mortgage' => 'Extinção de reserva / hipoteca',
        'leasing_agreement' => trans('cruds.vehicle.fields.leasing_agreement'),
    ];
    $unidentifiedGroups = [
        'DUA / Inspeção' => $vehicle->getMedia('documents')->count(),
        'Outros documentos' => $vehicle->getMedia('additional_documents')->count(),
    ];
    $davCount = $vehicle->getMedia('dav')->count();
@endphp
<section class="panel panel-default" aria-labelledby="document-summary-title" id="document-summary">
    <div class="panel-heading"><h5 id="document-summary-title" style="margin: 0;">Resumo da documentação guardada</h5></div>
    <div class="panel-body">
        <p>Uma confirmação na ficha pode corresponder a um documento em papel. Não significa que exista uma cópia digital. Este resumo é atualizado depois de gravar.</p>
        <div class="table-responsive">
            <table class="table table-condensed" style="margin-bottom: 0;">
                <thead><tr><th scope="col">Documento</th><th scope="col">Confirmação na ficha</th><th scope="col">Cópia digital</th></tr></thead>
                <tbody>
                    @foreach($documentChecks as $field => $label)
                        <tr data-document="{{ $field }}">
                            <th scope="row">{{ $label }}</th>
                            <td>{{ (int) $vehicle->{$field} === 1 ? 'Recebido segundo a ficha' : 'Por confirmar' }}</td>
                            <td>Sem ficheiro identificado</td>
                        </tr>
                    @endforeach
                    <tr data-document="inspection"><th scope="row">Inspeção</th><td>Por confirmar — sem confirmação própria na ficha</td><td>Sem ficheiro identificado</td></tr>
                    <tr data-document="dav">
                        <th scope="row">DAV</th>
                        <td>Sem confirmação própria na ficha</td>
                        <td>{{ $davCount ? 'Ficheiro identificado: '.$davCount.' no grupo DAV' : 'Por confirmar — sem ficheiro identificado' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="help-block">“Ficheiro identificado” indica o grupo onde foi guardado; o conteúdo e a validade devem ser conferidos ao abrir o ficheiro.</p>
        @if(array_sum($unidentifiedGroups) > 0)
            <div class="alert alert-info" style="margin-bottom: 0;">
                <strong>Ficheiros disponíveis por identificar</strong>
                <ul>
                    @foreach($unidentifiedGroups as $group => $count)
                        @if($count > 0)<li>{{ $group }}: {{ $count }}</li>@endif
                    @endforeach
                </ul>
                Por confirmar: estes anexos estão disponíveis nos grupos abaixo. Um ficheiro em “DUA / Inspeção” não confirma que existam os dois documentos. Os nomes dos ficheiros não foram usados para identificar o tipo.
            </div>
        @endif
    </div>
</section>

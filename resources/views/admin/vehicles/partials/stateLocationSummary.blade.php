<section class="panel panel-default" aria-label="Situação e localização da viatura">
    <div class="panel-heading"><strong>Situação e localização</strong></div>
    <div class="panel-body">
        <p><strong>Empresa de destino:</strong> <span id="vehicle-summary-destination-company">{{ $vehicleContext['destination_company'] }}</span></p>
        <p><strong>Fornecedor (a quem foi comprada):</strong> {{ $vehicleContext['supplier'] }}</p>
        <div class="row">
            <div class="col-sm-4"><dl><dt>Estado geral</dt><dd>{{ $vehicleContext['general_state'] }}</dd></dl></div>
            <div class="col-sm-4"><dl><dt>Estado na oficina</dt><dd>{{ $vehicleContext['workshop_state'] }}</dd></dl></div>
            <div class="col-sm-4"><dl><dt>Unidade atual registada</dt><dd>{{ $vehicleContext['location'] }}</dd></dl></div>
        </div>
        @if($vehicleContext['storage_location'] !== '')
            <p><strong>Local de armazenamento indicado:</strong> {{ $vehicleContext['storage_location'] }}</p>
        @endif
        @if($vehicleContext['consignment_destination'])
            <p><strong>Destino da consignação em curso:</strong> {{ $vehicleContext['consignment_destination'] }}</p>
        @endif
        @if($vehicleContext['location_conflict'] || $vehicleContext['consignment_conflict'])
            <p class="text-warning" role="status">Há registos em simultâneo. Confirme a localização e as consignações desta viatura.</p>
        @endif
    </div>
</section>

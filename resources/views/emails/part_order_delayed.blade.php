@php
    $vehicle = $partOrder->vehicle;
    $vehicleLabel = $vehicle ? trim(implode(' ', array_filter([
        $vehicle->license ?: $vehicle->foreign_license,
        optional($vehicle->brand)->name,
        $vehicle->model,
    ]))) : 'Sem viatura';
@endphp

<p>A encomenda de pecas passou o prazo previsto e ainda nao esta marcada como recebida.</p>

<p>
    <strong>Encomenda:</strong> #{{ $partOrder->id }}<br>
    <strong>Viatura:</strong> {{ $vehicleLabel ?: 'Sem viatura' }}<br>
    <strong>Fornecedor:</strong> {{ optional($partOrder->suplier)->name ?: 'Por definir' }}<br>
    <strong>Estado:</strong> {{ \App\Models\PartOrder::STATUS_SELECT[$partOrder->status] ?? $partOrder->status }}<br>
    <strong>Data prevista:</strong> {{ optional($partOrder->expected_delivery_date)->format('Y-m-d') ?: '-' }}
</p>

<p><strong>Pecas pendentes:</strong></p>
<ul>
    @foreach($partOrder->items as $item)
        @if(! in_array($item->status, ['received', 'installed'], true))
            <li>{{ $item->quantity }} x {{ $item->description }} @if($item->reference)({{ $item->reference }})@endif</li>
        @endif
    @endforeach
</ul>

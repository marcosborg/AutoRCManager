@php
    $vehicle = $partOrder->vehicle;
    $vehicleLabel = $vehicle ? trim(implode(' ', array_filter([
        $vehicle->license ?: $vehicle->foreign_license,
        optional($vehicle->brand)->name,
        $vehicle->model,
    ]))) : 'Sem viatura';
@endphp

<p>Foi criada uma nova encomenda de pecas.</p>

<p>
    <strong>Encomenda:</strong> #{{ $partOrder->id }}<br>
    <strong>Viatura:</strong> {{ $vehicleLabel ?: 'Sem viatura' }}<br>
    <strong>Fornecedor:</strong> {{ optional($partOrder->suplier)->name ?: 'Por definir' }}<br>
    <strong>Pedido por:</strong> {{ optional($partOrder->requested_by)->name ?: '-' }}<br>
    <strong>Tecnico:</strong> {{ optional($partOrder->technician)->name ?: '-' }}<br>
    <strong>Prioridade:</strong> {{ \App\Models\PartOrder::PRIORITY_SELECT[$partOrder->priority] ?? $partOrder->priority }}<br>
    <strong>Data prevista:</strong> {{ optional($partOrder->expected_delivery_date)->format('Y-m-d') ?: '-' }}
</p>

<p><strong>Pecas:</strong></p>
<ul>
    @foreach($partOrder->items as $item)
        <li>{{ $item->quantity }} x {{ $item->description }} @if($item->reference)({{ $item->reference }})@endif</li>
    @endforeach
</ul>

@if($partOrder->notes)
    <p><strong>Notas:</strong><br>{{ $partOrder->notes }}</p>
@endif

@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Encomenda #{{ $partOrder->id }}</div>
        <div class="panel-body">
            <p><strong>Viatura:</strong> {{ trim(($partOrder->vehicle->license ?? $partOrder->vehicle->foreign_license ?? '') . ' ' . ($partOrder->vehicle->brand->name ?? '') . ' ' . ($partOrder->vehicle->model ?? '')) ?: '-' }}</p>
            <p><strong>Fornecedor:</strong> {{ $partOrder->suplier->name ?? '-' }}</p>
            <p><strong>Estado:</strong> @include('admin.partOrders.partials.badge', ['value' => $partOrder->status, 'label' => App\Models\PartOrder::STATUS_SELECT[$partOrder->status] ?? $partOrder->status])</p>
            <p><strong>Notas:</strong> {{ $partOrder->notes ?: '-' }}</p>
            <h4>Peças</h4>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Ref.</th><th>Descrição</th><th>Qtd</th><th>Estado</th><th>Total final</th></tr></thead>
                <tbody>@foreach($partOrder->items as $item)<tr><td>{{ $item->reference }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ App\Models\PartOrderItem::STATUS_SELECT[$item->status] ?? $item->status }}</td><td>{{ number_format((float) $item->total_final, 2, ',', '.') }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Pagamento #{{ $partPayment->id }}</div><div class="panel-body"><p><strong>Encomenda:</strong> #{{ $partPayment->part_order_id }}</p><p><strong>Fornecedor:</strong> {{ $partPayment->suplier->name ?? '-' }}</p><p><strong>Valor:</strong> {{ number_format((float) $partPayment->amount, 2, ',', '.') }}</p><p><strong>Estado:</strong> {{ App\Models\PartPayment::STATUS_SELECT[$partPayment->payment_status] ?? $partPayment->payment_status }}</p><p><strong>Notas:</strong> {{ $partPayment->notes ?: '-' }}</p></div></div></div>
@endsection

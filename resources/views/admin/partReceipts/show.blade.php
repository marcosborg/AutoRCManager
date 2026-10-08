@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Receção #{{ $partReceipt->id }}</div><div class="panel-body"><p><strong>Encomenda:</strong> #{{ $partReceipt->part_order_id }}</p><p><strong>Recebido em:</strong> {{ optional($partReceipt->received_at)->format('Y-m-d H:i') }}</p><p><strong>Local:</strong> {{ $partReceipt->received_location ?: '-' }}</p><p><strong>Recebido por:</strong> {{ $partReceipt->received_by->name ?? '-' }}</p><p><strong>Observações:</strong> {{ $partReceipt->observations ?: '-' }}</p>@if($partReceipt->received_items === null)<p>Receção antiga, sem quantidades discriminadas.</p>@else
<table class="table table-bordered"><thead><tr><th>Peça</th><th>Quantidade nesta entrega</th></tr></thead><tbody>@foreach($partReceipt->received_items as $line)<tr><td>{{ $line['description'] }}</td><td>{{ $line['quantity'] }}</td></tr>@endforeach</tbody></table>@endif
@foreach($partReceipt->attachments as $media)<a class="btn btn-xs btn-default" target="_blank" href="{{ $media->getUrl() }}">{{ $media->file_name }}</a> @endforeach</div></div></div>
@endsection

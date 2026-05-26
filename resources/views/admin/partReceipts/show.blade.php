@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Receção #{{ $partReceipt->id }}</div><div class="panel-body"><p><strong>Encomenda:</strong> #{{ $partReceipt->part_order_id }}</p><p><strong>Recebido em:</strong> {{ optional($partReceipt->received_at)->format('Y-m-d H:i') }}</p><p><strong>Local:</strong> {{ $partReceipt->received_location ?: '-' }}</p><p><strong>Recebido por:</strong> {{ $partReceipt->received_by->name ?? '-' }}</p><p><strong>Observações:</strong> {{ $partReceipt->observations ?: '-' }}</p>@foreach($partReceipt->attachments as $media)<a class="btn btn-xs btn-default" target="_blank" href="{{ $media->getUrl() }}">{{ $media->file_name }}</a> @endforeach</div></div></div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Criar receção de peças</div><div class="panel-body">
@if($receiptOrder)
<a href="{{ route('admin.part-receipts.create') }}">Escolher outra encomenda</a>
<form method="POST" action="{{ route('admin.part-receipts.store') }}" enctype="multipart/form-data">@csrf @include('admin.partReceipts.partials.form')</form>
@else
<form method="GET" action="{{ route('admin.part-receipts.create') }}">
<label for="receipt-order">Encomenda</label><select id="receipt-order" class="form-control select2" name="part_order_id" required><option value="">Selecionar</option>@foreach($partOrders as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select>
<button class="btn btn-primary" type="submit">Escolher peças a receber</button>
</form>
@endif
</div></div></div>
@endsection

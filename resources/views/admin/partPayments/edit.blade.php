@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Editar pagamento #{{ $partPayment->id }}</div><div class="panel-body"><form method="POST" action="{{ route('admin.part-payments.update', $partPayment) }}">@csrf @method('PUT') @include('admin.partPayments.partials.form')</form></div></div></div>
@endsection

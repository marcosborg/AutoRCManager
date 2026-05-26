@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Criar encomenda de peças</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.part-orders.store') }}">
                @csrf
                @include('admin.partOrders.partials.form')
            </form>
        </div>
    </div>
</div>
@endsection

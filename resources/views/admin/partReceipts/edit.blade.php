@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Editar receção #{{ $partReceipt->id }}</div><div class="panel-body"><form method="POST" action="{{ route('admin.part-receipts.update', $partReceipt) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.partReceipts.partials.form')</form></div></div></div>
@endsection

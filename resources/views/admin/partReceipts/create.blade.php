@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Criar receção de peças</div><div class="panel-body"><form method="POST" action="{{ route('admin.part-receipts.store') }}" enctype="multipart/form-data">@csrf @include('admin.partReceipts.partials.form')</form></div></div></div>
@endsection

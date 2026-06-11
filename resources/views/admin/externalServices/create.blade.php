@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Criar serviço externo</div><div class="panel-body"><form method="POST" action="{{ route('admin.external-services.store') }}" enctype="multipart/form-data">@csrf @include('admin.externalServices.partials.form')</form></div></div></div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Editar serviço externo #{{ $externalService->id }}</div><div class="panel-body"><form method="POST" action="{{ route('admin.external-services.update', $externalService) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.externalServices.partials.form')</form></div></div></div>
@endsection

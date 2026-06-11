@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Editar trabalho planeado</div><div class="panel-body"><form method="POST" action="{{ route('admin.workshop-interventions.update', $intervention) }}">@csrf @method('PUT') @include('admin.workshopInterventions.partials.form')</form></div></div></div>
@endsection

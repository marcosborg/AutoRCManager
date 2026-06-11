@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Planificar trabalho</div><div class="panel-body"><form method="POST" action="{{ route('admin.workshop-interventions.store') }}">@csrf @include('admin.workshopInterventions.partials.form')</form></div></div></div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            Criar lote
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.vehicle-groups.store') }}">
                @csrf
                @include('admin.vehicleGroups.partials.form', ['vehicleGroup' => null])
            </form>
        </div>
    </div>
</div>
@endsection

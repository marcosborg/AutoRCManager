@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            Editar lote
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.vehicle-groups.update', [$vehicleGroup->id]) }}">
                @method('PUT')
                @csrf
                @include('admin.vehicleGroups.partials.form', ['vehicleGroup' => $vehicleGroup])
            </form>
        </div>
    </div>
</div>
@endsection

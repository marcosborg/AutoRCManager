@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading"><h1 class="panel-title">Oficina</h1></div><div class="panel-body">
    <p>Acesso às viaturas e ao trabalho da oficina.</p>
    @can('vehicle_access')<a class="btn btn-default" href="{{ route('admin.vehicles.index') }}">Viaturas</a>@endcan
    @can('repair_access')<a class="btn btn-primary" href="{{ route('admin.repairs.index') }}">Reparações</a>@endcan
    @can('part_order_access')<a class="btn btn-default" href="{{ route('admin.part-orders.index') }}">Encomendas de peças</a>@endcan
    @can('external_service_access')<a class="btn btn-default" href="{{ route('admin.external-services.index') }}">Serviços externos</a>@endcan
    <a class="btn btn-default" href="{{ route('admin.systemCalendar') }}">Calendário</a>
</div></div></div>
@endsection

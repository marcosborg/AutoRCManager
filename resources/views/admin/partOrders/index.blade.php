@extends('layouts.admin')
@section('content')
<div class="content">
    @can('part_order_create')
        <p><a class="btn btn-success" href="{{ route('admin.part-orders.create') }}">Nova encomenda de peças</a></p>
    @endcan
    <div class="panel panel-default">
        <div class="panel-heading">Encomendas de Peças</div>
        <div class="panel-body">
            <form method="GET" class="row" style="margin-bottom:15px;">
                <div class="col-md-2"><select class="form-control" name="status"><option value="">Estado</option>@foreach(App\Models\PartOrder::STATUS_SELECT as $key => $label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-control" name="priority"><option value="">Prioridade</option>@foreach(App\Models\PartOrder::PRIORITY_SELECT as $key => $label)<option value="{{ $key }}" {{ request('priority') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><select class="form-control select2" name="suplier_id"><option value="">Fornecedor</option>@foreach($supliers as $id => $label)<option value="{{ $id }}" {{ (string) request('suplier_id') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-3"><input class="form-control" name="vehicle_search" value="{{ request('vehicle_search') }}" placeholder="Matricula/modelo"></div>
                <div class="col-md-1"><label><input type="checkbox" name="delayed" value="1" {{ request('delayed') ? 'checked' : '' }}> Atrasadas</label></div>
                <div class="col-md-1"><button class="btn btn-default" type="submit">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>ID</th><th>Viatura</th><th>Fornecedor</th><th>Estado</th><th>Prioridade</th><th>Prevista</th><th>Peças</th><th>&nbsp;</th></tr></thead>
                    <tbody>
                    @forelse($partOrders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ trim(($order->vehicle->license ?? $order->vehicle->foreign_license ?? '') . ' ' . ($order->vehicle->brand->name ?? '') . ' ' . ($order->vehicle->model ?? '')) ?: '-' }}</td>
                            <td>{{ $order->suplier->name ?? '-' }}</td>
                            <td>@include('admin.partOrders.partials.badge', ['value' => $order->status, 'label' => App\Models\PartOrder::STATUS_SELECT[$order->status] ?? $order->status])</td>
                            <td>@include('admin.partOrders.partials.badge', ['type' => 'priority', 'value' => $order->priority, 'label' => App\Models\PartOrder::PRIORITY_SELECT[$order->priority] ?? $order->priority])</td>
                            <td>{{ optional($order->expected_delivery_date)->format('Y-m-d') ?: '-' }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td>
                                @can('part_order_show')<a class="btn btn-xs btn-primary" href="{{ route('admin.part-orders.show', $order) }}">{{ trans('global.view') }}</a>@endcan
                                @can('part_order_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.part-orders.edit', $order) }}">{{ trans('global.edit') }}</a>@endcan
                                @can('part_payment_create')<a class="btn btn-xs btn-default" href="{{ route('admin.part-payments.create', ['part_order_id' => $order->id]) }}">Pagamento</a>@endcan
                                @can('part_receipt_create')<a class="btn btn-xs btn-success" href="{{ route('admin.part-receipts.create', ['part_order_id' => $order->id]) }}">Receber</a>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Sem encomendas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $partOrders->links() }}
        </div>
    </div>
</div>
@endsection

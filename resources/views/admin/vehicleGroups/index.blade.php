@extends('layouts.admin')
@section('content')
<div class="content">
    @canany(['vehicle_group_create', 'vehicle_lot_create'])
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.vehicle-groups.create') }}">
                    Novo lote
                </a>
            </div>
        </div>
    @endcanany

    <div class="panel panel-default">
        <div class="panel-heading">
            Lotes
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover datatable datatable-VehicleGroup">
                    <thead>
                        <tr>
                            <th width="10"></th>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Viaturas</th>
                            <th>Estado</th>
                            <th>Pagamentos</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicleGroups as $vehicleGroup)
                            <tr data-entry-id="{{ $vehicleGroup->id }}">
                                <td></td>
                                <td>{{ $vehicleGroup->id }}</td>
                                <td>{{ $vehicleGroup->name }}</td>
                                <td>{{ $vehicleGroup->customer->name ?? '-' }}</td>
                                <td>{{ $vehicleGroup->type === 'unitario' ? 'Unitario' : 'Lote' }}</td>
                                <td>&euro;{{ number_format($vehicleGroup->effective_total, 2, ',', '.') }}</td>
                                <td>{{ $vehicleGroup->items_count }}</td>
                                <td>{{ $vehicleGroup->status }}</td>
                                <td>{{ $vehicleGroup->payments_count }}</td>
                                <td>
                                    @canany(['vehicle_group_show', 'vehicle_lot_show'])
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicle-groups.show', $vehicleGroup->id) }}">
                                            {{ trans('global.view') }}
                                        </a>
                                    @endcanany
                                    @canany(['vehicle_group_edit', 'vehicle_lot_edit'])
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.vehicle-groups.edit', $vehicleGroup->id) }}">
                                            {{ trans('global.edit') }}
                                        </a>
                                    @endcanany
                                    @canany(['vehicle_group_delete', 'vehicle_lot_delete'])
                                        <form action="{{ route('admin.vehicle-groups.destroy', $vehicleGroup->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                        </form>
                                    @endcanany
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
$(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  $('.datatable-VehicleGroup:not(.ajaxTable)').DataTable({ buttons: dtButtons })
})
</script>
@endsection

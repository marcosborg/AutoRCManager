@extends('layouts.admin')
@section('content')
<div class="content">
    @can('repair_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.supplier-orders.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.supplierOrder.title_singular') }}
                </a>
                <a class="btn btn-default" href="{{ route('admin.supplier-orders.summary') }}">
                    Resumo por fornecedor
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.supplierOrder.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-SupplierOrder">
                            <thead>
                                <tr>
                                    <th width="10">
                                    </th>
                                    <th>{{ trans('cruds.supplierOrder.fields.id') }}</th>
                                    <th>{{ trans('cruds.supplierOrder.fields.suplier') }}</th>
                                    <th>{{ trans('cruds.supplierOrder.fields.repair') }}</th>
                                    <th>{{ trans('cruds.supplierOrder.fields.order_date') }}</th>
                                    <th>{{ trans('cruds.supplierOrder.fields.notes') }}</th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supplierOrders as $order)
                                    <tr data-entry-id="{{ $order->id }}">
                                        <td></td>
                                        <td>{{ $order->id }}</td>
                                        <td>{{ $order->suplier->name ?? '' }}</td>
                                        <td>{{ $order->repair->id ?? '-' }}</td>
                                        <td>{{ $order->order_date }}</td>
                                        <td>{{ $order->notes }}</td>
                                        <td>
                                            @can('repair_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.supplier-orders.show', $order->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan
                                            @can('repair_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.supplier-orders.edit', $order->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan
                                            @can('repair_delete')
                                                <form action="{{ route('admin.supplier-orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
@can('repair_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.supplier-orders.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-SupplierOrder:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content">
    @can('vehicle_group_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.vehicle-groups.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.vehicleGroup.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleGroup.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-VehicleGroup">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleGroup.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleGroup.fields.name') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleGroup.fields.wholesale_pvp') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleGroup.fields.vehicles') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.vehicleGroup.fields.clients') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicleGroups as $key => $vehicleGroup)
                                    <tr data-entry-id="{{ $vehicleGroup->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $vehicleGroup->id ?? '' }}
                                        </td>
                                    <td>
                                        {{ $vehicleGroup->name ?? '' }}
                                    </td>
                                    <td>
                                        {{ number_format($vehicleGroup->wholesale_pvp ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ $vehicleGroup->vehicles_count }}
                                    </td>
                                        <td>
                                            {{ $vehicleGroup->clients_count }}
                                        </td>
                                        <td>
                                            @can('vehicle_group_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicle-groups.show', $vehicleGroup->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('vehicle_group_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.vehicle-groups.edit', $vehicleGroup->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('vehicle_group_delete')
                                                <form action="{{ route('admin.vehicle-groups.destroy', $vehicleGroup->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('vehicle_group_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.vehicle-groups.massDestroy') }}",
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
  let table = $('.datatable-VehicleGroup:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection

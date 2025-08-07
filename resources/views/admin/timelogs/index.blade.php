@extends('layouts.admin')
@section('content')
<div class="content">
    @can('timelog_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.timelogs.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.timelog.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.timelog.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Timelog">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.timelog.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.timelog.fields.vehicle') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicle.fields.model') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicle.fields.year') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicle.fields.color') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicle.fields.transmission') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicle.fields.month') }}
                                </th>
                                <th>
                                    {{ trans('cruds.timelog.fields.user') }}
                                </th>
                                <th>
                                    {{ trans('cruds.timelog.fields.start_time') }}
                                </th>
                                <th>
                                    {{ trans('cruds.timelog.fields.end_time') }}
                                </th>
                                <th>
                                    {{ trans('cruds.timelog.fields.rounded_minutes') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                        </thead>
                    </table>
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
@can('timelog_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.timelogs.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).data(), function (entry) {
          return entry.id
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

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: "{{ route('admin.timelogs.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'vehicle_license', name: 'vehicle.license' },
{ data: 'vehicle.model', name: 'vehicle.model' },
{ data: 'vehicle.year', name: 'vehicle.year' },
{ data: 'vehicle.color', name: 'vehicle.color' },
{ data: 'vehicle.transmission', name: 'vehicle.transmission' },
{ data: 'vehicle.month', name: 'vehicle.month' },
{ data: 'user_name', name: 'user.name' },
{ data: 'start_time', name: 'start_time' },
{ data: 'end_time', name: 'end_time' },
{ data: 'rounded_minutes', name: 'rounded_minutes' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-Timelog').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection
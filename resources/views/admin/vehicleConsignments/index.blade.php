@extends('layouts.admin')
@section('content')
<div class="content">
    @can('vehicle_consignment_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.vehicle-consignments.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.vehicleConsignment.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleConsignment.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.vehicle-consignments.index') }}" style="margin-bottom: 15px;">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="vehicle_id">{{ trans('cruds.vehicleConsignment.fields.vehicle') }}</label>
                                <select class="form-control select2" name="vehicle_id" id="vehicle_id">
                                    <option value="">{{ trans('global.pleaseSelect') }}</option>
                                    @foreach($vehicles as $id => $entry)
                                        <option value="{{ $id }}" {{ (string) request('vehicle_id') === (string) $id ? 'selected' : '' }}>{{ $entry }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2" style="margin-top: 25px;">
                                <button class="btn btn-primary" type="submit">{{ trans('global.search') }}</button>
                            </div>
                        </div>
                    </form>
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-VehicleConsignment">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.vehicle') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.from_unit') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.to_unit') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.reference_value') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.starts_at') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.ends_at') }}
                                </th>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.status') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                            <tr>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
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

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: "{{ route('admin.vehicle-consignments.index', ['vehicle_id' => request('vehicle_id')]) }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
      { data: 'id', name: 'id' },
      { data: 'vehicle_label', name: 'vehicle.license' },
      { data: 'from_unit_name', name: 'from_unit.name' },
      { data: 'to_unit_name', name: 'to_unit.name' },
      { data: 'reference_value', name: 'reference_value' },
      { data: 'starts_at', name: 'starts_at' },
      { data: 'ends_at', name: 'ends_at' },
      { data: 'status', name: 'status' },
      { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-VehicleConsignment').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
  let visibleColumnsIndexes = null;
  $('.datatable thead').on('input', '.search', function () {
      let strict = $(this).attr('strict') || false
      let value = strict && this.value ? "^" + this.value + "$" : this.value

      let index = $(this).parent().index()
      if (visibleColumnsIndexes !== null) {
        index = visibleColumnsIndexes[index]
      }

      table
        .column(index)
        .search(value, strict)
        .draw()
  });
  table.on('column-visibility.dt', function(e, settings, column, state) {
      visibleColumnsIndexes = []
      table.columns(":visible").every(function(colIdx) {
          visibleColumnsIndexes.push(colIdx);
      });
  })
});

</script>
@endsection

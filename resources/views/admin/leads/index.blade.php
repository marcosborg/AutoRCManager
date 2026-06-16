@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Leads Meta</div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Lead">
                        <thead>
                            <tr>
                                <th width="10"></th>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Orcamento</th>
                                <th>Veiculo</th>
                                <th>Vendedor</th>
                                <th>Estado</th>
                                <th>&nbsp;</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td>
                                    <select class="search" strict="true">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($statuses as $status => $label)
                                            <option value="{{ $status }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td></td>
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
    ajax: "{{ route('admin.leads.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
      { data: 'id', name: 'id' },
      { data: 'created_at', name: 'created_at' },
      { data: 'full_name', name: 'full_name' },
      { data: 'phone', name: 'phone' },
      { data: 'email', name: 'email' },
      { data: 'budget', name: 'budget' },
      { data: 'vehicle_interest', name: 'vehicle_interest' },
      { data: 'assigned_user_name', name: 'assigned_user.name' },
      { data: 'status', name: 'status' },
      { data: 'actions', name: '{{ trans('global.actions') }}', orderable: false, searchable: false }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-Lead').DataTable(dtOverrideGlobals);

  let visibleColumnsIndexes = null;
  $('.datatable thead').on('input change', '.search', function () {
    let strict = $(this).attr('strict') || false
    let value = strict && this.value ? "^" + this.value + "$" : this.value
    let index = $(this).parent().index()
    if (visibleColumnsIndexes !== null) index = visibleColumnsIndexes[index]
    table.column(index).search(value, strict).draw()
  });
  table.on('column-visibility.dt', function() {
    visibleColumnsIndexes = []
    table.columns(":visible").every(function(colIdx) { visibleColumnsIndexes.push(colIdx) })
  });
});
</script>
@endsection

@section('styles')
@parent
<style>
    input.search, select.search { width: 100px!important; }
</style>
@endsection

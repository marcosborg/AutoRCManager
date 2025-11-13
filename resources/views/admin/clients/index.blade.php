@extends('layouts.admin')
@section('content')
<div class="content">
    @can('client_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.clients.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.client.title_singular') }}
                </a>
                <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                    {{ trans('global.app_csvImport') }}
                </button>
                @include('csvImport.modal', ['model' => 'Client', 'route' => 'admin.clients.parseCsvImport'])
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.client.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Client">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.name') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.vat') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.address') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.location') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.zip') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.phone') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.email') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.country') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_name') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_vat') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_address') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_location') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_zip') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_phone') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_email') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.company_country') }}
                                </th>
                                <th>
                                    {{ trans('cruds.client.fields.created_at') }}
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
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($countries as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
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
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($countries as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
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
@can('client_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.clients.massDestroy') }}",
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
    ajax: "{{ route('admin.clients.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'name', name: 'name' },
{ data: 'vat', name: 'vat' },
{ data: 'address', name: 'address' },
{ data: 'location', name: 'location' },
{ data: 'zip', name: 'zip' },
{ data: 'phone', name: 'phone' },
{ data: 'email', name: 'email' },
{ data: 'country_name', name: 'country.name' },
{ data: 'company_name', name: 'company_name' },
{ data: 'company_vat', name: 'company_vat' },
{ data: 'company_address', name: 'company_address' },
{ data: 'company_location', name: 'company_location' },
{ data: 'company_zip', name: 'company_zip' },
{ data: 'company_phone', name: 'company_phone' },
{ data: 'company_email', name: 'company_email' },
{ data: 'company_country_name', name: 'company_country.name' },
{ data: 'created_at', name: 'created_at' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-Client').DataTable(dtOverrideGlobals);
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
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('client_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.clients.massDestroy') }}",
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
    ajax: "{{ route('admin.clients.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
      { data: 'id', name: 'id' },
      { data: 'name', name: 'name' },
      { data: 'vat', name: 'vat' },
      { data: 'address', name: 'address' },
      { data: 'location', name: 'location' },
      { data: 'zip', name: 'zip' },
      { data: 'phone', name: 'phone' },
      { data: 'email', name: 'email' },
      { data: 'country_name', name: 'country.name' },
      { data: 'company_name', name: 'company_name' },
      { data: 'company_vat', name: 'company_vat' },
      { data: 'company_address', name: 'company_address' },
      { data: 'company_location', name: 'company_location' },
      { data: 'company_zip', name: 'company_zip' },
      { data: 'company_phone', name: 'company_phone' },
      { data: 'company_email', name: 'company_email' },
      { data: 'company_country_name', name: 'company_country.name' },
      { data: 'created_at', name: 'created_at' },
      { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };

  let table = $('.datatable-Client').DataTable(dtOverrideGlobals);

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
  });

  // 👇 Novo: clicar na row abre o edit, mantendo os botões operacionais
  let editBaseUrl = '{{ url('admin/clients') }}';

  $('.datatable-Client tbody').on('click', 'tr', function (e) {
      // se clicou num link, botão, input, etc → não faz redirect
      if ($(e.target).closest('a, button, .btn, input, label, .dropdown-menu').length) {
          return;
      }

      let $cell = $(e.target).closest('td');
      let cellIndex = $cell.index();
      let lastIndex = table.columns().count() - 1;

      // ignora clique na primeira coluna (checkbox/select) e última (actions)
      if (cellIndex === 0 || cellIndex === lastIndex) {
          return;
      }

      let data = table.row(this).data();
      if (data && data.id) {
          window.location = editBaseUrl + '/' + data.id + '/edit';
      }
  });

});
</script>
@endsection

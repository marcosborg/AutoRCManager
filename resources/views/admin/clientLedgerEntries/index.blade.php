@extends('layouts.admin')
@section('content')
<div class="content">
    @can('client_ledger_entry_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.client-ledger-entries.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.clientLedgerEntry.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.clientLedgerEntry.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-ClientLedgerEntry">
                            <thead>
                                <tr>
                                    <th width="10">
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.client') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.vehicle') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.entry_type') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.amount') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.entry_date') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.description') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.clientLedgerEntry.fields.attachment') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientLedgerEntries as $key => $entry)
                                    <tr data-entry-id="{{ $entry->id }}">
                                        <td>
                                        </td>
                                        <td>
                                            {{ $entry->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $entry->client->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $entry->vehicle->license ?? '' }}
                                        </td>
                                        <td>
                                            {{ $entry->entry_type === 'debit' ? 'Debito' : 'Credito' }}
                                        </td>
                                        <td>
                                            {{ number_format((float) $entry->amount, 2, ',', '.') }}
                                        </td>
                                        <td>
                                            {{ $entry->entry_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $entry->description ?? '' }}
                                        </td>
                                        <td>
                                            @foreach($entry->attachment as $media)
                                                <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                                            @endforeach
                                        </td>
                                        <td>
                                            @can('client_ledger_entry_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.client-ledger-entries.show', $entry->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('client_ledger_entry_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.client-ledger-entries.edit', $entry->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('client_ledger_entry_delete')
                                                <form action="{{ route('admin.client-ledger-entries.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('client_ledger_entry_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.client-ledger-entries.massDestroy') }}",
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
  let table = $('.datatable-ClientLedgerEntry:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection

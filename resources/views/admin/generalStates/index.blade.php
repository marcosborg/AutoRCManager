@extends('layouts.admin')
@section('content')
<div class="content">
    @can('general_state_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.general-states.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.generalState.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.generalState.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-GeneralState">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th width="60">
                                        Ordem
                                    </th>
                                    <th>
                                        {{ trans('cruds.generalState.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.generalState.fields.name') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.generalState.fields.notification') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.generalState.fields.emails') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.generalState.fields.position') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="general-states-sortable">
                                @foreach($generalStates as $key => $generalState)
                                    <tr data-entry-id="{{ $generalState->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            @can('general_state_edit')
                                                <span class="general-state-drag-handle" title="Arrastar para ordenar">
                                                    <i class="fa fa-bars"></i>
                                                </span>
                                            @else
                                                {{ $key + 1 }}
                                            @endcan
                                        </td>
                                        <td>
                                            {{ $generalState->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $generalState->name ?? '' }}
                                        </td>
                                        <td>
                                            <span style="display:none">{{ $generalState->notification ?? '' }}</span>
                                            <input type="checkbox" disabled="disabled" {{ $generalState->notification ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            {{ $generalState->emails ?? '' }}
                                        </td>
                                        <td>
                                            {{ $generalState->position ?? '' }}
                                        </td>
                                        <td>
                                            @can('general_state_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.general-states.show', $generalState->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('general_state_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.general-states.edit', $generalState->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('general_state_delete')
                                                <form action="{{ route('admin.general-states.destroy', $generalState->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('general_state_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.general-states.massDestroy') }}",
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
    ordering: false,
    paging: false,
    searching: false,
    pageLength: 100,
  });
  let table = $('.datatable-GeneralState:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });

  @can('general_state_edit')
  const tbody = document.getElementById('general-states-sortable');
  let draggedRow = null;

  tbody.querySelectorAll('tr[data-entry-id]').forEach(function(row) {
    const handle = row.querySelector('.general-state-drag-handle');
    if (!handle) return;

    handle.addEventListener('mousedown', function() {
      row.setAttribute('draggable', 'true');
    });

    row.addEventListener('dragstart', function(event) {
      draggedRow = row;
      row.classList.add('general-state-row-dragging');
      event.dataTransfer.effectAllowed = 'move';
    });

    row.addEventListener('dragend', function() {
      row.classList.remove('general-state-row-dragging');
      row.removeAttribute('draggable');
      draggedRow = null;
      saveGeneralStateOrder();
    });

    row.addEventListener('dragover', function(event) {
      event.preventDefault();
      if (!draggedRow || draggedRow === row) return;

      const rect = row.getBoundingClientRect();
      const insertAfter = event.clientY > rect.top + rect.height / 2;
      tbody.insertBefore(draggedRow, insertAfter ? row.nextSibling : row);
    });
  });

  function saveGeneralStateOrder() {
    const order = Array.from(tbody.querySelectorAll('tr[data-entry-id]')).map(function(row) {
      return row.getAttribute('data-entry-id');
    });

    $.ajax({
      headers: {'x-csrf-token': _token},
      method: 'POST',
      url: "{{ route('admin.general-states.reorder') }}",
      data: { order: order }
    }).done(function() {
      tbody.querySelectorAll('tr[data-entry-id]').forEach(function(row, index) {
        const positionCell = row.children[6];
        if (positionCell) {
          positionCell.textContent = index + 1;
        }
      });
    }).fail(function() {
      alert('Nao foi possivel guardar a ordem.');
      window.location.reload();
    });
  }
  @endcan
  
})

</script>
@endsection

@section('styles')
@parent
<style>
    .general-state-drag-handle {
        cursor: move;
        display: inline-block;
        padding: 4px 8px;
        color: #555;
    }

    .general-state-row-dragging {
        opacity: .45;
    }
</style>
@endsection

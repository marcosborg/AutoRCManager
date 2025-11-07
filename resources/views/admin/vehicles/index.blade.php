@extends('layouts.admin')
@section('content')
<div class="content">
    @can('vehicle_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.vehicles.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.vehicle.title_singular') }}
                </a>
                <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                    {{ trans('global.app_csvImport') }}
                </button>
                @include('csvImport.modal', ['model' => 'Vehicle', 'route' => 'admin.vehicles.parseCsvImport'])
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicle.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Vehicle">
                        <thead>
                            <tr>
                                <th width="10"></th>
                                <th>{{ trans('cruds.vehicle.fields.general_state') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.license') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.foreign_license') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.brand') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.model') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.month') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.fuel') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.inspec_b') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.pvp') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.suplier') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.client') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.chekin_documents') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.sale_date') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.key') }}</th>
                                <th>&nbsp;</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($general_states as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($brands as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($supliers as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($clients as $key => $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
                                <td><input class="search" type="text" placeholder="{{ trans('global.search') }}"></td>
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
  @can('vehicle_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.vehicles.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).data(), function (entry) { return entry.id });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')
        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }
        }).done(function () { location.reload() })
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
    ajax: "{{ route('admin.vehicles.index') }}",
    columns: [
        { data: 'placeholder', name: 'placeholder' },
        { data: 'general_state_name', name: 'general_state.name' },
        { data: 'license', name: 'license' },
        { data: 'foreign_license', name: 'foreign_license' },
        { data: 'brand_name', name: 'brand.name' },
        { data: 'model', name: 'model' },
        { data: 'month', name: 'month' },
        { data: 'fuel', name: 'fuel' },
        { data: 'inspec_b', name: 'inspec_b' },
        { data: 'pvp', name: 'pvp' },
        { data: 'suplier_name', name: 'suplier.name' },
        { data: 'client_name', name: 'client.name' },
        { data: 'chekin_documents', name: 'chekin_documents' },
        { data: 'sale_date', name: 'sale_date' },
        { data: 'key', name: 'key' },
        { data: 'actions', name: '{{ trans('global.actions') }}', orderable: false, searchable: false }
    ],
    columnDefs: [
      {
        targets: -1,
        className: 'no-row-link', // impedir que clique na célula de ações dispare a navegação da linha
        render: function (data, type, row, meta) {
          let editDeleteButtons = data;
          // botão Financeiro adicional
          let financialBtn = `
              <a href="/admin/financial/${row.id}" class="btn btn-xs btn-success" style="margin-left: 5px;">
                  Financeiro
              </a>`;
          return editDeleteButtons + financialBtn;
        }
      }
    ],
    orderCellsTop: true,
    order: [[1, 'desc']],
    pageLength: 100,
    createdRow: function(row, data, dataIndex) {
      // dá cursor tipo link às células clicáveis (excepto ações)
      $(row).find('td:not(.no-row-link)').css('cursor', 'pointer');
    }
  };

  let table = $('.datatable-Vehicle').DataTable(dtOverrideGlobals);

  // Ajuste ao mudar de tab
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(){
      $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
  });

  // Filtros do cabeçalho
  let visibleColumnsIndexes = null;
  $('.datatable thead').on('input', '.search', function () {
      let strict = $(this).attr('strict') || false
      let value = strict && this.value ? "^" + this.value + "$" : this.value

      let index = $(this).parent().index()
      if (visibleColumnsIndexes !== null) index = visibleColumnsIndexes[index]

      table.column(index).search(value, strict).draw()
  });

  table.on('column-visibility.dt', function() {
      visibleColumnsIndexes = []
      table.columns(":visible").every(function(colIdx) { visibleColumnsIndexes.push(colIdx) });
  });

  /**
   * ---------- LINHA CLICÁVEL ABRE O EDIT ----------
   * Estratégia:
   * - Em cada draw, procurar no HTML da célula de ações um link que aponte para ".../edit"
   *   (exclui delete/financeiro) e gravar esse href no data-href da <tr>.
   * - Delegação de clique no tbody que ignora cliques em <a>, <button>, inputs, etc.
   */

  function setRowHrefFromActions() {
    $('.datatable-Vehicle tbody tr').each(function() {
      const $tr = $(this);
      const $actionsTd = $tr.find('td').last();

      if (!$actionsTd.length) return;

      // Tenta encontrar o link de editar:
      // 1) href termina com /edit
      // 2) não é delete (btn-danger) nem financeiro
      let $editLink = $actionsTd.find('a[href*="/edit"]').filter(function() {
        const href = $(this).attr('href') || '';
        const isDelete = $(this).hasClass('btn-danger') || /delete|destroy/i.test($(this).text());
        const isFinancial = /financial|financeiro/i.test($(this).text());
        // garantir que é mesmo rota de edit
        const looksLikeEdit = /\/edit(\?|$)/.test(href);
        return looksLikeEdit && !isDelete && !isFinancial;
      }).first();

      // fallback: primeiro link que não seja delete nem financeiro
      if (!$editLink.length) {
        $editLink = $actionsTd.find('a').filter(function() {
          const txt = ($(this).text() || '').trim();
          const isDelete = $(this).hasClass('btn-danger') || /apagar|delete|destroy/i.test(txt);
          const isFinancial = /financial|financeiro/i.test(txt);
          return !isDelete && !isFinancial;
        }).first();
      }

      const href = $editLink.attr('href');
      if (href) {
        $tr.attr('data-href', href);
        // acessibilidade
        $tr.attr('tabindex', '0').attr('role', 'link');
      } else {
        $tr.removeAttr('data-href').removeAttr('tabindex').removeAttr('role');
      }
    });
  }

  // correr ao desenhar
  table.on('draw.dt', function() { setRowHrefFromActions(); });
  // primeiro draw manual (por segurança)
  setRowHrefFromActions();

  // elementos a ignorar no clique da linha
  const IGNORE_SELECTORS = [
    'a', 'button', 'input', 'textarea', 'label', 'select',
    '.no-row-link', '.dropdown', '.select2', '.dt-button'
  ].join(',');

  function shouldIgnore(target) {
    return target.closest(IGNORE_SELECTORS) !== null;
  }

  // clique na linha
  $('.datatable-Vehicle tbody').on('click', 'tr', function(e) {
    // ignora se clicou em link/botão/input/etc.
    if (shouldIgnore(e.target)) return;

    const href = $(this).attr('data-href');
    if (!href) return;

    // botão do meio (1) ou Ctrl/Meta abre nova aba
    if (e.button === 1 || e.ctrlKey || e.metaKey) {
      window.open(href, '_blank');
    } else {
      window.location.href = href;
    }
  });

  // teclado: Enter/Espaço
  $('.datatable-Vehicle tbody').on('keydown', 'tr', function(e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    if (shouldIgnore(e.target)) return;

    const href = $(this).attr('data-href');
    if (!href) return;

    e.preventDefault();
    window.location.href = href;
  });
});
</script>
@endsection

@section('styles')
@parent
<style>
td { padding: 5px!important; margin: 0!important; }
input.search { width: 100px!important; }
select.search { width: 100px!important; }
/* feedback visual: só nas células clicáveis (definido em createdRow) */
</style>
@endsection

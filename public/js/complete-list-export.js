/* Export all filtered rows without changing the user's page or selection. */
(function ($) {
  'use strict';
  function plain(value) {
    var doc = new DOMParser().parseFromString(String(value == null ? '' : value), 'text/html');
    return (doc.body.textContent || '').replace(/\u00a0/g, ' ').trim();
  }
  function csv(value) {
    value = plain(value);
    // Prevent spreadsheet formula execution, including after leading whitespace.
    if (/^[\s]*[=+@-]/.test(value)) value = "'" + value;
    return '"' + value.replace(/"/g, '""') + '"';
  }
  $(document).on('init.dt', function (event, settings) {
    if (!settings || !settings._buttons || settings._completeExport) return;
    settings._completeExport = true;
    // Some lists provide a streamed server export instead of loading all rows in the browser.
    if ($(settings.nTable).is('[data-native-full-export]')) return;
    var dt = new $.fn.dataTable.Api(settings);
    dt.button().add(null, {
      text: 'Exportar todos os resultados (CSV)',
      className: 'btn-default',
      action: async function (e, api, node) {
        var button = $(node), busy = button.data('exporting');
        if (busy) return;
        button.data('exporting', true).prop('disabled', true).text('A preparar exportação…');
        try {
          var columns = settings.aoColumns.map(function (column, index) {
            return { column: column, index: index, title: plain(column.sTitle || '') };
          }).filter(function (item) {
            return item.title && !['placeholder', 'actions', 'vehicle_thumb'].includes(item.column.mData)
              && !/select-checkbox|no-export/.test(item.column.sClass || '')
              && !/^(ações|acções|actions|foto)$/i.test(item.title);
          });
          var rows = [], total = null, seen = new Set();
          if (settings.oFeatures.bServerSide) {
            var params = $.extend(true, {}, api.ajax.params());
            var ajax = settings.ajax;
            if (typeof ajax === 'function') throw new Error('unsupported');
            var url = typeof ajax === 'string' ? ajax : ajax.url;
            while (total === null || rows.length < total) {
              params.start = rows.length;
              params.length = 1000;
              var result = await $.ajax({
                url: url, type: (ajax && (ajax.type || ajax.method)) || 'GET',
                data: params, dataType: 'json', timeout: 60000,
                headers: $.extend({}, ajax.headers || {}, {'X-Requested-With': 'XMLHttpRequest'})
              });
              var batch = result.data;
              if (!Array.isArray(batch) || result.error || !Number.isFinite(Number(result.recordsFiltered))) throw new Error('invalid');
              if (total !== null && total !== Number(result.recordsFiltered)) throw new Error('changed');
              total = Number(result.recordsFiltered);
              if (total > 0 && batch.length === 0) throw new Error('incomplete');
              batch.forEach(function (row) {
                if (row.id != null) {
                  if (seen.has(String(row.id))) throw new Error('changed');
                  seen.add(String(row.id));
                }
              });
              rows = rows.concat(batch);
              if (rows.length > total) throw new Error('invalid');
            }
          } else {
            rows = api.rows({search: 'applied', order: 'applied'}).data().toArray();
          }
          var lines = [columns.map(function (item) { return csv(item.title); }).join(';')];
          rows.forEach(function (row, rowIndex) {
            lines.push(columns.map(function (item) {
              return csv(item.column.fnGetData(row, 'display', {settings: settings, row: rowIndex, col: item.index}));
            }).join(';'));
          });
          var blob = new Blob(['\ufeff' + lines.join('\r\n')], {type: 'text/csv;charset=utf-8;'});
          var href = URL.createObjectURL(blob), link = document.createElement('a');
          link.href = href;
          link.download = 'listagem-' + new Date().toISOString().slice(0, 10) + '.csv';
          document.body.appendChild(link); link.click(); link.remove();
          setTimeout(function () { URL.revokeObjectURL(href); }, 60000);
        } catch (error) {
          alert('Não foi possível concluir a exportação. Atualize a listagem e tente novamente. Nenhum ficheiro parcial foi criado.');
        } finally {
          button.data('exporting', false).prop('disabled', false).text('Exportar todos os resultados (CSV)');
        }
      }
    });
  });
})(jQuery);

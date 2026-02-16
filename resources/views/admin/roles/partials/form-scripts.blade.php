@section('scripts')
@parent
<script>
  $(function () {
    function bindPermissionsUi() {
      var $table = $('#permissions-matrix');
      if (!$table.length) return;

      var $rows = $table.find('tbody tr.perm-row');
      var $filter = $('#permissions-filter');

      function updateSelectedCount() {
        $('#permissions-selected-count').text($table.find('.perm-cb:checked').length);
      }

      function applyFilter() {
        var q = ($filter.val() || '').toString().trim().toLowerCase();
        if (!q) {
          $rows.show();
          return;
        }

        $rows.each(function () {
          var $row = $(this);
          var hay = ($row.data('perm-search') || '').toString();
          $row.toggle(hay.indexOf(q) !== -1);
        });
      }

      $filter.on('input', applyFilter);
      $table.on('change', '.perm-cb', updateSelectedCount);

      $('#permissions-select-visible').on('click', function () {
        $table.find('tbody tr.perm-row:visible .perm-cb').prop('checked', true);
        updateSelectedCount();
      });

      $('#permissions-deselect-visible').on('click', function () {
        $table.find('tbody tr.perm-row:visible .perm-cb').prop('checked', false);
        updateSelectedCount();
      });

      $table.on('click', '.perm-row-all', function () {
        $(this).closest('tr').find('.perm-cb').prop('checked', true);
        updateSelectedCount();
      });

      $table.on('click', '.perm-row-none', function () {
        $(this).closest('tr').find('.perm-cb').prop('checked', false);
        updateSelectedCount();
      });

      applyFilter();
      updateSelectedCount();
    }

    function bindUsersUi() {
      var $rows = $('#users-checklist tr.user-row');
      if (!$rows.length) return;

      var $filter = $('#users-filter');

      function updateSelectedCount() {
        $('#users-selected-count').text($('.user-cb:checked').length);
      }

      function applyFilter() {
        var q = ($filter.val() || '').toString().trim().toLowerCase();
        if (!q) {
          $rows.show();
          return;
        }

        $rows.each(function () {
          var $row = $(this);
          var hay = ($row.data('user-search') || '').toString();
          $row.toggle(hay.indexOf(q) !== -1);
        });
      }

      $filter.on('input', applyFilter);
      $(document).on('change', '.user-cb', updateSelectedCount);

      $('#users-select-visible').on('click', function () {
        $('#users-checklist tr.user-row:visible .user-cb').prop('checked', true);
        updateSelectedCount();
      });

      $('#users-deselect-visible').on('click', function () {
        $('#users-checklist tr.user-row:visible .user-cb').prop('checked', false);
        updateSelectedCount();
      });

      applyFilter();
      updateSelectedCount();
    }

    bindPermissionsUi();
    bindUsersUi();
  });
</script>
@endsection


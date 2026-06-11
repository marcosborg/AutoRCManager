<div class="modal fade" id="provenience-modal" tabindex="-1" role="dialog" aria-labelledby="provenience-modal-title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="provenience-modal-form" method="POST" action="{{ route('admin.proveniences.store') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="provenience-modal-title">Nova proveniência</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" id="provenience-modal-errors" style="display:none;"></div>
                    <div class="form-group">
                        <label class="required" for="provenience-modal-name">Nome</label>
                        <input class="form-control" type="text" name="name" id="provenience-modal-name" maxlength="255" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="provenience-modal-submit">Criar proveniência</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #provenience-modal {
        z-index: 1060;
    }

    #provenience-modal + .modal-backdrop,
    .provenience-modal-backdrop {
        z-index: 1055;
    }
</style>

@push('scripts')
<script>
    $(function () {
        var $modal = $('#provenience-modal');
        var $form = $('#provenience-modal-form');
        var $name = $('#provenience-modal-name');
        var $errors = $('#provenience-modal-errors');
        var $submit = $('#provenience-modal-submit');
        var targetSelector = null;

        $(document).on('click', '.js-create-provenience', function () {
            targetSelector = $(this).data('target');
            $form[0].reset();
            $errors.hide().empty();
            $modal.modal('show');
        });

        $modal.on('shown.bs.modal', function () {
            $name.trigger('focus');
            $('.modal-backdrop').last().addClass('provenience-modal-backdrop');
        });

        $modal.on('hidden.bs.modal', function () {
            if ($('.modal.in').length) {
                $('body').addClass('modal-open');
            }
        });

        $form.on('submit', function (event) {
            event.preventDefault();

            $errors.hide().empty();
            $submit.prop('disabled', true);

            $.ajax({
                method: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                headers: {
                    'Accept': 'application/json'
                }
            }).done(function (provenience) {
                var $target = $(targetSelector);
                var option = new Option(provenience.name, provenience.id, true, true);
                $target.append(option).trigger('change');
                $modal.modal('hide');
            }).fail(function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                var message = errors && errors.name
                    ? errors.name[0]
                    : 'Não foi possível criar a proveniência.';

                $errors.text(message).show();
            }).always(function () {
                $submit.prop('disabled', false);
            });
        });
    });
</script>
@endpush

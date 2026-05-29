<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="{{ $formId }}" class="js-reference-form" method="POST" action="{{ $route }}" data-select-targets="{{ implode('|', $selectTargets) }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">{{ $title }}</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger js-reference-error" style="display:none;"></div>
                    <div class="form-group">
                        <label class="required" for="{{ $formId }}-name">Nome</label>
                        <input class="form-control" type="text" name="name" id="{{ $formId }}-name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-default" id="vehicle-send-to-workshop-panel">
    <div class="panel-heading">
        Envio para oficina
    </div>
    <div class="panel-body" style="padding: 10px;">
        @if($showWorkshopSection ?? false)
            <div class="alert alert-info" style="margin-bottom: 0;">
                Esta viatura já se encontra na oficina. A intervenção só deve ser iniciada quando o trabalho começar.
            </div>
        @else
            <p>Move a viatura para a área da oficina com o estado <strong>Viaturas para reparar</strong>, sem criar uma intervenção.</p>
            <button class="btn btn-primary btn-sm" id="send-to-workshop-button" type="button">
                Enviar para oficina
            </button>
        @endif
    </div>
</div>

@section('scripts')
@parent
<script>
    $(function () {
        $('#send-to-workshop-button').on('click', function () {
            var form = document.getElementById('vehicle-send-to-workshop-form');
            if (!form) {
                return;
            }

            if (form.reportValidity && !form.reportValidity()) {
                return;
            }

            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
</script>
@endsection

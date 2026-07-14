@php
    $importDecision = old('import_decision', optional($importProcess)->decision);
    $importDecisionAt = old('import_decision_at', optional(optional($importProcess)->decision_at)->format('Y-m-d\TH:i'));
    $importDeadline = optional(optional($importProcess)->deadline_at)->format('d/m/Y');
@endphp
<input type="hidden" name="import_process_present" value="1">

<div class="alert alert-info">
    Este acompanhamento permanece associado à viatura mesmo depois de sair do estado Adjudicação.
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group {{ $errors->has('import_decision') ? 'has-error' : '' }}">
            <label class="required">Destino da viatura</label>
            <div>
                <label class="radio-inline">
                    <input type="radio" name="import_decision" value="legalize" {{ $importDecision === 'legalize' ? 'checked' : '' }}> Legalizar
                </label>
                <label class="radio-inline">
                    <input type="radio" name="import_decision" value="scrap" {{ $importDecision === 'scrap' ? 'checked' : '' }}> Abater
                </label>
            </div>
            @if($errors->has('import_decision'))
                <span class="help-block">{{ $errors->first('import_decision') }}</span>
            @endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group {{ $errors->has('import_decision_at') ? 'has-error' : '' }}">
            <label for="import_decision_at">Data da decisão</label>
            <input class="form-control" type="datetime-local" name="import_decision_at" id="import_decision_at" value="{{ $importDecisionAt }}">
            @if($errors->has('import_decision_at'))
                <span class="help-block">{{ $errors->first('import_decision_at') }}</span>
            @endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="import_deadline_display">Prazo calculado</label>
            <input class="form-control" id="import_deadline_display" value="{{ $importDeadline }}" readonly>
            <p class="help-block">20 dias para Legalizar ou 10 dias para Abater.</p>
        </div>
    </div>
</div>

<div id="import-legalize-fields" style="display:none;">
    <hr>
    <h4>Legalização</h4>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group {{ $errors->has('import_agency_documents_sent_at') ? 'has-error' : '' }}">
                <input type="hidden" name="import_agency_documents_sent" value="0">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="import_agency_documents_sent" id="import_agency_documents_sent" value="1" data-timestamp-target="import_agency_documents_sent_at" {{ old('import_agency_documents_sent', optional($importProcess)->agency_documents_sent_at ? 1 : 0) ? 'checked' : '' }}>
                        Documentos enviados para agência
                    </label>
                </div>
                <label for="import_agency_documents_sent_at">Data</label>
                <input class="form-control" type="datetime-local" name="import_agency_documents_sent_at" id="import_agency_documents_sent_at" value="{{ old('import_agency_documents_sent_at', optional(optional($importProcess)->agency_documents_sent_at)->format('Y-m-d\TH:i')) }}">
                @if($errors->has('import_agency_documents_sent_at'))
                    <span class="help-block">{{ $errors->first('import_agency_documents_sent_at') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group {{ $errors->has('import_documents_received_at') ? 'has-error' : '' }}">
                <input type="hidden" name="import_documents_received" value="0">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="import_documents_received" id="import_documents_received" value="1" data-timestamp-target="import_documents_received_at" {{ old('import_documents_received', optional($importProcess)->documents_received_at ? 1 : 0) ? 'checked' : '' }}>
                        Documentos recebidos — pronto para inspeção
                    </label>
                </div>
                <label for="import_documents_received_at">Data da receção</label>
                <input class="form-control" type="datetime-local" name="import_documents_received_at" id="import_documents_received_at" value="{{ old('import_documents_received_at', optional(optional($importProcess)->documents_received_at)->format('Y-m-d\TH:i')) }}">
                @if($errors->has('import_documents_received_at'))
                    <span class="help-block">{{ $errors->first('import_documents_received_at') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group {{ $errors->has('import_new_license_received_at') || $errors->has('import_new_license') ? 'has-error' : '' }}">
                <input type="hidden" name="import_new_license_received" value="0">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="import_new_license_received" id="import_new_license_received" value="1" data-timestamp-target="import_new_license_received_at" {{ old('import_new_license_received', optional($importProcess)->new_license_received_at ? 1 : 0) ? 'checked' : '' }}>
                        Receção da nova matrícula
                    </label>
                </div>
                <label for="import_new_license">Nova matrícula</label>
                <input class="form-control" type="text" name="import_new_license" id="import_new_license" value="{{ old('import_new_license', optional($importProcess)->new_license) }}">
                <label for="import_new_license_received_at" style="margin-top:8px;">Data da receção</label>
                <input class="form-control" type="datetime-local" name="import_new_license_received_at" id="import_new_license_received_at" value="{{ old('import_new_license_received_at', optional(optional($importProcess)->new_license_received_at)->format('Y-m-d\TH:i')) }}">
                @if($errors->has('import_new_license'))
                    <span class="help-block">{{ $errors->first('import_new_license') }}</span>
                @endif
                @if($errors->has('import_new_license_received_at'))
                    <span class="help-block">{{ $errors->first('import_new_license_received_at') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div id="import-scrap-fields" style="display:none;">
    <hr>
    <h4>Abate</h4>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group {{ $errors->has('import_scrapped_at') ? 'has-error' : '' }}">
                <input type="hidden" name="import_scrapped" value="0">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="import_scrapped" id="import_scrapped" value="1" data-timestamp-target="import_scrapped_at" {{ old('import_scrapped', optional($importProcess)->scrapped_at ? 1 : 0) ? 'checked' : '' }}>
                        Abate concluído
                    </label>
                </div>
                <label for="import_scrapped_at">Data da conclusão</label>
                <input class="form-control" type="datetime-local" name="import_scrapped_at" id="import_scrapped_at" value="{{ old('import_scrapped_at', optional(optional($importProcess)->scrapped_at)->format('Y-m-d\TH:i')) }}">
                @if($errors->has('import_scrapped_at'))
                    <span class="help-block">{{ $errors->first('import_scrapped_at') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const legalizeFields = document.getElementById('import-legalize-fields');
        const scrapFields = document.getElementById('import-scrap-fields');
        const decisionAt = document.getElementById('import_decision_at');
        const deadline = document.getElementById('import_deadline_display');

        function selectedDecision() {
            const selected = document.querySelector('input[name="import_decision"]:checked');
            return selected ? selected.value : '';
        }

        function updateImportFields() {
            const decision = selectedDecision();
            legalizeFields.style.display = decision === 'legalize' ? '' : 'none';
            scrapFields.style.display = decision === 'scrap' ? '' : 'none';

            if (!decision || !decisionAt.value) {
                deadline.value = '';
                return;
            }

            const calculated = new Date(decisionAt.value);
            calculated.setDate(calculated.getDate() + (decision === 'legalize' ? 20 : 10));
            deadline.value = calculated.toLocaleDateString('pt-PT');
        }

        document.querySelectorAll('input[name="import_decision"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!decisionAt.value) {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    decisionAt.value = now.toISOString().slice(0, 16);
                }
                updateImportFields();
            });
        });
        decisionAt.addEventListener('change', updateImportFields);
        updateImportFields();

        @if($errors->hasAny(['import_decision', 'import_decision_at', 'import_agency_documents_sent_at', 'import_documents_received_at', 'import_new_license', 'import_new_license_received_at', 'import_scrapped_at']))
            $('a[href="#vehicle-import-adjudication"]').tab('show');
        @endif
    });
</script>

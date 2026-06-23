@php($process = $process ?? null)

<div class="row">
    <div class="col-md-4 form-group {{ $errors->has('vehicle_id') ? 'has-error' : '' }}">
        <label for="vehicle_id">Viatura</label>
        <select class="form-control select2" name="vehicle_id" id="vehicle_id" style="width:100%;">
            <option value="">Indicar só matrícula</option>
            @foreach($vehicles as $id => $label)
                <option value="{{ $id }}" {{ (string) old('vehicle_id', $selectedVehicleId) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if($errors->has('vehicle_id'))<span class="help-block">{{ $errors->first('vehicle_id') }}</span>@endif
    </div>
    <div class="col-md-2 form-group {{ $errors->has('license') ? 'has-error' : '' }}">
        <label for="license">Matrícula</label>
        <input class="form-control" name="license" id="license" value="{{ old('license', $process?->license) }}">
        @if($errors->has('license'))<span class="help-block">{{ $errors->first('license') }}</span>@endif
    </div>
    <div class="col-md-3 form-group {{ $errors->has('insurance_company') ? 'has-error' : '' }}">
        <label for="insurance_company">Seguradora</label>
        <input class="form-control" name="insurance_company" id="insurance_company" value="{{ old('insurance_company', $process?->insurance_company) }}">
        @if($errors->has('insurance_company'))<span class="help-block">{{ $errors->first('insurance_company') }}</span>@endif
    </div>
    <div class="col-md-3 form-group">
        <label for="expert_name">Perito</label>
        <input class="form-control" name="expert_name" id="expert_name" value="{{ old('expert_name', $process?->expert_name) }}">
    </div>
</div>

<div class="row">
    <div class="col-md-3 form-group"><label for="claim_number">N.º sinistro</label><input class="form-control" name="claim_number" id="claim_number" value="{{ old('claim_number', $process?->claim_number) }}"></div>
    <div class="col-md-3 form-group"><label for="process_number">N.º processo</label><input class="form-control" name="process_number" id="process_number" value="{{ old('process_number', $process?->process_number) }}"></div>
    <div class="col-md-3 form-group {{ $errors->has('status') ? 'has-error' : '' }}">
        <label class="required" for="status">Estado</label>
        <select class="form-control" name="status" id="status" required>
            @foreach(\App\Models\OficinaExpertiseProcess::STATUS_SELECT as $key => $label)
                <option value="{{ $key }}" {{ old('status', $process?->status ?: \App\Models\OficinaExpertiseProcess::STATUS_RECEIVED) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if($errors->has('status'))<span class="help-block">{{ $errors->first('status') }}</span>@endif
    </div>
    <div class="col-md-3 form-group">
        <label for="repair_type">Tipo de reparação</label>
        <select class="form-control" name="repair_type" id="repair_type">
            <option value="">Por definir</option>
            @foreach(\App\Models\OficinaExpertiseProcess::REPAIR_TYPE_SELECT as $key => $label)
                <option value="{{ $key }}" {{ old('repair_type', $process?->repair_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-2 form-group"><label for="entry_date">Entrada</label><input class="form-control" type="date" name="entry_date" id="entry_date" value="{{ old('entry_date', optional($process?->entry_date)->format('Y-m-d') ?: now()->toDateString()) }}"></div>
    <div class="col-md-2 form-group"><label for="scheduled_expertise_date">Peritagem</label><input class="form-control" type="datetime-local" name="scheduled_expertise_date" id="scheduled_expertise_date" value="{{ old('scheduled_expertise_date', optional($process?->scheduled_expertise_date)->format('Y-m-d\\TH:i')) }}"></div>
    <div class="col-md-2 form-group"><label for="approval_date">Aprovação</label><input class="form-control" type="datetime-local" name="approval_date" id="approval_date" value="{{ old('approval_date', optional($process?->approval_date)->format('Y-m-d\\TH:i')) }}"></div>
    <div class="col-md-2 form-group"><label for="approved_amount">Valor aprovado</label><input class="form-control" type="number" min="0" step="0.01" name="approved_amount" id="approved_amount" value="{{ old('approved_amount', $process?->approved_amount) }}"></div>
    <div class="col-md-2 form-group"><label for="repair_start_date">Início reparação</label><input class="form-control" type="datetime-local" name="repair_start_date" id="repair_start_date" value="{{ old('repair_start_date', optional($process?->repair_start_date)->format('Y-m-d\\TH:i')) }}"></div>
    <div class="col-md-2 form-group"><label for="expected_repair_date">Previsão conclusão</label><input class="form-control" type="datetime-local" name="expected_repair_date" id="expected_repair_date" value="{{ old('expected_repair_date', optional($process?->expected_repair_date)->format('Y-m-d\\TH:i')) }}"></div>
</div>

<div class="row">
    <div class="col-md-3 form-group"><label for="repair_completed_date">Reparação concluída</label><input class="form-control" type="datetime-local" name="repair_completed_date" id="repair_completed_date" value="{{ old('repair_completed_date', optional($process?->repair_completed_date)->format('Y-m-d\\TH:i')) }}"></div>
    <div class="col-md-3 form-group"><label for="insurance_validation_date">Validação seguradora</label><input class="form-control" type="datetime-local" name="insurance_validation_date" id="insurance_validation_date" value="{{ old('insurance_validation_date', optional($process?->insurance_validation_date)->format('Y-m-d\\TH:i')) }}"></div>
    <div class="col-md-3 form-group"><label for="invoice_sent_date">Fatura enviada</label><input class="form-control" type="datetime-local" name="invoice_sent_date" id="invoice_sent_date" value="{{ old('invoice_sent_date', optional($process?->invoice_sent_date)->format('Y-m-d\\TH:i')) }}"></div>
    <div class="col-md-3 form-group {{ $errors->has('payment_received_date') ? 'has-error' : '' }}">
        <label for="payment_received_date">Pagamento recebido</label>
        <input class="form-control" type="datetime-local" name="payment_received_date" id="payment_received_date" value="{{ old('payment_received_date', optional($process?->payment_received_date)->format('Y-m-d\\TH:i')) }}">
        @if($errors->has('payment_received_date'))<span class="help-block">{{ $errors->first('payment_received_date') }}</span>@endif
    </div>
</div>

<div class="form-group">
    <label for="notes">Observações internas</label>
    <textarea class="form-control" name="notes" id="notes" rows="4">{{ old('notes', $process?->notes) }}</textarea>
</div>
<div class="row">
    <div class="col-md-6 form-group {{ $errors->has('rejection_reason') ? 'has-error' : '' }}">
        <label for="rejection_reason">Motivo de rejeição/cancelamento</label>
        <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3">{{ old('rejection_reason', $process?->rejection_reason) }}</textarea>
        @if($errors->has('rejection_reason'))<span class="help-block">{{ $errors->first('rejection_reason') }}</span>@endif
    </div>
    <div class="col-md-6 form-group">
        <label for="status_notes">Nota para histórico desta alteração</label>
        <textarea class="form-control" name="status_notes" id="status_notes" rows="3">{{ old('status_notes') }}</textarea>
    </div>
</div>

@can('oficina_expertise_process_attachment_create')
    <div class="panel panel-default">
        <div class="panel-heading">Anexos</div>
        <div class="panel-body">
            <div class="row">
                @foreach(\App\Models\OficinaExpertiseProcess::ATTACHMENT_COLLECTIONS as $collection => $label)
                    <div class="col-md-4 form-group {{ $errors->has($collection . '.*') ? 'has-error' : '' }}">
                        <label for="{{ $collection }}">{{ $label }}</label>
                        <input class="form-control" type="file" name="{{ $collection }}[]" id="{{ $collection }}" multiple>
                        @if($errors->has($collection . '.*'))<span class="help-block">{{ $errors->first($collection . '.*') }}</span>@endif
                        @if($process && $process->getMedia($collection)->isNotEmpty())
                            <span class="help-block">{{ $process->getMedia($collection)->count() }} ficheiro(s) já anexado(s).</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endcan

<button class="btn btn-danger" type="submit">Gravar</button>
<a class="btn btn-default" href="{{ route('admin.oficina-expertise-processes.index') }}">Cancelar</a>

@php($service = $externalService)
<div class="row">
    <div class="col-md-4 form-group {{ $errors->has('vehicle_id') ? 'has-error' : '' }}"><label class="required" for="vehicle_id">Matrícula</label><select class="form-control select2" name="vehicle_id" id="vehicle_id" required><option value="">Selecione por favor</option>@foreach($vehicles as $id => $label)<option value="{{ $id }}" {{ (string) old('vehicle_id', $selectedVehicleId) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>@if($errors->has('vehicle_id'))<span class="help-block">{{ $errors->first('vehicle_id') }}</span>@endif</div>
    <div class="col-md-4 form-group"><label for="suplier_id">Fornecedor</label><select class="form-control select2" name="suplier_id" id="suplier_id"><option value="">Por definir</option>@foreach($supliers as $id => $label)<option value="{{ $id }}" {{ (string) old('suplier_id', $service?->suplier_id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-4 form-group {{ $errors->has('invoice_file') ? 'has-error' : '' }}"><label for="invoice_file">Fatura</label><input class="form-control" type="file" name="invoice_file" id="invoice_file" accept=".pdf,.jpg,.jpeg,.png">@if($errors->has('invoice_file'))<span class="help-block">{{ $errors->first('invoice_file') }}</span>@endif @if($service?->invoice_file?->isNotEmpty())<span class="help-block"><a href="{{ $service->invoice_file->first()->getUrl() }}" target="_blank">Ver fatura atual</a>. Um novo ficheiro substitui o atual.</span>@endif</div>
</div>
<div class="row">
    <div class="col-md-8 form-group {{ $errors->has('description') ? 'has-error' : '' }}"><label class="required" for="description">Serviço</label><input class="form-control" name="description" id="description" value="{{ old('description', $service?->description) }}" required>@if($errors->has('description'))<span class="help-block">{{ $errors->first('description') }}</span>@endif</div>
    <div class="col-md-4 form-group"><label for="requested_by_id">Pedido por</label><select class="form-control select2" name="requested_by_id" id="requested_by_id"><option value="">-</option>@foreach($users as $id => $label)<option value="{{ $id }}" {{ (string) old('requested_by_id', $service?->requested_by_id ?: auth()->id()) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
</div>
<div class="row">
    <div class="col-md-2 form-group"><label class="required" for="priority">Prioridade</label><select class="form-control" name="priority" id="priority">@foreach(App\Models\ExternalService::PRIORITY_SELECT as $key => $label)<option value="{{ $key }}" {{ old('priority', $service?->priority ?: 'normal') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2 form-group"><label class="required" for="status">Estado</label><select class="form-control" name="status" id="status">@foreach(App\Models\ExternalService::STATUS_SELECT as $key => $label)<option value="{{ $key }}" {{ old('status', $service?->status ?: 'requested') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2 form-group"><label for="requested_delivery_days">Prazo dias</label><input class="form-control" type="number" min="0" name="requested_delivery_days" id="requested_delivery_days" value="{{ old('requested_delivery_days', $service?->requested_delivery_days) }}"></div>
    <div class="col-md-2 form-group"><label for="expected_date">Data prevista</label><input class="form-control" type="date" name="expected_date" id="expected_date" value="{{ old('expected_date', optional($service?->expected_date)->format('Y-m-d')) }}"></div>
    <div class="col-md-2 form-group"><label for="completed_date">Data real</label><input class="form-control" type="date" name="completed_date" id="completed_date" value="{{ old('completed_date', optional($service?->completed_date)->format('Y-m-d')) }}"></div>
    <div class="col-md-2 form-group"><label for="amount">Valor</label><input class="form-control" type="number" min="0" step="0.01" name="amount" id="amount" value="{{ old('amount', $service?->amount) }}"></div>
</div>
<div class="form-group"><label for="notes">Notas</label><textarea class="form-control" name="notes" id="notes" rows="4">{{ old('notes', $service?->notes) }}</textarea></div>
<button class="btn btn-danger" type="submit">Gravar</button>

@section('scripts')
@parent
<script>
$(function () {
    $('#requested_delivery_days').on('change', function () {
        var days = parseInt(this.value, 10);
        if (!Number.isNaN(days) && !$('#expected_date').val()) {
            var expected = new Date();
            expected.setDate(expected.getDate() + days);
            $('#expected_date').val(expected.toISOString().slice(0, 10));
        }
    });
});
</script>
@endsection

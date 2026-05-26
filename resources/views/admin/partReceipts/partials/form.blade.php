@php($receipt = $partReceipt ?? null)
<div class="row">
    <div class="col-md-4"><div class="form-group"><label class="required">Encomenda</label><select class="form-control select2" name="part_order_id" required><option value="">Selecionar</option>@foreach($partOrders as $id => $label)<option value="{{ $id }}" {{ (string) old('part_order_id', $selectedOrderId) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
    <div class="col-md-3"><div class="form-group"><label>Recebido em</label><input class="form-control" type="datetime-local" name="received_at" value="{{ old('received_at', $receipt && $receipt->received_at ? $receipt->received_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Recebido por</label><select class="form-control select2" name="received_by_id"><option value="">-</option>@foreach($users as $id => $label)<option value="{{ $id }}" {{ (string) old('received_by_id', $receipt->received_by_id ?? auth()->id()) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
    <div class="col-md-2"><div class="form-group"><label>Local</label><input class="form-control" name="received_location" value="{{ old('received_location', $receipt->received_location ?? '') }}"></div></div>
</div>
<div class="form-group"><label>Nome/assinatura</label><input class="form-control" name="signature_name" value="{{ old('signature_name', $receipt->signature_name ?? '') }}"></div>
<div class="form-group"><label>Observações</label><textarea class="form-control" name="observations">{{ old('observations', $receipt->observations ?? '') }}</textarea></div>
<div class="form-group"><label>Anexos</label><input class="form-control" type="file" name="attachments[]" multiple></div>
@if($receipt && $receipt->attachments->count())<p>@foreach($receipt->attachments as $media)<a href="{{ $media->getUrl() }}" target="_blank" class="btn btn-xs btn-default">{{ $media->file_name }}</a> @endforeach</p>@endif
<button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>

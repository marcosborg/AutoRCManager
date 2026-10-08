@php($receipt = $partReceipt ?? null)
@if($receipt)<input type="hidden" name="receipt_revision" value="{{ old('receipt_revision', app(App\Services\PartReceiptService::class)->revision($receipt)) }}">@endif
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>Encomenda</label><p>#{{ $receiptOrder?->id }} — {{ $receiptOrder?->vehicle?->license }}</p><input type="hidden" name="part_order_id" value="{{ $receiptOrder?->id }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Recebido em</label><input class="form-control" type="datetime-local" name="received_at" value="{{ old('received_at', $receipt && $receipt->received_at ? $receipt->received_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Recebido por</label><select class="form-control select2" name="received_by_id"><option value="">-</option>@foreach($users as $id => $label)<option value="{{ $id }}" {{ (string) old('received_by_id', $receipt->received_by_id ?? auth()->id()) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
    <div class="col-md-2"><div class="form-group"><label>Local</label><input class="form-control" name="received_location" value="{{ old('received_location', $receipt->received_location ?? '') }}"></div></div>
</div>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($receipt && $receipt->received_items === null)
    <div class="alert alert-info">Receção antiga, sem quantidades discriminadas. Os dados históricos são preservados.</div>
@else
    <h4>Peças que chegaram nesta entrega</h4>
    <p>Indique apenas as quantidades desta entrega. Deixe a zero o que ainda não chegou.</p>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>Peça</th><th>Encomendado</th><th>Outras entregas</th><th>Falta receber</th><th>Nesta entrega</th></tr></thead>
        <tbody>
        @foreach($receiptOrder?->items ?? [] as $item)
            @php($previousQuantity = collect($receipt?->received_items ?? [])->firstWhere('id', $item->id)['quantity'] ?? 0)
            @php($otherQuantity = max(0, $item->receivedAmount() - $previousQuantity))
            @php($remainingQuantity = max(0, $item->quantity - $otherQuantity))
            <tr><td>{{ $item->description }} <small>{{ $item->reference }}</small></td><td>{{ $item->quantity }}</td><td>{{ $otherQuantity }}</td><td>{{ $remainingQuantity }}</td>
                <td><input aria-label="Quantidade recebida: {{ $item->description }}" class="form-control" type="number" min="0" max="{{ $remainingQuantity }}" step="0.01" name="quantities[{{ $item->id }}]" value="{{ old('quantities.'.$item->id, $previousQuantity) }}" {{ in_array($item->status, ['installed', 'returned']) ? 'readonly' : '' }}></td></tr>
        @endforeach
        </tbody>
    </table></div>
@endif
<div class="form-group"><label>Nome/assinatura</label><input class="form-control" name="signature_name" value="{{ old('signature_name', $receipt->signature_name ?? '') }}"></div>
<div class="form-group"><label>Observações</label><textarea class="form-control" name="observations">{{ old('observations', $receipt->observations ?? '') }}</textarea></div>
<div class="form-group"><label>Anexos</label><input class="form-control" type="file" name="attachments[]" multiple></div>
@if($receipt && $receipt->attachments->count())<p>@foreach($receipt->attachments as $media)<a href="{{ $media->getUrl() }}" target="_blank" class="btn btn-xs btn-default">{{ $media->file_name }}</a> @endforeach</p>@endif
<button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>

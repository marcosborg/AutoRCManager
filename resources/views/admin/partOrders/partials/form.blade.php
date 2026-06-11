@php($order = $partOrder ?? null)
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="repair_id">Reparacao</label>
            <select class="form-control select2" name="repair_id" id="repair_id">
                <option value="">Sem reparacao</option>
                @foreach($repairs as $id => $label)
                    <option value="{{ $id }}" {{ (string) old('repair_id', $selectedRepairId) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="vehicle_id">Viatura</label>
            <select class="form-control select2" name="vehicle_id" id="vehicle_id">
                <option value="">Sem viatura</option>
                @foreach($vehicles as $id => $label)
                    <option value="{{ $id }}" {{ (string) old('vehicle_id', $selectedVehicleId) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <span class="help-block">Se escolher reparacao, a viatura e derivada automaticamente.</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="suplier_id">Fornecedor principal</label>
            <select class="form-control select2" name="suplier_id" id="suplier_id">
                <option value="">Por definir</option>
                @foreach($supliers as $id => $label)
                    <option value="{{ $id }}" {{ (string) old('suplier_id', $order->suplier_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="requested_by_id">Pedido por</label>
            <select class="form-control select2" name="requested_by_id" id="requested_by_id">
                <option value="">-</option>
                @foreach($users as $id => $label)
                    <option value="{{ $id }}" {{ (string) old('requested_by_id', $order->requested_by_id ?? auth()->id()) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="technician_id">Tecnico</label>
            <select class="form-control select2" name="technician_id" id="technician_id">
                <option value="">-</option>
                @foreach($users as $id => $label)
                    <option value="{{ $id }}" {{ (string) old('technician_id', $order->technician_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label class="required" for="priority">Prioridade</label>
            <select class="form-control" name="priority" id="priority" required>
                @foreach(App\Models\PartOrder::PRIORITY_SELECT as $key => $label)
                    <option value="{{ $key }}" {{ old('priority', $order->priority ?? 'normal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label class="required" for="status">Estado</label>
            <select class="form-control" name="status" id="status" required>
                @foreach(App\Models\PartOrder::STATUS_SELECT as $key => $label)
                    <option value="{{ $key }}" {{ old('status', $order->status ?? 'draft') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="requested_delivery_days">Prazo dias</label>
            <input class="form-control" type="number" min="0" name="requested_delivery_days" id="requested_delivery_days" value="{{ old('requested_delivery_days', $order->requested_delivery_days ?? '') }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="expected_delivery_date">Data prevista</label>
            <input class="form-control" type="date" name="expected_delivery_date" id="expected_delivery_date" value="{{ old('expected_delivery_date', optional($order?->expected_delivery_date)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="actual_delivery_date">Data real</label>
            <input class="form-control" type="date" name="actual_delivery_date" id="actual_delivery_date" value="{{ old('actual_delivery_date', optional($order?->actual_delivery_date)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="notes">Notas</label>
            <textarea class="form-control" name="notes" id="notes" rows="2">{{ old('notes', $order->notes ?? '') }}</textarea>
        </div>
    </div>
</div>
<hr>
<h4>Peças pedidas</h4>
<div class="table-responsive">
    <table class="table table-bordered" id="part-order-items-table">
        <thead>
            <tr>
                <th>Referencia</th>
                <th>Descricao</th>
                <th>Qtd</th>
                <th>IVA %</th>
                <th>Estado</th>
                <th>Obs.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @php($items = old('items', $order ? $order->items->toArray() : [['description' => '', 'quantity' => 1, 'status' => 'pending']]))
            @foreach($items as $index => $item)
                <tr>
                    <td><input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}"><input class="form-control" name="items[{{ $index }}][reference]" value="{{ $item['reference'] ?? '' }}"></td>
                    <td><input class="form-control" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}"></td>
                    <td><input class="form-control" type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}"></td>
                    <td><input class="form-control" type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][iva_percentage]" value="{{ $item['iva_percentage'] ?? '' }}"></td>
                    <td>
                        <select class="form-control" name="items[{{ $index }}][status]">
                            @foreach(App\Models\PartOrderItem::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}" {{ ($item['status'] ?? 'pending') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input class="form-control" name="items[{{ $index }}][observations]" value="{{ $item['observations'] ?? '' }}"></td>
                    <td><button type="button" class="btn btn-xs btn-danger remove-item-row">X</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<button type="button" class="btn btn-default btn-sm" id="add-item-row">Adicionar peça</button>
<div class="form-group" style="margin-top:15px;">
    <button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>
</div>

@section('scripts')
@parent
<script>
$(function () {
    var itemIndex = {{ count($items) }};
    $('#add-item-row').on('click', function () {
        var options = `{!! collect(App\Models\PartOrderItem::STATUS_SELECT)->map(fn($label, $key) => '<option value="'.$key.'">'.$label.'</option>')->implode('') !!}`;
        $('#part-order-items-table tbody').append(
            '<tr>' +
            '<td><input class="form-control" name="items[' + itemIndex + '][reference]"></td>' +
            '<td><input class="form-control" name="items[' + itemIndex + '][description]"></td>' +
            '<td><input class="form-control" type="number" step="0.01" min="0.01" name="items[' + itemIndex + '][quantity]" value="1"></td>' +
            '<td><input class="form-control" type="number" step="0.01" min="0" max="100" name="items[' + itemIndex + '][iva_percentage]"></td>' +
            '<td><select class="form-control" name="items[' + itemIndex + '][status]">' + options + '</select></td>' +
            '<td><input class="form-control" name="items[' + itemIndex + '][observations]"></td>' +
            '<td><button type="button" class="btn btn-xs btn-danger remove-item-row">X</button></td>' +
            '</tr>'
        );
        itemIndex++;
    });
    $(document).on('click', '.remove-item-row', function () {
        $(this).closest('tr').remove();
    });
});
</script>
@endsection

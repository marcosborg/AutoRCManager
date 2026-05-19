@php
    $selectedVehicles = old('vehicles', $vehicleGroup ? $vehicleGroup->vehicles->pluck('id')->toArray() : []);
    $itemsByVehicle = $vehicleGroup ? $vehicleGroup->items->keyBy('vehicle_id') : collect();
    $lotType = old('type', $vehicleGroup->type ?? 'lote');
    $subtotal = old('total_amount', $vehicleGroup->total_amount ?? $vehicleGroup->wholesale_pvp ?? 0);
    $vehicleLabels = collect($vehicles)->map(fn ($label) => (string) $label);
    $itemPrices = $itemsByVehicle->mapWithKeys(fn ($item, $vehicleId) => [$vehicleId => $item->adjusted_price]);
    $itemRegistrationAmounts = $itemsByVehicle->mapWithKeys(fn ($item, $vehicleId) => [$vehicleId => $item->registration_amount]);
    $itemTowAmounts = $itemsByVehicle->mapWithKeys(fn ($item, $vehicleId) => [$vehicleId => $item->tow_amount]);
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
            <label class="required" for="name">Nome</label>
            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $vehicleGroup->name ?? '') }}" required>
            @if($errors->has('name'))<span class="help-block">{{ $errors->first('name') }}</span>@endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group {{ $errors->has('customer_id') ? 'has-error' : '' }}">
            <label for="customer_id">Cliente principal</label>
            <select class="form-control select2" name="customer_id" id="customer_id">
                @foreach($clients as $id => $client)
                    <option value="{{ $id }}" {{ (string) old('customer_id', $vehicleGroup->customer_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $client }}</option>
                @endforeach
            </select>
            @if($errors->has('customer_id'))<span class="help-block">{{ $errors->first('customer_id') }}</span>@endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group {{ $errors->has('type') ? 'has-error' : '' }}">
            <label class="required" for="type">Tipo</label>
            <select class="form-control" name="type" id="type" required>
                <option value="lote" {{ $lotType === 'lote' ? 'selected' : '' }}>Lote global</option>
                <option value="unitario" {{ $lotType === 'unitario' ? 'selected' : '' }}>Lote discriminado</option>
            </select>
            @if($errors->has('type'))<span class="help-block">{{ $errors->first('type') }}</span>@endif
        </div>
    </div>
    <div class="col-md-5">
        <div class="form-group {{ $errors->has('total_amount') ? 'has-error' : '' }}">
            <label class="required" for="total_amount">Subtotal venda</label>
            <input class="form-control" type="number" name="total_amount" id="total_amount" value="{{ $subtotal }}" step="0.01" min="0">
            @if($errors->has('total_amount'))<span class="help-block">{{ $errors->first('total_amount') }}</span>@endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Total lote</label>
            <input class="form-control" type="text" id="lot_total_preview" value="" disabled>
        </div>
    </div>
</div>

<div class="form-group {{ $errors->has('vehicles') ? 'has-error' : '' }}">
    <label for="vehicles">Viaturas</label>
    <select class="form-control select2" name="vehicles[]" id="vehicles" multiple>
        @foreach($vehicles as $id => $vehicle)
            <option value="{{ $id }}" {{ in_array($id, $selectedVehicles) ? 'selected' : '' }}>{{ $vehicle }}</option>
        @endforeach
    </select>
    @if($errors->has('vehicles'))<span class="help-block">{{ $errors->first('vehicles') }}</span>@endif
</div>

<input type="hidden" name="distribution_mode" value="global">

<div class="panel panel-default">
    <div class="panel-heading">Viaturas selecionadas</div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Viatura</th>
                        <th class="lot-item-price-heading">Preco da viatura</th>
                        <th>Registo</th>
                        <th>Reboque</th>
                    </tr>
                </thead>
                <tbody id="selected-vehicles-table">
                    <tr>
                        <td colspan="4" class="text-muted">Selecione viaturas para as associar ao lote.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @if($errors->has('items.*.adjusted_price'))
            <span class="help-block text-danger">{{ $errors->first('items.*.adjusted_price') }}</span>
        @endif
    </div>
</div>

<div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
    <label for="notes">Observacoes</label>
    <textarea class="form-control" name="notes" id="notes">{{ old('notes', $vehicleGroup->notes ?? '') }}</textarea>
    @if($errors->has('notes'))<span class="help-block">{{ $errors->first('notes') }}</span>@endif
</div>

<div class="form-group">
    <button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>
    <a class="btn btn-default" href="{{ route('admin.vehicle-groups.index') }}">{{ trans('global.cancel') }}</a>
</div>

@section('scripts')
@parent
<script>
    $(function () {
        const vehicleLabels = @json($vehicleLabels);
        const existingPrices = @json($itemPrices);
        const existingRegistrationAmounts = @json($itemRegistrationAmounts);
        const existingTowAmounts = @json($itemTowAmounts);
        const oldPrices = @json(old('items', []));
        const table = $('#selected-vehicles-table');
        const typeField = $('#type');
        const subtotalField = $('#total_amount');
        const totalPreview = $('#lot_total_preview');

        function parseAmount(value) {
            const parsed = parseFloat(String(value || '').replace(',', '.'));
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function formatAmount(value) {
            return value.toFixed(2).replace('.', ',');
        }

        function itemPriceFor(vehicleId) {
            if (oldPrices[vehicleId] && oldPrices[vehicleId].adjusted_price !== undefined) {
                return oldPrices[vehicleId].adjusted_price;
            }

            return existingPrices[vehicleId] ?? '';
        }

        function itemRegistrationFor(vehicleId) {
            if (oldPrices[vehicleId] && oldPrices[vehicleId].registration_amount !== undefined) {
                return oldPrices[vehicleId].registration_amount;
            }

            return existingRegistrationAmounts[vehicleId] ?? '';
        }

        function itemTowFor(vehicleId) {
            if (oldPrices[vehicleId] && oldPrices[vehicleId].tow_amount !== undefined) {
                return oldPrices[vehicleId].tow_amount;
            }

            return existingTowAmounts[vehicleId] ?? '';
        }

        function isDiscriminated() {
            return typeField.val() === 'unitario';
        }

        function renderRows() {
            const selected = $('#vehicles').val() || [];
            const currentValues = {};

            $('.lot-item-field').each(function () {
                const match = ($(this).attr('name') || '').match(/^items\[(\d+)\]\[(adjusted_price|registration_amount|tow_amount)\]$/);
                if (match) {
                    currentValues[match[1]] = currentValues[match[1]] || {};
                    currentValues[match[1]][match[2]] = $(this).val();
                }
            });

            table.empty();

            if (selected.length === 0) {
                table.append('<tr><td colspan="' + (isDiscriminated() ? 4 : 3) + '" class="text-muted">Selecione viaturas para as associar ao lote.</td></tr>');
                updateTotals();
                return;
            }

            selected.forEach(function (vehicleId) {
                const label = $('<div>').text(vehicleLabels[vehicleId] || ('Viatura #' + vehicleId)).html();
                const price = $('<div>').text(currentValues[vehicleId]?.adjusted_price ?? itemPriceFor(vehicleId)).html();
                const registrationAmount = $('<div>').text(currentValues[vehicleId]?.registration_amount ?? itemRegistrationFor(vehicleId)).html();
                const towAmount = $('<div>').text(currentValues[vehicleId]?.tow_amount ?? itemTowFor(vehicleId)).html();
                const priceCell = isDiscriminated()
                    ? '<td><input class="form-control lot-item-field lot-item-price" type="number" name="items[' + vehicleId + '][adjusted_price]" value="' + price + '" step="0.01" min="0" required></td>'
                    : '';
                const registrationCell = '<td><input class="form-control lot-item-field lot-item-registration" type="number" name="items[' + vehicleId + '][registration_amount]" value="' + registrationAmount + '" step="0.01" min="0"></td>';
                const towCell = '<td><input class="form-control lot-item-field lot-item-tow" type="number" name="items[' + vehicleId + '][tow_amount]" value="' + towAmount + '" step="0.01" min="0"></td>';

                table.append('<tr><td>' + label + '</td>' + priceCell + registrationCell + towCell + '</tr>');
            });

            updateTotals();
        }

        function updateTotals() {
            let subtotal = parseAmount(subtotalField.val());

            if (isDiscriminated()) {
                subtotal = 0;
                $('.lot-item-price').each(function () {
                    subtotal += parseAmount($(this).val());
                });
                subtotalField.val(subtotal.toFixed(2));
                subtotalField.prop('readonly', true);
            } else {
                subtotalField.prop('readonly', false);
            }

            $('.lot-item-price-heading').toggle(isDiscriminated());

            let registrationTotal = 0;
            let towTotal = 0;
            $('.lot-item-registration').each(function () {
                registrationTotal += parseAmount($(this).val());
            });
            $('.lot-item-tow').each(function () {
                towTotal += parseAmount($(this).val());
            });

            const total = subtotal + registrationTotal + towTotal;
            totalPreview.val(formatAmount(total));
        }

        $('#vehicles').on('change', renderRows);
        typeField.on('change', renderRows);
        subtotalField.on('input', updateTotals);
        table.on('input', '.lot-item-field', updateTotals);

        renderRows();
    });
</script>
@endsection

@php
    $selectedVehicles = old('vehicles', $vehicleGroup ? $vehicleGroup->vehicles->pluck('id')->toArray() : []);
    $itemsByVehicle = $vehicleGroup ? $vehicleGroup->items->keyBy('vehicle_id') : collect();
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
                <option value="lote" {{ old('type', $vehicleGroup->type ?? 'lote') === 'lote' ? 'selected' : '' }}>Lote global</option>
                <option value="unitario" {{ old('type', $vehicleGroup->type ?? '') === 'unitario' ? 'selected' : '' }}>Discriminado</option>
            </select>
            @if($errors->has('type'))<span class="help-block">{{ $errors->first('type') }}</span>@endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group {{ $errors->has('total_amount') ? 'has-error' : '' }}">
            <label for="total_amount">Valor global</label>
            <input class="form-control" type="number" name="total_amount" id="total_amount" value="{{ old('total_amount', $vehicleGroup->total_amount ?? $vehicleGroup->wholesale_pvp ?? '') }}" step="0.01" min="0">
            @if($errors->has('total_amount'))<span class="help-block">{{ $errors->first('total_amount') }}</span>@endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group {{ $errors->has('distribution_mode') ? 'has-error' : '' }}">
            <label class="required" for="distribution_mode">Distribuicao</label>
            <select class="form-control" name="distribution_mode" id="distribution_mode" required>
                <option value="proportional" {{ old('distribution_mode', $vehicleGroup->distribution_mode ?? 'proportional') === 'proportional' ? 'selected' : '' }}>Proporcional</option>
                <option value="equal" {{ old('distribution_mode', $vehicleGroup->distribution_mode ?? '') === 'equal' ? 'selected' : '' }}>Igual</option>
            </select>
            @if($errors->has('distribution_mode'))<span class="help-block">{{ $errors->first('distribution_mode') }}</span>@endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Estado</label>
            <input class="form-control" type="text" value="{{ $vehicleGroup->status ?? 'open' }}" disabled>
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

<div class="panel panel-default">
    <div class="panel-heading">Valores por viatura selecionada</div>
    <div class="panel-body">
        <p class="text-muted">Preencha valores individuais quando o lote for discriminado ou quando quiser controlar o desconto. Viaturas sem linha usam o PVP atual como preco original.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Viatura</th>
                        <th>Preco original</th>
                        <th>Preco ajustado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $id => $label)
                        @php($item = $itemsByVehicle->get($id))
                        <tr>
                            <td>{{ $label }}</td>
                            <td><input class="form-control" type="number" name="items[{{ $id }}][original_price]" value="{{ old('items.' . $id . '.original_price', $item->original_price ?? '') }}" step="0.01" min="0"></td>
                            <td><input class="form-control" type="number" name="items[{{ $id }}][adjusted_price]" value="{{ old('items.' . $id . '.adjusted_price', $item->adjusted_price ?? '') }}" step="0.01" min="0"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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

@php
    $selectedPurchasingSupplier = old('our_registration', isset($vehicle) ? $vehicle->our_registration : null);
    $availablePurchasingSuppliers = collect($purchasingSuppliers ?? []);

    if ($selectedPurchasingSupplier && !$availablePurchasingSuppliers->has($selectedPurchasingSupplier)) {
        $availablePurchasingSuppliers->put($selectedPurchasingSupplier, $selectedPurchasingSupplier);
    }
@endphp
<div class="form-group {{ $errors->has('our_registration') ? 'has-error' : '' }}">
    <label for="our_registration">{{ trans('cruds.vehicle.fields.our_registration') }}</label>
    <div class="input-group">
        <select class="form-control" name="our_registration" id="our_registration">
            <option value></option>
            @foreach($availablePurchasingSuppliers as $value => $name)
                <option value="{{ $value }}" {{ $selectedPurchasingSupplier === $value ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @can('suplier_create')
            <span class="input-group-btn">
                <a class="btn btn-default" href="{{ route('admin.supliers.create') }}" target="_blank" rel="noopener">Novo fornecedor</a>
            </span>
        @endcan
    </div>
    @if($errors->has('our_registration'))
        <span class="help-block" role="alert">{{ $errors->first('our_registration') }}</span>
    @endif
    <span class="help-block">A lista é gerida em Configurações → Fornecedores. Depois de criar um fornecedor, atualize esta página.</span>
</div>

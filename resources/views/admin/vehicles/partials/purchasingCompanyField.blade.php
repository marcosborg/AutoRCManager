<div class="form-group {{ $errors->has('destination_company') ? 'has-error' : '' }}">
    <label for="destination_company">Empresa de destino</label>
    <select class="form-control" name="destination_company" id="destination_company">
        <option value="">Por confirmar</option>
        @foreach(\App\Models\Vehicle::DESTINATION_COMPANIES as $value => $name)
            <option value="{{ $value }}" {{ old('destination_company', isset($vehicle) ? $vehicle->destination_company : null) === $value ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    @error('destination_company')
        <span class="help-block" role="alert">{{ $message }}</span>
    @enderror
    <span class="help-block">Empresa do grupo a que a viatura se destina. Indique a quem foi comprada no campo Fornecedor.</span>
    @can('suplier_create')
        <a href="{{ route('admin.supliers.create') }}" target="_blank" rel="noopener">Novo fornecedor</a>
    @endcan
    @if(isset($vehicle) && $vehicle->our_registration)
        <p class="help-block"><strong>Registo anterior de empresa/fornecedor:</strong> {{ $vehicle->our_registration }}. Mantido para consulta; confirme a empresa de destino acima.</p>
    @endif
</div>
@unless(isset($vehicle))
    <div class="form-group {{ $errors->has('suplier_id') ? 'has-error' : '' }}">
        <label for="suplier_id">Fornecedor (a quem foi comprada)</label>
        <select class="form-control select2" name="suplier_id" id="suplier_id">
            @foreach($supliers as $id => $name)
                <option value="{{ $id }}" {{ (string) old('suplier_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @error('suplier_id')
            <span class="help-block" role="alert">{{ $message }}</span>
        @enderror
    </div>
@endunless

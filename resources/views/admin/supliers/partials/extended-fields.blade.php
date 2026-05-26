@php($suplier = $suplier ?? null)
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $suplier->email ?? '') }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Telefone</label><input class="form-control" name="phone" value="{{ old('phone', $suplier->phone ?? '') }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Telemóvel</label><input class="form-control" name="mobile" value="{{ old('mobile', $suplier->mobile ?? '') }}"></div></div>
</div>
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>NIF</label><input class="form-control" name="nif" value="{{ old('nif', $suplier->nif ?? '') }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Prazo médio entrega (dias)</label><input class="form-control" type="number" min="0" name="average_delivery_days" value="{{ old('average_delivery_days', $suplier->average_delivery_days ?? '') }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label style="display:block;">Ativo</label><input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" {{ old('active', $suplier->active ?? true) ? 'checked' : '' }}> Sim</label></div></div>
</div>
<div class="form-group"><label>Morada</label><textarea class="form-control" name="address">{{ old('address', $suplier->address ?? '') }}</textarea></div>
<div class="form-group"><label>Notas</label><textarea class="form-control" name="notes">{{ old('notes', $suplier->notes ?? '') }}</textarea></div>

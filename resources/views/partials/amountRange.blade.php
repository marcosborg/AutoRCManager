<label for="amount_min">PVP mínimo (€)</label>
<input class="form-control" id="amount_min" name="amount_min" type="number" min="0" step="0.01" value="{{ request('amount_min') }}" placeholder="Sem mínimo">
<label for="amount_max">PVP máximo (€)</label>
<input class="form-control" id="amount_max" name="amount_max" type="number" min="0" step="0.01" value="{{ request('amount_max') }}" placeholder="Sem máximo">
@error('amount_min')<span class="text-danger">{{ $message }}</span>@enderror
@error('amount_max')<span class="text-danger">{{ $message }}</span>@enderror

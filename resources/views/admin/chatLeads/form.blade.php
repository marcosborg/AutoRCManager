<div class="row">
    <div class="col-md-6"><div class="form-group"><label>Nome</label><input class="form-control" name="name" value="{{ old('name', $chatLead->name) }}"></div></div>
    <div class="col-md-6"><div class="form-group"><label>Telefone</label><input class="form-control" name="phone" value="{{ old('phone', $chatLead->phone) }}"></div></div>
</div>
<div class="row">
    <div class="col-md-6"><div class="form-group"><label>Email</label><input class="form-control" name="email" value="{{ old('email', $chatLead->email) }}"></div></div>
    <div class="col-md-6"><div class="form-group"><label>Canal</label><select class="form-control" name="channel_id">@foreach($channels as $id => $name)<option value="{{ $id }}" {{ (string) old('channel_id', $chatLead->channel_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div></div>
</div>
<div class="form-group"><label>Viatura/interesse</label><input class="form-control" name="vehicle_title" value="{{ old('vehicle_title', $chatLead->vehicle_title) }}"></div>
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>Orçamento</label><input class="form-control" name="budget_max" value="{{ old('budget_max', $chatLead->budget_max) }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Prioridade</label><select class="form-control" name="priority">@foreach($priorities as $value => $label)<option value="{{ $value }}" {{ old('priority', $chatLead->priority) === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
    <div class="col-md-4"><div class="form-group"><label>Estado</label><select class="form-control" name="status">@foreach($statuses as $value => $label)<option value="{{ $value }}" {{ old('status', $chatLead->status) === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
</div>
<div class="form-group"><label>Vendedor</label><select class="form-control" name="assigned_to">@foreach($users as $id => $name)<option value="{{ $id }}" {{ (string) old('assigned_to', $chatLead->assigned_to) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
<div class="form-group"><label><input type="checkbox" name="wants_financing" value="1" {{ old('wants_financing', $chatLead->wants_financing) ? 'checked' : '' }}> Quer financiamento</label></div>
<div class="form-group"><label><input type="checkbox" name="has_trade_in" value="1" {{ old('has_trade_in', $chatLead->has_trade_in) ? 'checked' : '' }}> Tem retoma</label></div>
<div class="form-group"><label>Resumo</label><textarea class="form-control" name="summary" rows="5">{{ old('summary', $chatLead->summary) }}</textarea></div>
<div class="form-group"><label>Notas IA</label><textarea class="form-control" name="ai_notes" rows="5">{{ old('ai_notes', $chatLead->ai_notes) }}</textarea></div>
<button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>

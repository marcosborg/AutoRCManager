@php($item = $aiAssistant)
<div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
    <label class="required" for="name">Nome</label>
    <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $item->name ?? '') }}" required>
    @if($errors->has('name'))<span class="help-block">{{ $errors->first('name') }}</span>@endif
</div>
<div class="form-group {{ $errors->has('slug') ? 'has-error' : '' }}">
    <label class="required" for="slug">Slug</label>
    <input class="form-control" type="text" name="slug" id="slug" value="{{ old('slug', $item->slug ?? 'carsete') }}" required>
    @if($errors->has('slug'))<span class="help-block">{{ $errors->first('slug') }}</span>@endif
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="company_name">Empresa</label>
            <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name', $item->company_name ?? config('ai_assistant.company_name')) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="commercial_phone">Telefone comercial</label>
            <input class="form-control" type="text" name="commercial_phone" id="commercial_phone" value="{{ old('commercial_phone', $item->commercial_phone ?? config('ai_assistant.commercial_phone')) }}">
        </div>
    </div>
</div>
@foreach(['system_prompt' => 'Prompt base', 'rules' => 'Regras', 'allowed_topics' => 'Temas permitidos', 'forbidden_topics' => 'Temas proibidos', 'escalation_rules' => 'Regras de escalamento'] as $field => $label)
    <div class="form-group">
        <label for="{{ $field }}">{{ $label }}</label>
        <textarea class="form-control" name="{{ $field }}" id="{{ $field }}" rows="4">{{ old($field, $item->{$field} ?? '') }}</textarea>
    </div>
@endforeach
<div class="form-group">
    <label for="default_language">Idioma</label>
    <input class="form-control" type="text" name="default_language" id="default_language" value="{{ old('default_language', $item->default_language ?? 'pt-PT') }}">
</div>
<div class="form-group">
    <label><input type="checkbox" name="active" value="1" {{ old('active', $item->active ?? true) ? 'checked' : '' }}> Ativo</label>
</div>
<div class="form-group">
    <button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button>
</div>

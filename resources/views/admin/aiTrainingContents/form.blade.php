@php($item = $content)
<div class="form-group">
    <label for="assistant_id">Assistente</label>
    <select class="form-control" name="assistant_id" id="assistant_id">
        @foreach($assistants as $id => $name)
            <option value="{{ $id }}" {{ (string) old('assistant_id', $item->assistant_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label class="required" for="title">Título</label>
    <input class="form-control" type="text" name="title" id="title" value="{{ old('title', $item->title ?? '') }}" required>
</div>
<div class="form-group">
    <label class="required" for="type">Tipo</label>
    <select class="form-control" name="type" id="type" required>
        @foreach($types as $value => $label)
            <option value="{{ $value }}" {{ old('type', $item->type ?? 'instruction') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label class="required" for="content">Conteúdo</label>
    <textarea class="form-control" name="content" id="content" rows="10" required>{{ old('content', $item->content ?? '') }}</textarea>
</div>
<div class="form-group">
    <label for="sort_order">Ordem</label>
    <input class="form-control" type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $item->sort_order ?? '') }}">
</div>
<div class="form-group">
    <label><input type="checkbox" name="active" value="1" {{ old('active', $item->active ?? true) ? 'checked' : '' }}> Ativo</label>
</div>
<div class="form-group"><button class="btn btn-danger" type="submit">{{ trans('global.save') }}</button></div>

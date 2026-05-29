<div class="form-group">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="input-group">
        <select class="form-control select2" name="{{ $name }}" id="{{ $id }}">
            <option value="">Selecione</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" {{ (string) $oldValue === (string) $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" data-toggle="modal" data-target="{{ $modalTarget }}">Nova</button>
        </span>
    </div>
</div>

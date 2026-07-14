@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">Alertas de Importação / Adjudicação</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.import-configuration.tolls-recipient.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group {{ $errors->has('user_ids') || $errors->has('user_ids.*') ? 'has-error' : '' }}">
                            <label class="required" for="tolls-recipient-users">Responsáveis pelos alertas</label>
                            <select class="form-control select2" name="user_ids[]" id="tolls-recipient-users" multiple required>
                                @foreach($users as $id => $name)
                                    <option value="{{ $id }}" {{ collect(old('user_ids', $selectedTollsRecipientIds))->contains(fn ($selectedId) => (string) $selectedId === (string) $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('user_ids'))
                                <span class="help-block">{{ $errors->first('user_ids') }}</span>
                            @endif
                            <p class="help-block">Todos os utilizadores selecionados recebem individualmente cada tarefa.</p>
                        </div>
                        <button class="btn btn-danger" type="submit">Gravar responsáveis</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

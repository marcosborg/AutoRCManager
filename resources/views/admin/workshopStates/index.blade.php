@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Estados da Oficina</div>
        <div class="panel-body">
            @can('workshop_state_create')
                <form method="POST" action="{{ route('admin.workshop-states.store') }}" class="form-inline" style="margin-bottom: 20px;">
                    @csrf
                    <div class="form-group">
                        <label class="sr-only" for="new-workshop-state-name">Nome</label>
                        <input class="form-control" id="new-workshop-state-name" name="name" placeholder="Novo estado" required>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="new-workshop-state-position">Ordem</label>
                        <input class="form-control" id="new-workshop-state-position" name="position" type="number" min="0" value="0" placeholder="Ordem">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                    <button class="btn btn-success" type="submit">Adicionar</button>
                </form>
            @endcan

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Ordem</th>
                            <th>Ativo</th>
                            <th>Predefinido</th>
                            <th>Viaturas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workshopStates as $workshopState)
                            <tr>
                                    <td><input class="form-control" form="workshop-state-update-{{ $workshopState->id }}" name="name" value="{{ $workshopState->name }}" required></td>
                                    <td><input class="form-control" form="workshop-state-update-{{ $workshopState->id }}" name="position" type="number" min="0" value="{{ $workshopState->position }}"></td>
                                    <td>
                                        <input form="workshop-state-update-{{ $workshopState->id }}" type="hidden" name="is_active" value="0">
                                        <input form="workshop-state-update-{{ $workshopState->id }}" type="checkbox" name="is_active" value="1" {{ $workshopState->is_active ? 'checked' : '' }} {{ $workshopState->is_default ? 'disabled' : '' }}>
                                        @if($workshopState->is_default)<input form="workshop-state-update-{{ $workshopState->id }}" type="hidden" name="is_active" value="1">@endif
                                    </td>
                                    <td>
                                        <input form="workshop-state-update-{{ $workshopState->id }}" type="hidden" name="is_default" value="0">
                                        <input form="workshop-state-update-{{ $workshopState->id }}" type="checkbox" name="is_default" value="1" {{ $workshopState->is_default ? 'checked disabled' : '' }}>
                                        @if($workshopState->is_default)<input form="workshop-state-update-{{ $workshopState->id }}" type="hidden" name="is_default" value="1">@endif
                                    </td>
                                    <td>{{ $workshopState->vehicles_count }}</td>
                                    <td>
                                        @can('workshop_state_edit')
                                            <form id="workshop-state-update-{{ $workshopState->id }}" method="POST" action="{{ route('admin.workshop-states.update', $workshopState) }}" style="display:inline;">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-xs btn-primary" type="submit">Guardar</button>
                                            </form>
                                        @endcan
                                        @can('workshop_state_delete')
                                            @if(!$workshopState->is_default)
                                                <form method="POST" action="{{ route('admin.workshop-states.destroy', $workshopState) }}" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-xs btn-danger" type="submit" onclick="return confirm('Eliminar ou desativar este estado?')">
                                                        {{ $workshopState->vehicles_count > 0 ? 'Desativar' : 'Eliminar' }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

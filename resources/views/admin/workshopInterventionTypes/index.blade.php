@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Tipos de intervenção</div>
        <div class="panel-body">
            @can('workshop_intervention_type_create')
                <form method="POST" action="{{ route('admin.workshop-intervention-types.store') }}" class="form-inline" style="margin-bottom:20px">
                    @csrf
                    <div class="form-group"><label class="sr-only">Nome</label><input class="form-control" name="name" placeholder="Novo tipo" required></div>
                    <button class="btn btn-success">Adicionar</button>
                </form>
            @endcan
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <table class="table table-bordered">
                <thead><tr><th>Nome</th><th>Ativo</th><th>Utilizações</th><th></th></tr></thead>
                <tbody>
                    @foreach($types as $type)
                        <tr>
                            <td><input class="form-control" name="name" value="{{ $type->name }}" required form="type-update-{{ $type->id }}"></td>
                            <td><label><input type="checkbox" name="is_active" value="1" {{ $type->is_active ? 'checked' : '' }} form="type-update-{{ $type->id }}"> Ativo</label></td>
                            <td>{{ $type->interventions_count }}</td>
                            <td style="white-space:nowrap">
                                @can('workshop_intervention_type_edit')
                                    <form id="type-update-{{ $type->id }}" method="POST" action="{{ route('admin.workshop-intervention-types.update', $type) }}" style="display:inline">@csrf @method('PUT')<button class="btn btn-xs btn-primary">Gravar</button></form>
                                @endcan
                                @can('workshop_intervention_type_delete')
                                    <form method="POST" action="{{ route('admin.workshop-intervention-types.destroy', $type) }}" style="display:inline" onsubmit="return confirm('Eliminar este tipo?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Eliminar</button></form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

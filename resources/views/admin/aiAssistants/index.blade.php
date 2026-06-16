@extends('layouts.admin')
@section('content')
<div class="content">
    @can('ai_assistant_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.ai-assistants.create') }}">Adicionar assistente</a>
            </div>
        </div>
    @endcan
    <div class="panel panel-default">
        <div class="panel-heading">Assistentes IA</div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Empresa</th>
                        <th>Telefone</th>
                        <th>Ativo</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aiAssistants as $assistant)
                        <tr>
                            <td>{{ $assistant->id }}</td>
                            <td>{{ $assistant->name }}</td>
                            <td>{{ $assistant->slug }}</td>
                            <td>{{ $assistant->company_name }}</td>
                            <td>{{ $assistant->commercial_phone }}</td>
                            <td>{{ $assistant->active ? 'Sim' : 'Não' }}</td>
                            <td>
                                @can('ai_assistant_show')<a class="btn btn-xs btn-primary" href="{{ route('admin.ai-assistants.show', $assistant) }}">{{ trans('global.view') }}</a>@endcan
                                @can('ai_assistant_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.ai-assistants.edit', $assistant) }}">{{ trans('global.edit') }}</a>@endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $aiAssistants->links() }}
        </div>
    </div>
</div>
@endsection

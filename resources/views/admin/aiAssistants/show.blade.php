@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Assistente IA</div>
        <div class="panel-body">
            <table class="table table-bordered table-striped">
                <tr><th>ID</th><td>{{ $aiAssistant->id }}</td></tr>
                <tr><th>Nome</th><td>{{ $aiAssistant->name }}</td></tr>
                <tr><th>Slug</th><td>{{ $aiAssistant->slug }}</td></tr>
                <tr><th>Empresa</th><td>{{ $aiAssistant->company_name }}</td></tr>
                <tr><th>Telefone</th><td>{{ $aiAssistant->commercial_phone }}</td></tr>
                <tr><th>Ativo</th><td>{{ $aiAssistant->active ? 'Sim' : 'Não' }}</td></tr>
            </table>
            <a class="btn btn-default" href="{{ route('admin.ai-assistants.index') }}">{{ trans('global.back_to_list') }}</a>
        </div>
    </div>
</div>
@endsection

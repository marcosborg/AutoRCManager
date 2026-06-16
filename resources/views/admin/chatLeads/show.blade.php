@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Lead do Assistente</div><div class="panel-body">
    <table class="table table-bordered table-striped">
        <tr><th>ID</th><td>{{ $chatLead->id }}</td></tr>
        <tr><th>Nome</th><td>{{ $chatLead->name }}</td></tr>
        <tr><th>Telefone</th><td>{{ $chatLead->phone }}</td></tr>
        <tr><th>Email</th><td>{{ $chatLead->email }}</td></tr>
        <tr><th>Canal</th><td>{{ $chatLead->channel->name ?? '' }}</td></tr>
        <tr><th>Viatura</th><td>{{ $chatLead->vehicle_title }}</td></tr>
        <tr><th>Resumo</th><td><pre style="white-space: pre-wrap">{{ $chatLead->summary }}</pre></td></tr>
    </table>
    <h4>Conversas</h4>
    @foreach($chatLead->conversations as $conversation)
        <p><a href="{{ route('admin.chat-conversations.show', $conversation) }}">Conversa #{{ $conversation->id }}</a> - {{ $conversation->status }} - {{ $conversation->messages->count() }} mensagens</p>
    @endforeach
    <a class="btn btn-default" href="{{ route('admin.chat-leads.index') }}">{{ trans('global.back_to_list') }}</a>
</div></div></div>
@endsection

@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Conversas IA</div><div class="panel-body">
    <table class="table table-bordered table-striped table-hover">
        <thead><tr><th>ID</th><th>Lead</th><th>Telefone</th><th>Canal</th><th>Estado</th><th>Humano</th><th>Última mensagem</th><th>&nbsp;</th></tr></thead>
        <tbody>
            @foreach($conversations as $conversation)
                <tr>
                    <td>{{ $conversation->id }}</td>
                    <td>{{ $conversation->lead->name ?? '' }}</td>
                    <td>{{ $conversation->customer_phone }}</td>
                    <td>{{ $conversation->channel->name ?? '' }}</td>
                    <td>{{ \App\Models\ChatConversation::STATUS_SELECT[$conversation->status] ?? $conversation->status }}</td>
                    <td>{{ $conversation->human_takeover ? 'Sim' : 'Não' }}</td>
                    <td>{{ optional($conversation->last_message_at)->format('Y-m-d H:i') }}</td>
                    <td>@can('chat_conversation_show')<a class="btn btn-xs btn-primary" href="{{ route('admin.chat-conversations.show', $conversation) }}">{{ trans('global.view') }}</a>@endcan</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $conversations->links() }}
</div></div></div>
@endsection

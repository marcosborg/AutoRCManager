@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Conversa #{{ $chatConversation->id }}</div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4"><strong>Lead:</strong> {{ $chatConversation->lead->name ?? '' }}</div>
                <div class="col-md-4"><strong>Telefone:</strong> {{ $chatConversation->customer_phone }}</div>
                <div class="col-md-4"><strong>Canal:</strong> {{ $chatConversation->channel->name ?? '' }}</div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-md-4"><strong>Estado:</strong> {{ \App\Models\ChatConversation::STATUS_SELECT[$chatConversation->status] ?? $chatConversation->status }}</div>
                <div class="col-md-4"><strong>Prioridade:</strong> {{ \App\Models\ChatLead::PRIORITY_SELECT[$chatConversation->lead->priority ?? ''] ?? ($chatConversation->lead->priority ?? '') }}</div>
                <div class="col-md-4"><strong>Humano:</strong> {{ $chatConversation->human_takeover ? 'Sim' : 'Não' }}</div>
            </div>
            @can('chat_conversation_edit')
                <div style="margin: 15px 0;">
                    <form method="POST" action="{{ route('admin.chat-conversations.takeover', $chatConversation) }}" style="display:inline-block">@csrf <button class="btn btn-warning" type="submit">Assumir por humano</button></form>
                    <form method="POST" action="{{ route('admin.chat-conversations.release', $chatConversation) }}" style="display:inline-block">@csrf <button class="btn btn-success" type="submit">Libertar para IA</button></form>
                    <form method="POST" action="{{ route('admin.chat-conversations.close', $chatConversation) }}" style="display:inline-block">@csrf <button class="btn btn-danger" type="submit">Fechar</button></form>
                </div>
            @endcan
            <h4>Histórico</h4>
            @foreach($chatConversation->messages as $message)
                <div class="well well-sm">
                    <strong>{{ \App\Models\ChatMessage::SENDER_SELECT[$message->sender] ?? $message->sender }}</strong>
                    <small class="text-muted">{{ $message->created_at->format('Y-m-d H:i') }} | {{ \App\Models\ChatMessage::DELIVERY_STATUS_SELECT[$message->delivery_status] ?? $message->delivery_status }}</small>
                    <p style="white-space: pre-wrap; margin-top: 8px;">{{ $message->message }}</p>
                </div>
            @endforeach
            <a class="btn btn-default" href="{{ route('admin.chat-conversations.index') }}">{{ trans('global.back_to_list') }}</a>
        </div>
    </div>
</div>
@endsection

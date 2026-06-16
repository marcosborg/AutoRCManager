@extends('layouts.admin')
@section('content')
<div class="content">
    @can('chat_lead_create')
        <div style="margin-bottom: 10px;" class="row"><div class="col-lg-12"><a class="btn btn-success" href="{{ route('admin.chat-leads.create') }}">Adicionar lead IA</a></div></div>
    @endcan
    <div class="panel panel-default">
        <div class="panel-heading">Leads do Assistente</div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-hover">
                <thead><tr><th>ID</th><th>Nome</th><th>Telefone</th><th>Canal</th><th>Viatura</th><th>Prioridade</th><th>Estado</th><th>Vendedor</th><th>&nbsp;</th></tr></thead>
                <tbody>
                    @foreach($chatLeads as $lead)
                        <tr>
                            <td>{{ $lead->id }}</td>
                            <td>{{ $lead->name }}</td>
                            <td>{{ $lead->phone }}</td>
                            <td>{{ $lead->channel->name ?? '' }}</td>
                            <td>{{ $lead->vehicle_title }}</td>
                            <td>{{ \App\Models\ChatLead::PRIORITY_SELECT[$lead->priority] ?? $lead->priority }}</td>
                            <td>{{ \App\Models\ChatLead::STATUS_SELECT[$lead->status] ?? $lead->status }}</td>
                            <td>{{ $lead->assigned_user->name ?? '' }}</td>
                            <td>
                                @can('chat_lead_show')<a class="btn btn-xs btn-primary" href="{{ route('admin.chat-leads.show', $lead) }}">{{ trans('global.view') }}</a>@endcan
                                @can('chat_lead_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.chat-leads.edit', $lead) }}">{{ trans('global.edit') }}</a>@endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $chatLeads->links() }}
        </div>
    </div>
</div>
@endsection

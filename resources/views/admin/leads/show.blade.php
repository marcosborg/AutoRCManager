@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">Lead #{{ $lead->id }}</div>
                <div class="panel-body">
                    <p><strong>Estado:</strong> {{ $statuses[$lead->status] ?? $lead->status }}</p>
                    <p><strong>Nome:</strong> {{ $lead->full_name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: '-' }}</p>
                    <p><strong>Telefone:</strong> {{ $lead->phone ?: '-' }}</p>
                    <p><strong>Email:</strong> {{ $lead->email ?: '-' }}</p>
                    <p><strong>Orcamento:</strong> {{ $lead->budget ?: '-' }}</p>
                    <p><strong>Veiculo/interesse:</strong> {{ $lead->vehicle_interest ?: '-' }}</p>
                    <p><strong>Financiamento:</strong> {{ $lead->financing ?: '-' }}</p>
                    <p><strong>Retoma:</strong> {{ $lead->trade_in ?: '-' }}</p>
                    <p><strong>Vendedor:</strong> {{ $lead->assigned_user->name ?? '-' }}</p>
                    <p><strong>Meta leadgen:</strong> {{ $lead->leadgen_id }}</p>
                    <p><strong>Page/Form:</strong> {{ $lead->page_id }} / {{ $lead->form_id }}</p>
                    @can('lead_edit')
                        <a class="btn btn-info" href="{{ route('admin.leads.edit', $lead) }}">{{ trans('global.edit') }}</a>
                    @endcan
                    <a class="btn btn-default" href="{{ route('admin.leads.index') }}">{{ trans('global.back_to_list') }}</a>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">Notas</div>
                <div class="panel-body">
                    @can('lead_edit')
                        <form method="POST" action="{{ route('admin.leads.notes.store', $lead) }}">
                            @csrf
                            <div class="form-group {{ $errors->has('body') ? 'has-error' : '' }}">
                                <textarea class="form-control" name="body" rows="3" placeholder="Adicionar nota">{{ old('body') }}</textarea>
                                @if($errors->has('body'))<span class="help-block">{{ $errors->first('body') }}</span>@endif
                            </div>
                            <button class="btn btn-success" type="submit">Adicionar nota</button>
                        </form>
                        <hr>
                    @endcan
                    @forelse($lead->notes->sortByDesc('created_at') as $note)
                        <div class="well well-sm">
                            <p>{{ $note->body }}</p>
                            <small>{{ $note->created_at }} · {{ $note->user->name ?? '-' }}</small>
                            @can('lead_edit')
                                <form method="POST" action="{{ route('admin.leads.notes.destroy', [$lead, $note]) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-danger" type="submit">{{ trans('global.delete') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p><em>Sem notas.</em></p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Timeline</div>
                <div class="panel-body">
                    <p><strong>Criado:</strong> {{ $lead->created_at }}</p>
                    <p><strong>Atualizado:</strong> {{ $lead->updated_at }}</p>
                    <hr>
                    <h5>Atribuicoes</h5>
                    @forelse($lead->assignment_histories->sortByDesc('created_at') as $history)
                        <p>
                            {{ $history->created_at }}<br>
                            <strong>{{ $history->user->name ?? 'Sem vendedor' }}</strong>
                            <small>({{ $history->reason }})</small>
                        </p>
                    @empty
                        <p><em>Sem historico.</em></p>
                    @endforelse
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">Raw Meta</div>
                <div class="panel-body">
                    <pre style="white-space: pre-wrap; max-height: 420px; overflow:auto;">{{ json_encode($lead->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

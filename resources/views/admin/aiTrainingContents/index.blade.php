@extends('layouts.admin')
@section('content')
<div class="content">
    @can('ai_training_content_create')
        <div style="margin-bottom: 10px;" class="row"><div class="col-lg-12"><a class="btn btn-success" href="{{ route('admin.ai-training-contents.create') }}">Adicionar treino</a></div></div>
    @endcan
    <div class="panel panel-default">
        <div class="panel-heading">Treino da IA</div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-hover">
                <thead><tr><th>ID</th><th>Assistente</th><th>Título</th><th>Tipo</th><th>Ativo</th><th>Ordem</th><th>&nbsp;</th></tr></thead>
                <tbody>
                    @foreach($contents as $content)
                        <tr>
                            <td>{{ $content->id }}</td>
                            <td>{{ $content->assistant->name ?? 'Global' }}</td>
                            <td>{{ $content->title }}</td>
                            <td>{{ \App\Models\AiTrainingContent::TYPE_SELECT[$content->type] ?? $content->type }}</td>
                            <td>{{ $content->active ? 'Sim' : 'Não' }}</td>
                            <td>{{ $content->sort_order }}</td>
                            <td>
                                @can('ai_training_content_show')<a class="btn btn-xs btn-primary" href="{{ route('admin.ai-training-contents.show', $content) }}">{{ trans('global.view') }}</a>@endcan
                                @can('ai_training_content_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.ai-training-contents.edit', $content) }}">{{ trans('global.edit') }}</a>@endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $contents->links() }}
        </div>
    </div>
</div>
@endsection

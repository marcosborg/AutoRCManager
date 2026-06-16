@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Treino da IA</div><div class="panel-body">
    <table class="table table-bordered table-striped">
        <tr><th>ID</th><td>{{ $aiTrainingContent->id }}</td></tr>
        <tr><th>Assistente</th><td>{{ $aiTrainingContent->assistant->name ?? 'Global' }}</td></tr>
        <tr><th>Título</th><td>{{ $aiTrainingContent->title }}</td></tr>
        <tr><th>Tipo</th><td>{{ \App\Models\AiTrainingContent::TYPE_SELECT[$aiTrainingContent->type] ?? $aiTrainingContent->type }}</td></tr>
        <tr><th>Conteúdo</th><td><pre style="white-space: pre-wrap">{{ $aiTrainingContent->content }}</pre></td></tr>
    </table>
    <a class="btn btn-default" href="{{ route('admin.ai-training-contents.index') }}">{{ trans('global.back_to_list') }}</a>
</div></div></div>
@endsection

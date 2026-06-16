@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Criar treino da IA</div><div class="panel-body"><form method="POST" action="{{ route('admin.ai-training-contents.store') }}">@csrf @include('admin.aiTrainingContents.form', ['content' => null])</form></div></div></div>
@endsection

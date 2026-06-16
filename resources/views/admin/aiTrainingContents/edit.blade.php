@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Editar treino da IA</div><div class="panel-body"><form method="POST" action="{{ route('admin.ai-training-contents.update', $aiTrainingContent) }}">@method('PUT') @csrf @include('admin.aiTrainingContents.form', ['content' => $aiTrainingContent])</form></div></div></div>
@endsection

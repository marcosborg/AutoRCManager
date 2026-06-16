@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Editar assistente IA</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.ai-assistants.update', $aiAssistant) }}">
                @method('PUT')
                @csrf
                @include('admin.aiAssistants.form', ['aiAssistant' => $aiAssistant])
            </form>
        </div>
    </div>
</div>
@endsection

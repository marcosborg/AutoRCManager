@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Criar assistente IA</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.ai-assistants.store') }}">
                @csrf
                @include('admin.aiAssistants.form', ['aiAssistant' => null])
            </form>
        </div>
    </div>
</div>
@endsection

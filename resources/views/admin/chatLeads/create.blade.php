@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">Criar lead do assistente</div><div class="panel-body">
    <form method="POST" action="{{ route('admin.chat-leads.store') }}">
        @csrf
        @include('admin.chatLeads.form')
    </form>
</div></div></div>
@endsection

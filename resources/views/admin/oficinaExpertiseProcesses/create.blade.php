@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Criar Peritagem de Oficina</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.oficina-expertise-processes.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.oficinaExpertiseProcesses.partials.form')
            </form>
        </div>
    </div>
</div>
@endsection

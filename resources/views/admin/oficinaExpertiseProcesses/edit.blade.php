@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Editar Peritagem de Oficina #{{ $process->id }}</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.oficina-expertise-processes.update', $process) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.oficinaExpertiseProcesses.partials.form')
            </form>
        </div>
    </div>
</div>
@endsection

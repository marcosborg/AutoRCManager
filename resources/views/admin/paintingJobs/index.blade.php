@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header"><strong>Pintura</strong>
        @can('painting_job_create')<a class="btn btn-success pull-right" href="{{ route('admin.painting-jobs.create') }}">Nova ficha</a>@endcan
    </div>
    <div class="card-body">
        <form class="row" method="GET">
            <div class="col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Matrícula ou viatura"></div>
            <div class="col-md-2"><select class="form-control" name="status"><option value="">Todos os estados</option>@foreach($statuses as $key => $label)<option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-control" name="painter_id"><option value="">Todos os pintores</option>@foreach($painters as $id => $name)<option value="{{ $id }}" {{ (string) request('painter_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input class="form-control" type="date" name="from" value="{{ request('from') }}"></div>
            <div class="col-md-2"><input class="form-control" type="date" name="to" value="{{ request('to') }}"></div>
            <div class="col-md-1"><button class="btn btn-primary">Filtrar</button></div>
        </form>
        <div class="table-responsive" style="margin-top:20px"><table class="table table-bordered table-striped">
            <thead><tr><th>#</th><th>Viatura</th><th>Entrada</th><th>Saída</th><th>Pintor</th><th>Estado</th><th></th></tr></thead>
            <tbody>@forelse($jobs as $job)<tr>
                <td>{{ $job->id }}</td><td><strong>{{ $job->license }}</strong><br>{{ $job->brand_model }}</td>
                <td>{{ optional($job->entry_date)->format('d/m/Y') }}</td><td>{{ optional($job->exit_date)->format('d/m/Y') }}</td><td>{{ $job->painter?->name ?: 'Por atribuir' }}</td>
                <td><span class="label label-{{ $job->status === 'completed' ? 'success' : 'warning' }}">{{ $statuses[$job->status] ?? $job->status }}</span></td>
                <td><a class="btn btn-xs btn-primary" href="{{ route('admin.painting-jobs.show', $job) }}">Ver</a> @can('painting_job_edit')<a class="btn btn-xs btn-info" href="{{ route('admin.painting-jobs.edit', $job) }}">Editar</a>@endcan</td>
            </tr>@empty<tr><td colspan="7">Sem fichas de pintura.</td></tr>@endforelse</tbody>
        </table></div>{{ $jobs->links() }}
    </div>
</div>
@endsection

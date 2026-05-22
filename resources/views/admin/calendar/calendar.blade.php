@extends('layouts.admin')
@section('content')
<div class="content">
    <h3 class="page-title">{{ trans('global.systemCalendar') }}</h3>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.systemCalendar') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.systemCalendar.tasks.store') }}" class="row" style="margin-bottom: 20px;">
                        @csrf
                        <div class="col-md-4">
                            <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                                <label for="title">Tarefa</label>
                                <input class="form-control" type="text" name="title" id="title" value="{{ old('title') }}" required>
                                @if($errors->has('title'))
                                    <span class="help-block" role="alert">{{ $errors->first('title') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group {{ $errors->has('due_date') ? 'has-error' : '' }}">
                                <label for="due_date">Data</label>
                                <input class="form-control date" type="text" name="due_date" id="due_date" value="{{ old('due_date') }}" required>
                                @if($errors->has('due_date'))
                                    <span class="help-block" role="alert">{{ $errors->first('due_date') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                                <label for="notes">Notas</label>
                                <input class="form-control" type="text" name="notes" id="notes" value="{{ old('notes') }}">
                                @if($errors->has('notes'))
                                    <span class="help-block" role="alert">{{ $errors->first('notes') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button class="btn btn-danger btn-block" type="submit">Criar tarefa</button>
                        </div>
                    </form>

                    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.1.0/fullcalendar.min.css' />

                    <div id='calendar'></div>

                    <hr>
                    <h4>Tarefas</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tarefa</th>
                                    <th>Data</th>
                                    <th>Notas</th>
                                    <th>Criada por</th>
                                    <th>Estado</th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                    <tr id="task-{{ $task->id }}">
                                        <td>{{ $task->title }}</td>
                                        <td>{{ $task->due_date }}</td>
                                        <td>{{ $task->notes }}</td>
                                        <td>{{ $task->created_by->name ?? '-' }}</td>
                                        <td>
                                            @if($task->completed_at)
                                                <span class="label label-success">Concluida</span>
                                            @else
                                                <span class="label label-warning">Pendente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$task->completed_at)
                                                <form method="POST" action="{{ route('admin.systemCalendar.tasks.complete', $task) }}" style="display:inline-block">
                                                    @csrf
                                                    <button class="btn btn-xs btn-success" type="submit">Concluir</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.systemCalendar.tasks.destroy', $task) }}" style="display:inline-block" onsubmit="return confirm('{{ trans('global.areYouSure') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-xs btn-danger" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Sem tarefas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/moment.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.1.0/fullcalendar.min.js'></script>
<script>
    $(document).ready(function () {
            // page is now ready, initialize the calendar...
            events={!! json_encode($events) !!};
            $('#calendar').fullCalendar({
                // put your options and callbacks here
                events: events,


            })
        });
</script>
@stop

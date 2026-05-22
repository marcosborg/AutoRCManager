<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarTask;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SystemCalendarController extends Controller
{
    public $sources = [

    ];

    public function index()
    {
        $events = [];
        foreach ($this->sources as $source) {
            foreach ($source['model']::all() as $model) {
                $crudFieldValue = $model->getAttributes()[$source['date_field']];

                if (! $crudFieldValue) {
                    continue;
                }

                $events[] = [
                    'title' => trim($source['prefix'] . ' ' . $model->{$source['field']} . ' ' . $source['suffix']),
                    'start' => $crudFieldValue,
                    'url'   => route($source['route'], $model->id),
                ];
            }
        }

        $tasks = CalendarTask::with('created_by')
            ->where('created_by_id', auth()->id())
            ->orderByRaw('completed_at IS NOT NULL')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($tasks as $task) {
            $events[] = [
                'title' => ($task->completed_at ? '[OK] ' : '[Tarefa] ') . $task->title,
                'start' => Carbon::createFromFormat(config('panel.date_format'), $task->due_date)->format('Y-m-d'),
                'url' => route('admin.systemCalendar') . '#task-' . $task->id,
                'color' => $task->completed_at ? '#00a65a' : '#f39c12',
            ];
        }

        return view('admin.calendar.calendar', compact('events', 'tasks'));
    }

    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'due_date' => ['required', 'date_format:' . config('panel.date_format')],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by_id'] = $request->user()?->id;

        CalendarTask::create($data);

        return redirect()->route('admin.systemCalendar')->with('message', 'Tarefa criada com sucesso.');
    }

    public function completeTask(CalendarTask $task)
    {
        abort_if((int) $task->created_by_id !== (int) auth()->id(), 403);

        $task->update(['completed_at' => now()]);

        return redirect()->route('admin.systemCalendar')->with('message', 'Tarefa concluida.');
    }

    public function destroyTask(CalendarTask $task)
    {
        abort_if((int) $task->created_by_id !== (int) auth()->id(), 403);

        $task->delete();

        return redirect()->route('admin.systemCalendar')->with('message', 'Tarefa eliminada.');
    }
}

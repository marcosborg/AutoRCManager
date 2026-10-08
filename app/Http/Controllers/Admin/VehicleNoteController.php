<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\AuditLog;
use App\Models\VehicleNote;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleNoteController extends Controller
{
    public function index(Vehicle $vehicle)
    {
        abort_unless(Gate::allows('vehicle_show') || Gate::allows('vehicle_edit'), 403);

        $notes = VehicleNote::where('vehicle_id', $vehicle->id)->orderByDesc('id')->paginate(20);

        return view('admin.vehicleNotes.index', compact('vehicle', 'notes'));
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        abort_unless(Gate::allows('vehicle_edit'), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'submission_token' => ['required', 'uuid'],
        ]);
        $body = trim($data['body']);
        if ($body === '' || preg_match('/^[\s\p{Z}]+$/u', $body)) {
            throw ValidationException::withMessages(['body' => 'Escreva a nota antes de a adicionar.']);
        }

        DB::transaction(function () use ($request, $vehicle, $data, $body) {
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            $existing = VehicleNote::where('vehicle_id', $vehicle->id)->where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                if ((int) $existing->author_id !== (int) $request->user()->id || $existing->body !== $body) {
                    throw ValidationException::withMessages(['body' => 'Este envio já foi utilizado. Atualize a página antes de escrever outra nota.']);
                }
                return;
            }
            VehicleNote::create([
                'vehicle_id' => $vehicle->id,
                'author_id' => $request->user()->id,
                'author_name' => $request->user()->name,
                'body' => $body,
                'submission_token' => $data['submission_token'],
            ]);
        });

        return redirect()->route('admin.vehicles.notes.index', $vehicle)->with('message', 'Nota interna adicionada.');
    }
    public function history(Vehicle $vehicle, VehicleNote $note)
    {
        abort_unless(Gate::allows('vehicle_show') || Gate::allows('vehicle_edit'), 403);
        abort_unless((int) $note->vehicle_id === (int) $vehicle->id, 404);
        $revisions = $note->revisions()->orderByDesc('id')->paginate(20);
        return view('admin.vehicleNotes.history', compact('vehicle', 'note', 'revisions'));
    }

    public function update(Request $request, Vehicle $vehicle, VehicleNote $note)
    {
        abort_unless(Gate::allows('vehicle_edit'), 403);
        abort_unless((int) $note->vehicle_id === (int) $vehicle->id, 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'revision' => ['required', 'string', 'size:64'],
        ]);
        $body = trim($data['body']);
        if ($body === '' || preg_match('/^[\s\p{Z}]+$/u', $body)) {
            throw ValidationException::withMessages(['body' => 'Escreva a nota antes de a guardar.']);
        }
        DB::transaction(function () use ($request, $vehicle, $note, $data, $body) {
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            $note = VehicleNote::where('vehicle_id', $vehicle->id)->whereKey($note->id)->lockForUpdate()->firstOrFail();
            if (! hash_equals($note->revisionToken(), $data['revision'])) {
                throw ValidationException::withMessages(['body' => 'Esta nota já foi alterada por outra pessoa. Atualize a página e confira a versão atual antes de corrigir.']);
            }
            if ($note->body === $body) return;
            $before = $note->body;
            $note->update(['body' => $body]);
            AuditLog::create([
                'description' => 'vehicle_note:edited',
                'subject_id' => $note->id,
                'subject_type' => VehicleNote::class.'#'.$note->id,
                'user_id' => $request->user()->id,
                'properties' => ['vehicle_id' => $vehicle->id, 'editor_name' => $request->user()->name, 'before' => $before, 'after' => $body],
                'host' => $request->ip(),
            ]);
        });
        return redirect()->route('admin.vehicles.notes.history', [$vehicle, $note])->with('message', 'Nota guardada. As alterações ficam no histórico.');
    }
}

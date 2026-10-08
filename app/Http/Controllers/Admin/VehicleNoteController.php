<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
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
}

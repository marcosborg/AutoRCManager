<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Domain\Repairs\RepairRules;
use App\Http\Controllers\Controller;
use App\Models\Repair;
use App\Models\RepairPart;
use App\Models\RepairState;
use App\Models\RepairWorkLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class WorkshopApiController extends Controller
{
    public function repairStates()
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json(
            RepairState::query()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function repairs(Request $request)
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Repair::query()
            ->with(['vehicle:id,license,foreign_license,brand_id,model', 'vehicle.brand:id,name', 'repair_state:id,name'])
            ->whereNotNull('vehicle_id')
            ->whereHas('vehicle')
            ->orderByDesc('id');

        $status = $request->query('status');
        if ($status === 'open') {
            $query->where(function ($q) {
                $q->whereNull('repair_state_id')->orWhere('repair_state_id', '!=', 3);
            });
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->whereHas('vehicle', function ($vehicleQuery) use ($search) {
                $vehicleQuery
                    ->where('license', 'like', '%' . $search . '%')
                    ->orWhere('foreign_license', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        $repairs = $query->limit(200)->get();

        return response()->json([
            'data' => $repairs->map(fn (Repair $repair) => $this->repairListPayload($repair)),
        ]);
    }

    public function repair(Repair $repair)
    {
        abort_if(Gate::denies('repair_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $repair->load($this->repairDetailRelations());

        return response()->json([
            'data' => $this->repairDetailPayload($repair),
        ]);
    }

    public function updateRepair(Request $request, Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $rules = [
            'repair_state_id' => ['nullable', 'integer', 'exists:repair_states,id'],
            'name' => ['nullable', 'string', 'max:191'],
            'kilometers' => ['nullable', 'integer'],
            'obs_1' => ['nullable', 'string'],
            'obs_2' => ['nullable', 'string'],
            'work_performed' => ['nullable', 'string'],
            'materials_used' => ['nullable', 'string'],
            'expected_completion_date' => ['nullable', 'date'],
            'timestamp' => ['nullable', 'date'],
        ];

        foreach ($this->checklistFieldDefinitions() as $field) {
            $rules[$field['key']] = ['nullable', 'boolean'];
            $rules[$field['key'] . '_text'] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        if (array_key_exists('expected_completion_date', $data) && $data['expected_completion_date']) {
            $data['expected_completion_date'] = Carbon::parse($data['expected_completion_date'])
                ->format(config('panel.date_format'));
        }

        if (array_key_exists('timestamp', $data) && $data['timestamp']) {
            $data['timestamp'] = Carbon::parse($data['timestamp'])
                ->format(config('panel.date_format') . ' ' . config('panel.time_format'));
        }

        $repair->update($data);
        $repair->refresh()->load($this->repairDetailRelations());

        return response()->json([
            'data' => $this->repairDetailPayload($repair),
        ]);
    }

    public function startRepair(Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (! $repair->getRawOriginal('repair_started_at')) {
            $repair->repair_started_at = now();
            $repair->repair_finished_at = null;
            $repair->save();
        }

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function finishRepair(Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (! $repair->getRawOriginal('repair_started_at')) {
            return response()->json([
                'message' => 'Inicie a reparacao antes de finalizar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $repair->getRawOriginal('repair_finished_at')) {
            $repair->repair_finished_at = now();
            $repair->save();
        }

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function startWork(Repair $repair, Request $request)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $openLog = RepairWorkLog::where('repair_id', $repair->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('finished_at')
            ->first();

        if (! $openLog) {
            RepairWorkLog::create([
                'repair_id' => $repair->id,
                'user_id' => $request->user()->id,
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function finishWork(Repair $repair, Request $request)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $openLog = RepairWorkLog::where('repair_id', $repair->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('finished_at')
            ->latest('id')
            ->first();

        if (! $openLog) {
            return response()->json([
                'message' => 'Nao existe trabalho em curso para este mecanico.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $startedAt = Carbon::parse((string) $openLog->started_at);
        $endedAt = now();
        $openLog->update([
            'finished_at' => $endedAt,
            'duration_minutes' => $startedAt->diffInMinutes($endedAt),
        ]);

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function addPart(Request $request, Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'supplier' => ['nullable', 'string', 'max:191'],
            'invoice_number' => ['nullable', 'string', 'max:191'],
            'part_date' => ['nullable', 'date'],
            'part_name' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $repair->parts()->create($data);

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ], Response::HTTP_CREATED);
    }

    public function updatePart(Request $request, Repair $repair, RepairPart $part)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ((int) $part->repair_id !== (int) $repair->id) {
            return response()->json(['message' => 'Peca invalida para esta intervencao.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate([
            'supplier' => ['nullable', 'string', 'max:191'],
            'invoice_number' => ['nullable', 'string', 'max:191'],
            'part_date' => ['nullable', 'date'],
            'part_name' => ['nullable', 'string', 'max:191'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $part->update($data);

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function deletePart(Repair $repair, RepairPart $part)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ((int) $part->repair_id !== (int) $repair->id) {
            return response()->json(['message' => 'Peca invalida para esta intervencao.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $part->delete();

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function uploadMedia(Request $request, Repair $repair)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'collection' => ['required', 'in:checkin,checkout'],
            'file' => ['required', 'image', 'max:8192'],
        ]);

        $repair->addMediaFromRequest('file')->toMediaCollection($data['collection']);

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ], Response::HTTP_CREATED);
    }

    public function deleteMedia(Repair $repair, int $mediaId)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $media = $repair->media()
            ->whereIn('collection_name', ['checkin', 'checkout'])
            ->where('id', $mediaId)
            ->first();

        if (! $media) {
            return response()->json(['message' => 'Ficheiro nao encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $media->delete();

        return response()->json([
            'data' => $this->repairDetailPayload($repair->fresh($this->repairDetailRelations())),
        ]);
    }

    public function vehicles(Request $request)
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $search = trim((string) $request->query('search', ''));
        $query = Vehicle::query()->with('brand:id,name')->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('license', 'like', '%' . $search . '%')
                    ->orWhere('foreign_license', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        $vehicles = $query->limit(50)->get(['id', 'license', 'foreign_license', 'brand_id', 'model']);

        return response()->json([
            'data' => $vehicles->map(function (Vehicle $vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license' => $vehicle->license,
                    'foreign_license' => $vehicle->foreign_license,
                    'brand' => $vehicle->brand?->name,
                    'model' => $vehicle->model,
                ];
            }),
        ]);
    }

    public function newIntervention(Request $request, Vehicle $vehicle)
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (RepairRules::hasOpenRepairs($vehicle->id)) {
            return response()->json([
                'message' => 'Ja existe uma intervencao aberta para esta viatura.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $repair = Repair::create([
            'vehicle_id' => $vehicle->id,
            'kilometers' => is_numeric($vehicle->kilometers) ? (int) $vehicle->kilometers : null,
            'timestamp' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $repair->id,
                'vehicle_id' => $repair->vehicle_id,
                'timestamp' => $repair->getRawOriginal('timestamp'),
            ],
        ], Response::HTTP_CREATED);
    }

    private function repairListPayload(Repair $repair): array
    {
        $startedAt = $repair->getRawOriginal('repair_started_at');
        $finishedAt = $repair->getRawOriginal('repair_finished_at');

        return [
            'id' => $repair->id,
            'vehicle_id' => $repair->vehicle_id,
            'vehicle_label' => trim(implode(' ', array_filter([
                $repair->vehicle?->license ?? $repair->vehicle?->foreign_license,
                $repair->vehicle?->brand?->name,
                $repair->vehicle?->model,
            ]))),
            'state' => $repair->repair_state?->name ?? 'Aberta',
            'repair_state_id' => $repair->repair_state_id,
            'is_open' => $repair->repair_state_id === null || (int) $repair->repair_state_id !== 3,
            'timestamp' => $repair->getRawOriginal('timestamp'),
            'repair_started_at' => $startedAt,
            'repair_finished_at' => $finishedAt,
            'repair_duration_minutes' => $repair->repair_duration_minutes,
        ];
    }

    private function repairDetailPayload(Repair $repair): array
    {
        $repair->loadMissing($this->repairDetailRelations());

        $totalPartsAmount = (float) $repair->parts->sum('amount');
        $totalWorkMinutes = (int) $repair->workLogs->sum('duration_minutes');

        $vehicleRepairs = collect();
        $canCreateNewIntervention = false;
        if ($repair->vehicle_id) {
            $vehicleRepairs = Repair::with('repair_state')
                ->where('vehicle_id', $repair->vehicle_id)
                ->orderByDesc('created_at')
                ->get();

            $currentIsOpen = $repair->repair_state_id === null || (int) $repair->repair_state_id !== 3;
            $canCreateNewIntervention = ! RepairRules::hasOpenRepairs($repair->vehicle_id) || ! $currentIsOpen;
        }

        $mechanicTotals = $repair->workLogs
            ->groupBy('user_id')
            ->map(function ($logs) {
                $minutes = (int) $logs->sum(function ($log) {
                    if ($log->duration_minutes !== null) {
                        return (int) $log->duration_minutes;
                    }

                    if ($log->finished_at) {
                        return Carbon::parse((string) $log->started_at)->diffInMinutes(Carbon::parse((string) $log->finished_at));
                    }

                    return Carbon::parse((string) $log->started_at)->diffInMinutes(now());
                });

                return [
                    'user_id' => (int) $logs->first()->user_id,
                    'name' => $logs->first()->user?->name ?? 'Desconhecido',
                    'minutes' => $minutes,
                ];
            })
            ->values();

        return [
            'id' => $repair->id,
            'vehicle' => [
                'id' => $repair->vehicle?->id,
                'license' => $repair->vehicle?->license,
                'foreign_license' => $repair->vehicle?->foreign_license,
                'brand' => $repair->vehicle?->brand?->name,
                'model' => $repair->vehicle?->model,
                'version' => $repair->vehicle?->version,
                'transmission' => $repair->vehicle?->transmission,
                'engine_displacement' => $repair->vehicle?->engine_displacement,
                'year' => $repair->vehicle?->year,
                'month' => $repair->vehicle?->month,
                'license_date' => $repair->vehicle?->license_date,
                'color' => $repair->vehicle?->color,
                'fuel' => $repair->vehicle?->fuel,
                'kilometers' => $repair->vehicle?->kilometers,
                'inspec_b' => $repair->vehicle?->inspec_b,
                'general_state' => $repair->vehicle?->general_state?->name,
                'initial_photos' => $repair->vehicle
                    ? $repair->vehicle->inicial->map(fn (Media $media) => [
                        'id' => $media->id,
                        'url' => url($media->getUrl()),
                        'thumb' => url($media->getUrl('thumb')),
                    ])->values()
                    : [],
            ],
            'name' => $repair->name,
            'kilometers' => $repair->kilometers,
            'obs_1' => $repair->obs_1,
            'obs_2' => $repair->obs_2,
            'checklist_percentage' => $repair->checklist_percentage,
            'checklist' => collect($this->checklistFieldDefinitions())->map(function (array $field) use ($repair) {
                return [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'group' => $field['group'],
                    'checked' => (bool) ($repair->{$field['key']} ?? false),
                    'note' => $repair->{$field['key'] . '_text'} ?? null,
                ];
            })->values(),
            'repair_state_id' => $repair->repair_state_id,
            'repair_state' => $repair->repair_state?->name ?? 'Aberta',
            'timestamp' => $repair->getRawOriginal('timestamp'),
            'repair_started_at' => $repair->getRawOriginal('repair_started_at'),
            'repair_finished_at' => $repair->getRawOriginal('repair_finished_at'),
            'repair_duration_minutes' => $repair->repair_duration_minutes,
            'work_performed' => $repair->work_performed,
            'materials_used' => $repair->materials_used,
            'expected_completion_date' => $repair->getRawOriginal('expected_completion_date'),
            'checkin_photos' => $repair->checkin->map(fn (Media $media) => [
                'id' => $media->id,
                'url' => url($media->getUrl()),
                'thumb' => url($media->getUrl('thumb')),
            ])->values(),
            'checkout_photos' => $repair->checkout->map(fn (Media $media) => [
                'id' => $media->id,
                'url' => url($media->getUrl()),
                'thumb' => url($media->getUrl('thumb')),
            ])->values(),
            'parts' => $repair->parts
                ->sortByDesc('id')
                ->values()
                ->map(fn (RepairPart $part) => [
                    'id' => $part->id,
                    'supplier' => $part->supplier,
                    'invoice_number' => $part->invoice_number,
                    'part_date' => $part->getRawOriginal('part_date'),
                    'part_name' => $part->part_name,
                    'amount' => (float) $part->amount,
                ]),
            'parts_total' => $totalPartsAmount,
            'work_logs' => $repair->workLogs
                ->sortByDesc('id')
                ->values()
                ->map(fn (RepairWorkLog $log) => [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user?->name,
                    'started_at' => $log->getRawOriginal('started_at'),
                    'finished_at' => $log->getRawOriginal('finished_at'),
                    'duration_minutes' => (int) ($log->duration_minutes ?? 0),
                ]),
            'work_total_minutes' => $totalWorkMinutes,
            'mechanic_totals' => $mechanicTotals,
            'can_create_new_intervention' => $canCreateNewIntervention,
            'vehicle_repairs' => $vehicleRepairs->map(function (Repair $item) use ($repair) {
                return [
                    'id' => $item->id,
                    'opened_at' => optional($item->created_at)->format('Y-m-d H:i'),
                    'state' => $item->repair_state?->name ?? 'Aberta',
                    'expected_completion_date' => $item->getRawOriginal('expected_completion_date'),
                    'checklist_percentage' => $item->checklist_percentage,
                    'is_current' => (int) $item->id === (int) $repair->id,
                ];
            })->values(),
        ];
    }

    private function repairDetailRelations(): array
    {
        return [
            'vehicle:id,license,foreign_license,brand_id,model,version,transmission,engine_displacement,year,month,license_date,color,fuel,kilometers,inspec_b,general_state_id',
            'vehicle.brand:id,name',
            'vehicle.general_state:id,name',
            'repair_state:id,name',
            'parts',
            'workLogs.user:id,name',
        ];
    }

    private function checklistFieldDefinitions(): array
    {
        return [
            ['key' => 'front_windshield', 'label' => 'Para-brisas dianteiro', 'group' => 'Exterior'],
            ['key' => 'front_lights', 'label' => 'Luzes dianteiras', 'group' => 'Exterior'],
            ['key' => 'rear_lights', 'label' => 'Luzes traseiras', 'group' => 'Exterior'],
            ['key' => 'horn_functionality', 'label' => 'Funcionalidade da buzina', 'group' => 'Exterior'],
            ['key' => 'wiper_blades_water_level', 'label' => 'Escovas / nivel de agua', 'group' => 'Exterior'],
            ['key' => 'brake_clutch_oil_level', 'label' => 'Nivel oleo travoes/embraiagem', 'group' => 'Mecanica'],
            ['key' => 'electrical_systems', 'label' => 'Sistemas eletricos', 'group' => 'Mecanica'],
            ['key' => 'engine_coolant_level', 'label' => 'Nivel liquido refrigeracao', 'group' => 'Mecanica'],
            ['key' => 'engine_oil_level', 'label' => 'Nivel oleo motor', 'group' => 'Mecanica'],
            ['key' => 'filters_air_cabin_oil_fuel', 'label' => 'Filtros ar/habitaculo/oleo/combustivel', 'group' => 'Mecanica'],
            ['key' => 'check_leaks_engine_gearbox_steering', 'label' => 'Fugas motor/caixa/direcao', 'group' => 'Mecanica'],
            ['key' => 'brake_pads_disks', 'label' => 'Pastilhas/discos travao', 'group' => 'Mecanica'],
            ['key' => 'shock_absorbers', 'label' => 'Amortecedores', 'group' => 'Mecanica'],
            ['key' => 'tire_condition', 'label' => 'Estado pneus', 'group' => 'Mecanica'],
            ['key' => 'battery', 'label' => 'Bateria', 'group' => 'Mecanica'],
            ['key' => 'spare_tire_vest_triangle_tools', 'label' => 'Pneu suplente/colete/triangulo/ferramentas', 'group' => 'Seguranca'],
            ['key' => 'check_clearance', 'label' => 'Verificar folgas', 'group' => 'Exterior'],
            ['key' => 'check_shields', 'label' => 'Verificar escudos', 'group' => 'Exterior'],
            ['key' => 'paint_condition', 'label' => 'Estado da pintura', 'group' => 'Exterior'],
            ['key' => 'dents', 'label' => 'Mossas', 'group' => 'Exterior'],
            ['key' => 'diverse_strips', 'label' => 'Frisos diversos', 'group' => 'Exterior'],
            ['key' => 'diverse_plastics_check_scratches', 'label' => 'Plasticos diversos / riscos', 'group' => 'Exterior'],
            ['key' => 'wheels', 'label' => 'Jantes', 'group' => 'Exterior'],
            ['key' => 'bolts_paint', 'label' => 'Parafusos / pintura', 'group' => 'Exterior'],
            ['key' => 'seat_belts', 'label' => 'Cintos seguranca', 'group' => 'Interior'],
            ['key' => 'radio', 'label' => 'Radio', 'group' => 'Interior'],
            ['key' => 'air_conditioning', 'label' => 'Ar condicionado', 'group' => 'Interior'],
            ['key' => 'front_rear_window_functionality', 'label' => 'Funcionalidade vidros frente/tras', 'group' => 'Interior'],
            ['key' => 'seats_upholstery', 'label' => 'Bancos / estofos', 'group' => 'Interior'],
            ['key' => 'sun_visors', 'label' => 'Palas de sol', 'group' => 'Interior'],
            ['key' => 'carpets', 'label' => 'Tapetes', 'group' => 'Interior'],
            ['key' => 'trunk_shelf', 'label' => 'Chapeleira', 'group' => 'Interior'],
            ['key' => 'buttons', 'label' => 'Botoes', 'group' => 'Interior'],
            ['key' => 'door_panels', 'label' => 'Paineis de porta', 'group' => 'Interior'],
            ['key' => 'locks', 'label' => 'Fechaduras', 'group' => 'Interior'],
            ['key' => 'interior_covers_headlights_taillights', 'label' => 'Forros interiores/farois/faroins', 'group' => 'Interior'],
            ['key' => 'open_close_doors_remote_control_all_functions', 'label' => 'Abrir/fechar portas/comando/funcoes', 'group' => 'Interior'],
            ['key' => 'turn_on_ac_check_glass', 'label' => 'Ligar AC e verificar vidros', 'group' => 'Interior'],
            ['key' => 'check_engine_lift_hood', 'label' => 'Verificar motor com capot aberto', 'group' => 'Mecanica'],
            ['key' => 'connect_vehicle_to_scanner_check_errors', 'label' => 'Scanner e erros', 'group' => 'Diagnostico'],
            ['key' => 'check_chassis_confirm_with_registration', 'label' => 'Confirmar chassis com livrete', 'group' => 'Documentacao'],
            ['key' => 'manufacturer_plate', 'label' => 'Chapa fabricante', 'group' => 'Documentacao'],
            ['key' => 'check_chassis_stickers', 'label' => 'Autocolantes chassis', 'group' => 'Documentacao'],
            ['key' => 'check_gearbox_oil', 'label' => 'Verificar oleo caixa', 'group' => 'Mecanica'],
        ];
    }
}


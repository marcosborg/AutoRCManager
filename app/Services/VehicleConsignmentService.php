<?php

namespace App\Services;

use App\Domain\Consignments\ConsignmentStatus;
use App\Domain\Repairs\RepairRules;
use App\Models\VehicleConsignment;
use App\Models\VehicleLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleConsignmentService
{
    public function createConsignment(array $data): VehicleConsignment
    {
        return DB::transaction(function () use ($data) {
            $startsAt = $this->parseDateTime($data['starts_at'] ?? null);
            if (! $startsAt) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Data de inicio invalida.',
                ]);
            }

            $this->ensureNoActiveConsignment($data['vehicle_id'], $startsAt);
            $this->ensureNoOverlappingConsignments($data['vehicle_id'], $startsAt, null);

            $this->endActiveLocationIfNeeded($data['vehicle_id'], $startsAt);

            $consignment = VehicleConsignment::create([
                'vehicle_id' => $data['vehicle_id'],
                'from_unit_id' => $data['from_unit_id'],
                'to_unit_id' => $data['to_unit_id'] ?? null,
                'to_unit_name' => $data['to_unit_name'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => null,
                'status' => ConsignmentStatus::ACTIVE,
            ]);

            if (! empty($data['to_unit_id'])) {
                VehicleLocation::create([
                    'vehicle_id' => $data['vehicle_id'],
                    'operational_unit_id' => $data['to_unit_id'],
                    'starts_at' => $startsAt,
                    'ends_at' => null,
                ]);
            }

            return $consignment;
        });
    }

    public function closeConsignment(VehicleConsignment $consignment, array $data): VehicleConsignment
    {
        return DB::transaction(function () use ($consignment, $data) {
            if ($consignment->status === ConsignmentStatus::CLOSED || $consignment->ends_at) {
                throw ValidationException::withMessages([
                    'status' => 'A consignacao ja esta encerrada.',
                ]);
            }

            $endsAt = $this->parseDateTime($data['ends_at'] ?? null);
            if (! $endsAt) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Data de fim invalida.',
                ]);
            }

            $startsAt = Carbon::parse($consignment->starts_at);
            if ($endsAt->lt($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'A data de fim deve ser posterior ao inicio.',
                ]);
            }

            $this->ensureNoOverlappingConsignments($consignment->vehicle_id, $startsAt, $endsAt, $consignment->id);
            $this->ensureNoOpenRepairs($consignment->vehicle_id);

            $this->endActiveLocationOnClose($consignment, $endsAt);

            $consignment->status = ConsignmentStatus::CLOSED;
            $consignment->ends_at = $endsAt;
            $consignment->save();

            return $consignment;
        });
    }

    public function updateConsignment(VehicleConsignment $consignment, array $data): VehicleConsignment
    {
        return DB::transaction(function () use ($consignment, $data) {
            $wasClosed = $consignment->status === ConsignmentStatus::CLOSED || $consignment->ends_at;
            $willBeClosed = ($data['status'] ?? null) === ConsignmentStatus::CLOSED;

            if ($wasClosed && ! $willBeClosed) {
                throw ValidationException::withMessages([
                    'status' => 'Uma consignacao encerrada nao pode voltar a ativa.',
                ]);
            }

            $startsAt = $this->parseDateTime($data['starts_at'] ?? null);
            $endsAt = $willBeClosed ? $this->parseDateTime($data['ends_at'] ?? null) : null;
            if (! $startsAt) {
                throw ValidationException::withMessages(['starts_at' => 'Data de inicio invalida.']);
            }
            if ($willBeClosed && ! $endsAt) {
                throw ValidationException::withMessages(['ends_at' => 'Data de fim invalida.']);
            }
            if ($endsAt && $endsAt->lt($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'A data de fim deve ser posterior ao inicio.',
                ]);
            }

            $this->removeLocationEffects($consignment);
            $this->ensureNoActiveConsignment((int) $data['vehicle_id'], $startsAt, $consignment->id);
            $this->ensureNoOverlappingConsignments((int) $data['vehicle_id'], $startsAt, $endsAt, $consignment->id);

            if (! $wasClosed && $willBeClosed) {
                $this->ensureNoOpenRepairs((int) $data['vehicle_id']);
            }

            $this->endActiveLocationIfNeeded((int) $data['vehicle_id'], $startsAt);

            $consignment->fill([
                'vehicle_id' => $data['vehicle_id'],
                'from_unit_id' => $data['from_unit_id'],
                'to_unit_id' => $data['to_unit_id'] ?? null,
                'to_unit_name' => $data['to_unit_name'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $willBeClosed ? ConsignmentStatus::CLOSED : ConsignmentStatus::ACTIVE,
            ])->save();

            $this->createLocationEffect($consignment, $startsAt, $endsAt);

            return $consignment->refresh();
        });
    }

    public function deleteConsignment(VehicleConsignment $consignment): void
    {
        DB::transaction(function () use ($consignment): void {
            $this->removeLocationEffects($consignment);
            $consignment->delete();
        });
    }

    private function ensureNoActiveConsignment(int $vehicleId, Carbon $startsAt, ?int $ignoreId = null): void
    {
        $activeQuery = VehicleConsignment::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', ConsignmentStatus::ACTIVE)
            ->whereNull('ends_at');
        if ($ignoreId) {
            $activeQuery->whereKeyNot($ignoreId);
        }

        if ($activeQuery->exists()) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Ja existe uma consignacao ativa para esta viatura.',
            ]);
        }

        $overlaps = VehicleConsignment::query()
            ->where('vehicle_id', $vehicleId)
            ->where('starts_at', '<', $startsAt)
            ->whereNull('ends_at');
        if ($ignoreId) {
            $overlaps->whereKeyNot($ignoreId);
        }

        if ($overlaps->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Existe uma consignacao ativa que sobrepoe a data de inicio.',
            ]);
        }
    }

    private function createLocationEffect(VehicleConsignment $consignment, Carbon $startsAt, ?Carbon $endsAt): void
    {
        if (! $consignment->to_unit_id) {
            return;
        }

        VehicleLocation::create([
            'vehicle_id' => $consignment->vehicle_id,
            'operational_unit_id' => $consignment->to_unit_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    private function removeLocationEffects(VehicleConsignment $consignment): void
    {
        $startsAt = Carbon::parse($consignment->starts_at);
        $vehicleId = (int) $consignment->vehicle_id;

        if ($consignment->to_unit_id) {
            VehicleLocation::query()
                ->where('vehicle_id', $vehicleId)
                ->where('operational_unit_id', $consignment->to_unit_id)
                ->where('starts_at', $startsAt)
                ->when(
                    $consignment->ends_at,
                    fn ($query) => $query->where('ends_at', Carbon::parse($consignment->ends_at)),
                    fn ($query) => $query->whereNull('ends_at')
                )
                ->delete();
        }

        $previousLocation = VehicleLocation::query()
            ->where('vehicle_id', $vehicleId)
            ->where('ends_at', $startsAt)
            ->orderByDesc('starts_at')
            ->first();

        if (! $previousLocation) {
            return;
        }

        $nextStart = VehicleLocation::query()
            ->where('vehicle_id', $vehicleId)
            ->where('starts_at', '>', $startsAt)
            ->min('starts_at');

        $previousLocation->ends_at = $nextStart;
        $previousLocation->save();
    }

    private function ensureNoOverlappingConsignments(int $vehicleId, Carbon $startsAt, ?Carbon $endsAt, ?int $ignoreId = null): void
    {
        $query = VehicleConsignment::query()->where('vehicle_id', $vehicleId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($endsAt) {
            $query->where('starts_at', '<', $endsAt)
                ->where(function ($q) use ($startsAt) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', $startsAt);
                });
        } else {
            $query->where(function ($q) use ($startsAt) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $startsAt);
            });
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => 'A consignacao sobrepoe outra consignacao existente.',
            ]);
        }
    }

    private function endActiveLocationIfNeeded(int $vehicleId, Carbon $startsAt): void
    {
        $activeLocations = VehicleLocation::query()
            ->where('vehicle_id', $vehicleId)
            ->where('starts_at', '<=', $startsAt)
            ->where(function ($query) use ($startsAt) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $startsAt);
            })
            ->orderByDesc('starts_at')
            ->get();

        if ($activeLocations->count() > 1) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Existem multiplas localizacoes para a viatura neste periodo.',
            ]);
        }

        $activeLocation = $activeLocations->first();
        if (! $activeLocation) {
            return;
        }

        $activeLocation->ends_at = $startsAt;
        $activeLocation->save();
    }

    private function endActiveLocationOnClose(VehicleConsignment $consignment, Carbon $endsAt): void
    {
        if (! $consignment->to_unit_id) {
            return;
        }

        $activeLocations = VehicleLocation::query()
            ->where('vehicle_id', $consignment->vehicle_id)
            ->whereNull('ends_at')
            ->orderBy('starts_at')
            ->get();

        if ($activeLocations->count() > 1) {
            throw ValidationException::withMessages([
                'ends_at' => 'Existem multiplas localizacoes ativas para esta viatura.',
            ]);
        }

        $activeLocation = $activeLocations
            ->firstWhere('operational_unit_id', $consignment->to_unit_id);

        if (! $activeLocation) {
            throw ValidationException::withMessages([
                'ends_at' => 'Nao foi encontrada localizacao ativa para encerrar.',
            ]);
        }

        $locationStart = Carbon::parse($activeLocation->starts_at);
        if ($endsAt->lt($locationStart)) {
            throw ValidationException::withMessages([
                'ends_at' => 'A data de fim e anterior ao inicio da localizacao.',
            ]);
        }

        $activeLocation->ends_at = $endsAt;
        $activeLocation->save();
    }

    private function ensureNoOpenRepairs(int $vehicleId): void
    {
        $hasOpenRepairs = RepairRules::hasOpenRepairs($vehicleId);

        if ($hasOpenRepairs) {
            throw ValidationException::withMessages([
                'ends_at' => 'Nao e possivel encerrar com reparacao aberta.',
            ]);
        }
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat(
            config('panel.date_format') . ' ' . config('panel.time_format'),
            $value
        );
    }
}

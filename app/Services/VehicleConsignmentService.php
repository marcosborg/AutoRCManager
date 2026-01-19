<?php

namespace App\Services;

use App\Domain\Consignments\ConsignmentStatus;
use App\Domain\Consignments\ConsignmentRules;
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
                'to_unit_id' => $data['to_unit_id'],
                'reference_value' => $data['reference_value'],
                'starts_at' => $startsAt,
                'ends_at' => null,
                'status' => ConsignmentStatus::ACTIVE,
            ]);

            VehicleLocation::create([
                'vehicle_id' => $data['vehicle_id'],
                'operational_unit_id' => $data['to_unit_id'],
                'starts_at' => $startsAt,
                'ends_at' => null,
            ]);

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

    private function ensureNoActiveConsignment(int $vehicleId, Carbon $startsAt): void
    {
        $active = ConsignmentRules::hasActiveConsignment($vehicleId);

        if ($active) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Ja existe uma consignacao ativa para esta viatura.',
            ]);
        }

        $overlaps = VehicleConsignment::query()
            ->where('vehicle_id', $vehicleId)
            ->where('starts_at', '<', $startsAt)
            ->whereNull('ends_at')
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'starts_at' => 'Existe uma consignacao ativa que sobrepoe a data de inicio.',
            ]);
        }
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
            ->whereNull('ends_at')
            ->orderBy('starts_at')
            ->get();

        if ($activeLocations->count() > 1) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Existem multiplas localizacoes ativas para esta viatura.',
            ]);
        }

        $activeLocation = $activeLocations->first();
        if (! $activeLocation) {
            return;
        }

        $locationStart = Carbon::parse($activeLocation->starts_at);
        if ($locationStart->gt($startsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => 'A data de inicio nao pode ser anterior a localizacao ativa.',
            ]);
        }

        $activeLocation->ends_at = $startsAt;
        $activeLocation->save();
    }

    private function endActiveLocationOnClose(VehicleConsignment $consignment, Carbon $endsAt): void
    {
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

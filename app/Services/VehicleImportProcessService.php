<?php

namespace App\Services;

use App\Domain\Repairs\RepairRules;
use App\Models\CalendarTask;
use App\Models\OperationalAlertRecipient;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImportProcess;
use App\Support\LicensePlate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleImportProcessService
{
    public function sync(Vehicle $vehicle, array $data, User $user): ?VehicleImportProcess
    {
        if (! array_key_exists('import_process_present', $data)) {
            return $vehicle->import_process;
        }

        return DB::transaction(function () use ($vehicle, $data, $user): VehicleImportProcess {
            $process = $vehicle->import_process()->first();
            $decision = (string) ($data['import_decision'] ?? '');

            $this->validateDecisionChange($process, $decision);

            $decisionAt = $this->decisionAt($process, $decision, $data['import_decision_at'] ?? null);
            $deadlineAt = $decisionAt->copy()->addDays(
                $decision === VehicleImportProcess::DECISION_LEGALIZE ? 20 : 10
            );

            $agencyDocumentsSentAt = $decision === VehicleImportProcess::DECISION_LEGALIZE
                ? $this->timestampFromCheckbox($data, 'import_agency_documents_sent', 'import_agency_documents_sent_at')
                : null;
            $documentsReceivedAt = $decision === VehicleImportProcess::DECISION_LEGALIZE
                ? $this->timestampFromCheckbox($data, 'import_documents_received', 'import_documents_received_at')
                : null;
            $newLicenseReceivedAt = $decision === VehicleImportProcess::DECISION_LEGALIZE
                ? $this->timestampFromCheckbox($data, 'import_new_license_received', 'import_new_license_received_at')
                : null;
            $scrappedAt = $decision === VehicleImportProcess::DECISION_SCRAP
                ? $this->timestampFromCheckbox($data, 'import_scrapped', 'import_scrapped_at')
                : null;

            $newLicense = $newLicenseReceivedAt
                ? LicensePlate::formatNational($data['import_new_license'] ?? null)
                : null;

            if ($newLicenseReceivedAt && ! $newLicense) {
                throw ValidationException::withMessages([
                    'import_new_license' => 'Indique a nova matrícula antes de registar a sua receção.',
                ]);
            }

            $tollsRecipients = collect();

            if ($newLicenseReceivedAt) {
                $recipientConfiguration = OperationalAlertRecipient::with('users')
                    ->where('key', OperationalAlertRecipient::KEY_TOLLS)
                    ->first();
                $tollsRecipients = $recipientConfiguration?->users ?? collect();

                if ($tollsRecipients->isEmpty() && $recipientConfiguration?->user) {
                    $tollsRecipients = collect([$recipientConfiguration->user]);
                }
            }

            if ($newLicenseReceivedAt && $tollsRecipients->isEmpty()) {
                throw ValidationException::withMessages([
                    'import_new_license_received_at' => 'Configure primeiro os responsáveis pelos alertas.',
                ]);
            }

            if ($newLicense) {
                $this->validateUniqueLicense($vehicle, $newLicense);
            }

            $previousDocumentsReceivedAt = $process?->documents_received_at;
            $previousNewLicenseReceivedAt = $process?->new_license_received_at;
            $previousNewLicense = $process?->new_license;

            $attributes = [
                'decision' => $decision,
                'decision_at' => $decisionAt,
                'deadline_at' => $deadlineAt,
                'agency_documents_sent_at' => $agencyDocumentsSentAt,
                'documents_received_at' => $documentsReceivedAt,
                'new_license' => $newLicense,
                'new_license_received_at' => $newLicenseReceivedAt,
                'scrapped_at' => $scrappedAt,
                'updated_by_id' => $user->id,
            ];

            if (! $process) {
                $attributes['created_by_id'] = $user->id;
                $attributes['previous_license'] = $vehicle->license;
                $process = $vehicle->import_process()->create($attributes);
            } else {
                $process->update($attributes);
            }

            if ($newLicenseReceivedAt && $newLicense) {
                if (! $process->previous_license) {
                    $process->update(['previous_license' => $vehicle->license]);
                }

                $vehicle->license = $newLicense;
                $vehicle->saveQuietly();
            } elseif ($previousNewLicenseReceivedAt && $previousNewLicense && $process->previous_license
                && LicensePlate::normalize($vehicle->license) === LicensePlate::normalize($previousNewLicense)) {
                $vehicle->license = $process->previous_license;
                $vehicle->saveQuietly();
            }

            $this->syncDeadlineTask($vehicle, $process, $user);
            $this->syncDocumentsTask($vehicle, $process, $user, $previousDocumentsReceivedAt);
            $this->syncNewLicenseTasks($vehicle, $process, $user, $tollsRecipients);

            return $process->fresh();
        });
    }

    private function validateDecisionChange(?VehicleImportProcess $process, string $decision): void
    {
        if (! $process || $process->decision === $decision) {
            return;
        }

        $hasMilestones = $process->agency_documents_sent_at
            || $process->documents_received_at
            || $process->new_license_received_at
            || $process->scrapped_at;

        if ($hasMilestones) {
            throw ValidationException::withMessages([
                'import_decision' => 'Remova primeiro os marcos já registados antes de alterar a decisão.',
            ]);
        }
    }

    private function decisionAt(?VehicleImportProcess $process, string $decision, mixed $input): Carbon
    {
        if ($input) {
            return Carbon::parse($input);
        }

        if ($process && $process->decision === $decision && $process->decision_at) {
            return $process->decision_at->copy();
        }

        return now();
    }

    private function timestampFromCheckbox(array $data, string $checkbox, string $timestamp): ?Carbon
    {
        if (! filter_var($data[$checkbox] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        return ! empty($data[$timestamp]) ? Carbon::parse($data[$timestamp]) : now();
    }

    private function validateUniqueLicense(Vehicle $vehicle, string $license): void
    {
        $normalized = LicensePlate::normalize($license);
        $duplicate = Vehicle::withTrashed()
            ->whereKeyNot($vehicle->id)
            ->where(function ($query) use ($normalized): void {
                foreach (['license', 'foreign_license'] as $index => $column) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}(
                        "REPLACE(REPLACE(REPLACE(UPPER(COALESCE({$column}, '')), '-', ''), ' ', ''), '.', '') = ?",
                        [$normalized]
                    );
                }
            })
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'import_new_license' => sprintf('Já existe a viatura #%d com esta matrícula.', $duplicate->id),
            ]);
        }
    }

    private function syncDeadlineTask(Vehicle $vehicle, VehicleImportProcess $process, User $user): void
    {
        CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_IMPORT_DEADLINE)
            ->where('dedupe_key', '<>', $this->deadlineDedupeKey($vehicle, $process->decision))
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);

        $task = CalendarTask::firstOrNew([
            'dedupe_key' => $this->deadlineDedupeKey($vehicle, $process->decision),
        ]);
        $task->fill([
            'title' => sprintf(
                '%s da viatura %s',
                $process->decision === VehicleImportProcess::DECISION_LEGALIZE ? 'Legalizar' : 'Abater',
                $this->vehicleLabel($vehicle)
            ),
            'due_date' => $process->deadline_at->format(config('panel.date_format')),
            'notes' => 'Prazo criado automaticamente na Gestão de Importados / Adjudicações.',
            'vehicle_id' => $vehicle->id,
            'recipient_group' => CalendarTask::GROUP_MANAGEMENT,
            'type' => CalendarTask::TYPE_IMPORT_DEADLINE,
            'target_url' => $this->vehicleUrl($vehicle),
            'created_by_id' => $task->created_by_id ?: $user->id,
        ]);
        $task->completed_at = ($process->decision === VehicleImportProcess::DECISION_LEGALIZE && $process->new_license_received_at)
            || ($process->decision === VehicleImportProcess::DECISION_SCRAP && $process->scrapped_at)
                ? ($task->completed_at ?: now())
                : null;
        $task->save();
    }

    private function syncDocumentsTask(Vehicle $vehicle, VehicleImportProcess $process, User $user, mixed $previousTimestamp): void
    {
        $dedupeKey = "vehicle-import:{$vehicle->id}:ipo-documents-ready";

        if (! $process->documents_received_at) {
            CalendarTask::where('dedupe_key', $dedupeKey)->delete();

            return;
        }

        if ($previousTimestamp || ! RepairRules::hasOpenRepairs($vehicle->id)) {
            return;
        }

        $this->createOperationalTask(
            $vehicle,
            $user,
            $dedupeKey,
            CalendarTask::TYPE_IPO_DOCUMENTS_READY,
            'Documentos prontos para IPO — documentos recebidos: '.$this->vehicleLabel($vehicle),
            CalendarTask::GROUP_WORKSHOP,
        );
    }

    private function syncNewLicenseTasks(
        Vehicle $vehicle,
        VehicleImportProcess $process,
        User $user,
        Collection $tollsRecipients
    ): void {
        $workshopKey = "vehicle-import:{$vehicle->id}:new-license-workshop";
        $legacyTollsKey = "vehicle-import:{$vehicle->id}:new-license-tolls";

        if (! $process->new_license_received_at || ! $process->new_license) {
            CalendarTask::where('vehicle_id', $vehicle->id)
                ->whereIn('type', [CalendarTask::TYPE_NEW_LICENSE_WORKSHOP, CalendarTask::TYPE_NEW_LICENSE_TOLLS])
                ->delete();

            return;
        }

        $this->createOperationalTask(
            $vehicle,
            $user,
            $workshopKey,
            CalendarTask::TYPE_NEW_LICENSE_WORKSHOP,
            'Nova matrícula recebida: '.$process->new_license.' — viatura #'.$vehicle->id,
            CalendarTask::GROUP_WORKSHOP,
        );

        CalendarTask::where('dedupe_key', $legacyTollsKey)->delete();
        CalendarTask::where('vehicle_id', $vehicle->id)
            ->where('type', CalendarTask::TYPE_NEW_LICENSE_TOLLS)
            ->whereNotIn('assigned_to_id', $tollsRecipients->pluck('id'))
            ->delete();

        foreach ($tollsRecipients as $tollsRecipient) {
            $tollsKey = "vehicle-import:{$vehicle->id}:new-license-tolls:user:{$tollsRecipient->id}";
            $task = CalendarTask::firstOrNew(['dedupe_key' => $tollsKey]);
            $task->fill([
                'title' => 'Adicionar '.$process->new_license.' ao alerta de portagens',
                'due_date' => now()->format(config('panel.date_format')),
                'notes' => 'Tarefa criada automaticamente após a receção da nova matrícula.',
                'vehicle_id' => $vehicle->id,
                'assigned_to_id' => $tollsRecipient->id,
                'type' => CalendarTask::TYPE_NEW_LICENSE_TOLLS,
                'target_url' => $this->vehicleUrl($vehicle),
                'created_by_id' => $task->created_by_id ?: $user->id,
            ]);
            $task->save();
        }
    }

    private function createOperationalTask(
        Vehicle $vehicle,
        User $user,
        string $dedupeKey,
        string $type,
        string $title,
        string $recipientGroup
    ): void {
        $task = CalendarTask::firstOrNew(['dedupe_key' => $dedupeKey]);
        $task->fill([
            'title' => $title,
            'due_date' => now()->format(config('panel.date_format')),
            'notes' => 'Tarefa operacional criada automaticamente.',
            'vehicle_id' => $vehicle->id,
            'recipient_group' => $recipientGroup,
            'type' => $type,
            'target_url' => $this->vehicleUrl($vehicle),
            'created_by_id' => $task->created_by_id ?: $user->id,
        ]);
        $task->save();
    }

    private function deadlineDedupeKey(Vehicle $vehicle, string $decision): string
    {
        return "vehicle-import:{$vehicle->id}:deadline:{$decision}";
    }

    private function vehicleUrl(Vehicle $vehicle): string
    {
        return route('admin.vehicles.edit', $vehicle).'#vehicle-import-adjudication';
    }

    private function vehicleLabel(Vehicle $vehicle): string
    {
        return $vehicle->license ?: $vehicle->foreign_license ?: '#'.$vehicle->id;
    }
}

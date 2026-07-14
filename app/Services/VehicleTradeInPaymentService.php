<?php

namespace App\Services;

use App\Models\Client;
use App\Models\GeneralState;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Models\VehicleTradeIn;
use Illuminate\Validation\ValidationException;

class VehicleTradeInPaymentService
{
    public function validate(array $data): void
    {
        $errors = [];
        $normalizedLicense = VehicleTradeIn::normalizeLicense((string) ($data['trade_in_license'] ?? ''));

        if ($normalizedLicense === '') {
            $errors['trade_in_license'] = 'Indique a matricula da retoma.';
        }

        $existingVehicle = $normalizedLicense !== '' ? $this->findVehicle($normalizedLicense) : null;

        if (! $existingVehicle) {
            foreach ([
                'trade_in_brand_id' => 'Indique a marca da retoma.',
                'trade_in_model' => 'Indique o modelo da retoma.',
                'trade_in_year' => 'Indique o ano da retoma.',
                'trade_in_kilometers' => 'Indique os quilometros da retoma.',
            ] as $field => $message) {
                if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                    $errors[$field] = $message;
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function create(Client $client, array $data, ?int $userId): VehicleTradeIn
    {
        $normalizedLicense = VehicleTradeIn::normalizeLicense((string) $data['trade_in_license']);
        $supplier = $this->supplierForClient($client);
        $vehicle = $this->findVehicle($normalizedLicense);
        $vehicleAlreadyExisted = (bool) $vehicle;

        if ($vehicle) {
            if ($vehicle->trashed()) {
                $vehicle->restore();
            }

            $vehicle->update([
                'purchase_price' => $data['amount'],
                'suplier_id' => $supplier->id,
                'acquisition_notes' => trim(
                    trim((string) ($vehicle->acquisition_notes ?? ''))."\n".
                    'Retoma recebida como pagamento do cliente '.$client->name.' em '.$data['paid_at'].'.'
                ),
            ]);
        } else {
            $vehicle = Vehicle::create([
                'license' => trim((string) $data['trade_in_license']),
                'general_state_id' => $this->requiredStockStateId(),
                'brand_id' => $data['trade_in_brand_id'],
                'model' => $data['trade_in_model'],
                'year' => $data['trade_in_year'],
                'kilometers' => $data['trade_in_kilometers'],
                'purchase_price' => $data['amount'],
                'suplier_id' => $supplier->id,
                'acquisition_notes' => 'Retoma recebida como pagamento do cliente '.$client->name.'.',
            ]);
        }

        return VehicleTradeIn::create([
            'created_by_id' => $userId,
            'converted_by_id' => $vehicleAlreadyExisted ? $userId : null,
            'created_vehicle_id' => $vehicle->id,
            'license' => trim((string) $data['trade_in_license']),
            'normalized_license' => $normalizedLicense,
            'amount' => $data['amount'],
            'status' => $vehicleAlreadyExisted ? VehicleTradeIn::STATUS_CONVERTED : VehicleTradeIn::STATUS_PENDING,
            'converted_at' => $vehicleAlreadyExisted ? now() : null,
            'notes' => trim('Pagamento do cliente '.$client->name.'. '.($data['notes'] ?? '')) ?: null,
            'has_vehicle_delivery_declaration' => true,
        ]);
    }

    private function supplierForClient(Client $client): Suplier
    {
        return Suplier::firstOrCreate([
            'name' => $client->name,
        ], [
            'email' => $client->email,
            'phone' => $client->phone,
            'nif' => $client->vat,
            'address' => trim(implode(' ', array_filter([$client->address, $client->zip, $client->location]))),
            'active' => true,
            'notes' => 'Criado automaticamente a partir de pagamento por retoma do cliente #'.$client->id.'.',
        ]);
    }

    private function findVehicle(string $normalizedLicense): ?Vehicle
    {
        return Vehicle::withTrashed()
            ->whereRaw("REPLACE(REPLACE(UPPER(license), '-', ''), ' ', '') = ?", [$normalizedLicense])
            ->orWhereRaw("REPLACE(REPLACE(UPPER(foreign_license), '-', ''), ' ', '') = ?", [$normalizedLicense])
            ->first();
    }

    private function requiredStockStateId(): int
    {
        $stockStateId = GeneralState::query()
            ->whereRaw('LOWER(name) = ?', ['em stock disponível'])
            ->orWhereRaw('LOWER(name) = ?', ['em stock disponivel'])
            ->orWhereRaw('LOWER(name) = ?', ['stand'])
            ->orderByRaw("CASE WHEN LOWER(name) IN ('em stock disponível', 'em stock disponivel') THEN 0 ELSE 1 END")
            ->value('id');

        if ($stockStateId) {
            return (int) $stockStateId;
        }

        throw ValidationException::withMessages([
            'trade_in_license' => 'Nao foi encontrado estado de stock para criar a viatura.',
        ]);
    }
}

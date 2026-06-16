<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use App\Models\VehicleTradeIn;
use App\Domain\Consignments\ConsignmentRules;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_edit');
    }

    public function rules()
    {
        return [
            'general_state_id' => [
                'required',
                'integer',
            ],
            'license' => [
                'string',
                'nullable',
            ],
            'foreign_license' => [
                'string',
                'nullable',
            ],
            'our_registration' => [
                'nullable',
                'in:ARC,RRS,GER',
            ],
            'model' => [
                'string',
                'nullable',
            ],
            'version' => [
                'string',
                'nullable',
            ],
            'year' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'month' => [
                'string',
                'nullable',
            ],
            'license_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'color' => [
                'string',
                'nullable',
            ],
            'fuel' => [
                'string',
                'nullable',
            ],
            'kilometers' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'inspec_b' => [
                'string',
                'nullable',
            ],
            'date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'documents' => [
                'array',
            ],
            'additional_documents' => [
                'array',
            ],
            'photos' => [
                'array',
            ],
            'payment_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'iuc_paid_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'iuc_paid_value' => [
                'nullable',
                'numeric',
            ],
            'tow_paid_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'tow_paid_value' => [
                'nullable',
                'numeric',
            ],
            'invoice' => [
                'array',
            ],
            'is_invoiced' => [
                'nullable',
                'boolean',
            ],
            'inicial' => [
                'array',
            ],
            'storage_location' => [
                'string',
                'nullable',
            ],
            'withdrawal_authorization' => [
                'string',
                'nullable',
            ],
            'withdrawal_authorization_file' => [
                'array',
            ],
            'withdrawal_authorization_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'withdrawal_documents' => [
                'array',
            ],
            'pickup_state_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'client_registration' => [
                'string',
                'nullable',
            ],
            'chekin_documents' => [
                'string',
                'nullable',
            ],
            'chekin_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'chekout_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'sale_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'sele_chekout' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
            'first_key' => [
                'string',
                'nullable',
            ],
            'scuts' => [
                'string',
                'nullable',
            ],
            'key' => [
                'string',
                'nullable',
            ],
            'manuals' => [
                'string',
                'nullable',
            ],
            'elements_with_vehicle' => [
                'string',
                'nullable',
            ],
            'local' => [
                'string',
                'nullable',
            ],
            'engine_displacement' => [
                'string',
                'nullable',
            ],
            'commission' => [
                'nullable',
                'numeric',
            ],
            'iuc_price' => [
                'nullable',
                'numeric',
            ],
            'mes_iuc' => [
                'nullable',
                'string',
                'max:20',
            ],
            'purchase_has_vat' => [
                'nullable',
                'boolean',
            ],
            'purchase_vat_value' => [
                'nullable',
                'numeric',
            ],
            'acquisition_notes' => [
                'nullable',
                'string',
            ],
            'supplier_payment_date' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'supplier_payment_amount' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'supplier_payment_method_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],
            'supplier_payment_proof' => [
                'nullable',
                'file',
                'max:10240',
            ],
            'generic_payment_expense_label' => [
                'nullable',
                'string',
                'max:191',
            ],
            'generic_payment_date' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'generic_payment_amount' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'generic_payment_method_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],
            'generic_payment_proof' => [
                'nullable',
                'file',
                'max:10240',
            ],
            'client_payment_date' => [
                'nullable',
                'date_format:' . config('panel.date_format'),
            ],
            'client_payment_amount' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'client_payment_method_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],
            'client_payment_proof' => [
                'nullable',
                'file',
                'max:10240',
            ],
            'client_payment_method_info_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],
            'financial_institution_id' => [
                'nullable',
                'integer',
                'exists:financial_institutions,id',
            ],
            'client_financed_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'client_financing_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $vehicle = $this->route('vehicle');
            $currentVehicleId = $vehicle instanceof Vehicle ? (int) $vehicle->getKey() : (int) $vehicle;

            if (! $currentVehicleId) {
                return;
            }

            $boundVehicle = $vehicle instanceof Vehicle ? $vehicle : Vehicle::find($currentVehicleId);
            $this->validateUniqueNormalizedLicense($validator, 'license', $currentVehicleId, $boundVehicle);
            $this->validateUniqueNormalizedLicense($validator, 'foreign_license', $currentVehicleId, $boundVehicle);

            $incomingSaleDate = $this->input('sale_date');
            if ($boundVehicle && ConsignmentRules::shouldBlockSale($boundVehicle, $incomingSaleDate)) {
                $validator->errors()->add('sale_date', 'Nao e possivel vender com consignacao ativa.');
            }

            if ($boundVehicle && $this->isSaleDateBeingSet($boundVehicle) && $this->saleOutstandingAfterRequest($boundVehicle) > 0.004) {
                $validator->errors()->add(
                    'sale_date',
                    'Para fechar a venda, a divida do cliente tem de ficar saldada por pagamento ou retoma.'
                );
            }

            $date = $this->input('supplier_payment_date');
            $amount = $this->input('supplier_payment_amount');
            $method = $this->input('supplier_payment_method_id');
            $proof = $this->file('supplier_payment_proof');
            $filledCount = collect([$date, $amount, $method, $proof])->filter(fn ($v) => $v !== null && $v !== '')->count();

            if ($filledCount > 0 && $filledCount < 4) {
                $validator->errors()->add(
                    'supplier_payment_amount',
                    'Para registar um pagamento faseado, preencha data, valor, meio de pagamento e comprovativo.'
                );
            }

            $genericDescription = $this->input('generic_payment_expense_label');
            $genericDate = $this->input('generic_payment_date');
            $genericAmount = $this->input('generic_payment_amount');
            $genericMethod = $this->input('generic_payment_method_id');
            $genericProof = $this->file('generic_payment_proof');
            $genericFilledCount = collect([$genericDescription, $genericDate, $genericAmount, $genericMethod, $genericProof])
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->count();

            if ($genericFilledCount > 0 && $genericFilledCount < 5) {
                $validator->errors()->add(
                    'generic_payment_amount',
                    'Para registar um pagamento generico, preencha despesa, data, valor, meio de pagamento e comprovativo.'
                );
            }

            $clientDate = $this->input('client_payment_date');
            $clientAmount = $this->input('client_payment_amount');
            $clientMethod = $this->input('client_payment_method_id');
            $clientProof = $this->file('client_payment_proof');
            $clientFilledCount = collect([$clientDate, $clientAmount, $clientMethod, $clientProof])
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->count();

            if ($clientFilledCount > 0 && $clientFilledCount < 4) {
                $validator->errors()->add(
                    'client_payment_amount',
                    'Para registar um pagamento de cliente, preencha data, valor, meio de pagamento e comprovativo.'
                );
            }
        });
    }

    private function saleOutstandingAfterRequest(Vehicle $vehicle): float
    {
        $salesTotal = $this->moneyInput('pvp', $vehicle->pvp)
            + $this->moneyInput('sales_iuc', $vehicle->sales_iuc)
            + $this->moneyInput('sales_tow', $vehicle->sales_tow)
            + $this->moneyInput('sales_transfer', $vehicle->sales_transfer)
            + $this->moneyInput('sales_others', $vehicle->sales_others);

        $clientPaymentsTotal = (float) $vehicle->client_payments()->sum('amount');
        $incomingClientPayment = $this->input('client_payment_amount');
        if ($incomingClientPayment !== null && $incomingClientPayment !== '') {
            $clientPaymentsTotal += (float) $incomingClientPayment;
        }

        $tradeInsTotal = (float) $vehicle->trade_ins()
            ->where('status', VehicleTradeIn::STATUS_CONVERTED)
            ->sum('amount');

        return round($salesTotal - $clientPaymentsTotal - $tradeInsTotal, 2);
    }

    private function isSaleDateBeingSet(Vehicle $vehicle): bool
    {
        return $this->hasFilledInput('sale_date') && ! $this->hasFilledValue($vehicle->sale_date);
    }

    private function hasFilledInput(string $field): bool
    {
        return $this->hasFilledValue($this->input($field));
    }

    private function hasFilledValue(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    private function moneyInput(string $field, mixed $fallback): float
    {
        $value = $this->input($field, $fallback);

        return $value === null || $value === '' ? 0.0 : (float) $value;
    }

    private function validateUniqueNormalizedLicense(Validator $validator, string $field, int $currentVehicleId, ?Vehicle $currentVehicle): void
    {
        $normalizedLicense = $this->normalizeLicense((string) $this->input($field, ''));

        if ($normalizedLicense === '') {
            return;
        }

        if ($currentVehicle) {
            $currentLicenses = [
                $this->normalizeLicense((string) $currentVehicle->license),
                $this->normalizeLicense((string) $currentVehicle->foreign_license),
            ];

            if (in_array($normalizedLicense, $currentLicenses, true)) {
                return;
            }
        }

        $existingVehicle = Vehicle::withTrashed()
            ->where('id', '!=', $currentVehicleId)
            ->where(function ($query) use ($normalizedLicense) {
                $query
                    ->whereRaw("REPLACE(REPLACE(UPPER(license), '-', ''), ' ', '') = ?", [$normalizedLicense])
                    ->orWhereRaw("REPLACE(REPLACE(UPPER(foreign_license), '-', ''), ' ', '') = ?", [$normalizedLicense]);
            })
            ->first(['id', 'license', 'foreign_license', 'deleted_at']);

        if (! $existingVehicle) {
            return;
        }

        if ((int) $existingVehicle->id === $currentVehicleId) {
            return;
        }

        $validator->errors()->add(
            $field,
            sprintf(
                'Ja existe uma viatura com esta matricula: #%d %s.',
                $existingVehicle->id,
                $existingVehicle->license ?: $existingVehicle->foreign_license ?: ''
            )
        );
    }

    private function normalizeLicense(string $license): string
    {
        $license = Str::upper(trim($license));

        return preg_replace('/[\s-]+/', '', $license) ?? '';
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use App\Domain\Consignments\ConsignmentRules;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
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
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $vehicle = $this->route('vehicle');
            if (! $vehicle) {
                return;
            }

            $incomingSaleDate = $this->input('sale_date');
            if (ConsignmentRules::shouldBlockSale($vehicle, $incomingSaleDate)) {
                $validator->errors()->add('sale_date', 'Nao e possivel vender com consignacao ativa.');
            }
        });
    }
}

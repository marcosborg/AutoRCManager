<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use App\Support\RolePreview;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreVehicleRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_create');
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
            'suplier_id' => ['nullable', 'integer', 'exists:supliers,id'],
            'destination_company' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(Vehicle::DESTINATION_COMPANIES))],
            'our_registration' => [
                'nullable',
                'string',
                'max:255',
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
                'date_format:'.config('panel.date_format'),
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
                'date_format:'.config('panel.date_format'),
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
            'initial_photo_files' => ['sometimes', 'array', 'max:10'],
            'initial_photo_files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'vehicle_photo_files' => ['sometimes', 'array', 'max:10'],
            'vehicle_photo_files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'payment_date' => [
                'date_format:'.config('panel.date_format'),
                'nullable',
            ],
            'iuc_paid_date' => [
                'date_format:'.config('panel.date_format'),
                'nullable',
            ],
            'iuc_paid_value' => [
                'nullable',
                'numeric',
            ],
            'tow_paid_date' => [
                'date_format:'.config('panel.date_format'),
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
                'date_format:'.config('panel.date_format'),
                'nullable',
            ],
            'withdrawal_documents' => [
                'array',
            ],
            'pickup_state_date' => [
                'date_format:'.config('panel.date_format'),
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
                'date_format:'.config('panel.date_format'),
                'nullable',
            ],
            'chekout_date' => [
                'date_format:'.config('panel.date_format'),
                'nullable',
            ],
            'sale_date' => [
                'date_format:'.config('panel.date_format'),
                'nullable',
            ],
            'sele_chekout' => [
                'date_format:'.config('panel.date_format'),
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
                RolePreview::hasAnyEffectiveRole($this->user(), ['Stand', 'Stand Adm']) ? 'required' : 'nullable',
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
            'financial_institution_id' => [
                'nullable',
                'integer',
                'exists:financial_institutions,id',
            ],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $files = array_merge(
                (array) $this->file('initial_photo_files', []),
                (array) $this->file('vehicle_photo_files', [])
            );
            $totalSize = collect($files)->sum(fn ($file) => $file instanceof \Illuminate\Http\UploadedFile && $file->isValid() ? $file->getSize() : 0);
            if ($totalSize > 6 * 1024 * 1024) {
                $validator->errors()->add('initial_photo_files', 'As fotografias não podem ultrapassar 6 MB no total.');
            }
            $this->validateUniqueNormalizedLicense($validator, 'license');
            $this->validateUniqueNormalizedLicense($validator, 'foreign_license');
        });
    }

    public function attributes(): array
    {
        return [
            'initial_photo_files' => 'fotografias na aquisição',
            'initial_photo_files.*' => 'fotografia na aquisição',
            'vehicle_photo_files' => 'fotografias atuais',
            'vehicle_photo_files.*' => 'fotografia atual',
        ];
    }

    private function validateUniqueNormalizedLicense(Validator $validator, string $field): void
    {
        $normalizedLicense = $this->normalizeLicense((string) $this->input($field, ''));

        if ($normalizedLicense === '') {
            return;
        }

        $existingVehicle = Vehicle::withTrashed()
            ->where(function ($query) use ($normalizedLicense) {
                $query
                    ->whereRaw("REPLACE(REPLACE(UPPER(license), '-', ''), ' ', '') = ?", [$normalizedLicense])
                    ->orWhereRaw("REPLACE(REPLACE(UPPER(foreign_license), '-', ''), ' ', '') = ?", [$normalizedLicense]);
            })
            ->first(['id', 'license', 'foreign_license', 'deleted_at']);

        if (! $existingVehicle) {
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

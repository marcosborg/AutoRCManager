<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Gate;

class VehicleResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        if (Gate::denies('financial_sensitive_access')) {
            foreach ([
                'purchase_price',
                'purchase_has_vat',
                'purchase_vat_value',
                'commission',
                'iuc_price',
                'tow_price',
                'acquisition_notes',
            ] as $field) {
                unset($data[$field]);
            }
        }

        return $data;
    }
}

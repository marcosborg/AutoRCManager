<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientLedgerEntryRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('client_ledger_entry_create');
    }

    public function rules()
    {
        return [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                'exists:vehicles,id',
            ],
            'entry_type' => [
                'required',
                'in:debit,credit',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'entry_date' => [
                'required',
                'date',
            ],
            'description' => [
                'required',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'attachment' => [
                'nullable',
                'file',
            ],
        ];
    }
}

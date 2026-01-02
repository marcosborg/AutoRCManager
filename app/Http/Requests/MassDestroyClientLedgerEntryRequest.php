<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

class MassDestroyClientLedgerEntryRequest extends FormRequest
{
    public function authorize()
    {
        abort_if(Gate::denies('client_ledger_entry_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return true;
    }

    public function rules()
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'exists:client_ledger_entries,id',
        ];
    }
}

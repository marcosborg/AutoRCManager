<?php

namespace App\Http\Requests;

use App\Models\OficinaExpertiseProcess;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOficinaExpertiseProcessRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('oficina_expertise_process_create');
    }

    public function rules()
    {
        return [
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'license' => ['nullable', 'string', 'max:50'],
            'insurance_company' => ['nullable', 'string', 'max:255'],
            'claim_number' => ['nullable', 'string', 'max:255'],
            'process_number' => ['nullable', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'scheduled_expertise_date' => ['nullable', 'date'],
            'expert_name' => ['nullable', 'string', 'max:255'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'approval_date' => ['nullable', 'date'],
            'repair_start_date' => ['nullable', 'date'],
            'expected_repair_date' => ['nullable', 'date'],
            'repair_completed_date' => ['nullable', 'date'],
            'insurance_validation_date' => ['nullable', 'date'],
            'invoice_sent_date' => ['nullable', 'date'],
            'payment_received_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(OficinaExpertiseProcess::STATUS_SELECT))],
            'repair_type' => ['nullable', Rule::in(array_keys(OficinaExpertiseProcess::REPAIR_TYPE_SELECT))],
            'notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
            'status_notes' => ['nullable', 'string'],
            'expertise_report.*' => ['nullable', 'file', 'max:20480'],
            'proofs.*' => ['nullable', 'file', 'max:20480'],
            'initial_photos.*' => ['nullable', 'file', 'max:20480'],
            'final_photos.*' => ['nullable', 'file', 'max:20480'],
            'sent_invoice.*' => ['nullable', 'file', 'max:20480'],
            'payment_proof.*' => ['nullable', 'file', 'max:20480'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('vehicle_id') && ! $this->filled('license')) {
                $validator->errors()->add('vehicle_id', 'Escolha uma viatura ou indique uma matrícula.');
            }

            if ($this->input('status') === OficinaExpertiseProcess::STATUS_CANCELLED && ! $this->filled('rejection_reason')) {
                $validator->errors()->add('rejection_reason', 'Indique o motivo para cancelar o processo.');
            }

            if ($this->input('status') === OficinaExpertiseProcess::STATUS_CLOSED && ! $this->filled('payment_received_date')) {
                $validator->errors()->add('payment_received_date', 'O processo só pode ser fechado depois de registar o pagamento recebido.');
            }
        });
    }
}

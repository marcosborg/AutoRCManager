<?php

namespace App\Services;

use App\Models\AccountOperation;
use App\Models\Department;
use App\Models\ExternalService;
use App\Models\PartOrder;
use App\Models\PartOrderItem;
use App\Models\PartPayment;
use App\Models\Vehicle;

/** Explains the existing calculation; never writes data or substitutes a new total. */
class VehicleCostReviewService
{
    public function build(Vehicle $vehicle, ?array $profitability = null): array
    {
        $profitability ??= app(VehicleProfitabilityService::class)->build($vehicle);
        $vehicle->loadMissing([
            'purchase_price_histories', 'repairs.parts', 'repairs.workLogs',
            'repairs.part_orders.items', 'repairs.part_orders.payments',
            'part_orders.items', 'part_orders.payments', 'external_services',
            'generic_payments', 'supplier_payments', 'paintingJobs',
        ]);

        $rows = [];
        $payments = [];
        $warnings = [];
        $purchaseHistory = $vehicle->purchase_price_histories->where('reason', 'workshop_sale')->sortBy('id')->first();
        $rows[] = $this->row('purchase_origin', 'Compra', 'Compra de origem',
            $purchaseHistory ? $purchaseHistory->previous_purchase_price : $vehicle->purchase_price,
            true, 'Registado', 'Consultar pagamentos ao fornecedor', 'vehicle', $vehicle->id,
            'admin.vehicles.edit', [$vehicle->id], $purchaseHistory
                ? ['Valor anterior à primeira transferência para a oficina, no histórico #'.$purchaseHistory->id.'.']
                : ['Valor de compra da ficha da viatura.']);

        foreach (['purchase_vat_value' => 'IVA da compra', 'iuc_price' => 'IUC', 'commission' => 'Comissão', 'tow_price' => 'Reboque'] as $field => $label) {
            if ((float) $vehicle->{$field} == 0.0) {
                continue;
            }
            $rows[] = $this->row('acquisition_'.$field, 'Campos da aquisição', $label, $vehicle->{$field},
                false, 'Registado', 'Não identificado por parcela', 'vehicle', $vehicle->id,
                'admin.vehicles.edit', [$vehicle->id], ['Entra nas despesas de aquisição, mas este campo não é somado diretamente no custo global.', 'Pode já estar representado noutro lançamento; conferir antes de acrescentar.']);
            $warnings['acquisition_fields_omitted'] = 'Há campos da aquisição que não entram diretamente no custo global. Confirme se já foram lançados noutra origem antes de os somar.';
        }

        $repairPartsCount = 0;
        foreach ($vehicle->repairs as $repair) {
            foreach ($repair->parts as $part) {
                $repairPartsCount++;
                $notes = ['Reparação #'.$repair->id.'; peça #'.$part->id.'.'];
                if ($part->invoice_number) {
                    $notes[] = 'Fatura: '.$part->invoice_number.'.';
                }
                $rows[] = $this->row('repair_part_'.$part->id, 'Peças', $part->part_name ?: 'Peça de reparação',
                    $part->amount, true, 'Registado', 'Não identificado', 'repair', $repair->id,
                    'admin.repairs.show', [$repair->id], $notes);
            }
        }

        // Match the existing calculator: the same order can be linked both ways.
        $orders = $vehicle->part_orders->concat($vehicle->repairs->flatMap(fn ($repair) => $repair->part_orders))->unique('id');
        $orderItemsCount = 0;
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $orderItemsCount++;
                $hasFinal = $item->total_final !== null;
                $hasEstimate = $item->total_estimated !== null;
                $notes = ['Encomenda #'.$order->id.' / linha #'.$item->id.'.',
                    'Encomenda: '.(PartOrder::STATUS_SELECT[$order->status] ?? $order->status).'; linha: '.(PartOrderItem::STATUS_SELECT[$item->status] ?? $item->status).'.',
                    'Quantidade encomendada: '.(float) $item->quantity.'; recebida: '.$item->receivedAmount().'.',
                    'O cálculo atual inclui a quantidade encomendada. Os pagamentos da encomenda não estão repartidos por linha.'];
                if ($order->status === 'cancelled' || $item->status === 'returned' || ! in_array($item->status, ['received', 'installed'], true)) {
                    $warnings['order_states_included'] = 'O custo atual inclui linhas de encomendas canceladas, devolvidas ou ainda não totalmente recebidas. Os valores foram mantidos para conferência.';
                    $notes[] = 'Conferir inclusão: o estado atual não exclui esta linha do custo.';
                }
                if (! $hasFinal && $hasEstimate) {
                    $warnings['estimated_values_included'] = 'Há valores estimados de encomendas incluídos no custo atual; não equivalem a custo final confirmado.';
                }
                if (! $hasFinal && ! $hasEstimate) {
                    $notes[] = 'Sem valor final nem estimativa: o cálculo atual usa zero; isto não confirma custo nulo.';
                    $warnings['missing_order_values'] = 'Há linhas de encomenda sem valor final nem estimativa; o cálculo atual usa zero.';
                }
                $rows[] = $this->row('order_item_'.$item->id, 'Peças', $item->description ?: 'Linha de encomenda',
                    $item->total_final ?? $item->total_estimated ?? 0, true,
                    $hasFinal ? 'Valor final registado' : ($hasEstimate ? 'Estimado' : 'Por confirmar'),
                    'Consultar pagamentos da encomenda', 'part_order', $order->id,
                    'admin.part-orders.show', [$order->id], $notes);
            }
            foreach ($order->payments as $payment) {
                $payments[] = $this->row('part_payment_'.$payment->id, 'Pagamentos de encomendas', 'Pagamento #'.$payment->id.' — encomenda #'.$order->id,
                    $payment->amount, false, 'Registado', PartPayment::STATUS_SELECT[$payment->payment_status] ?? 'Por confirmar',
                    'part_payment', $payment->id, 'admin.part-payments.edit', [$payment->id],
                    ['Valor do registo de pagamento; apenas o estado “Pago” declara este valor como pago.', 'Um registo parcialmente pago não indica aqui quanto foi efetivamente liquidado. Não é somado novamente ao custo.']);
            }
        }
        if ($repairPartsCount > 0 && $orderItemsCount > 0) {
            $warnings['repair_order_overlap'] = 'Existem peças na reparação e nas encomendas. São duas origens somadas pelo cálculo atual: confirme se alguma corresponde à mesma peça. Não foi comprovado um duplicado.';
        }

        foreach ($vehicle->external_services as $service) {
            $included = $service->status !== 'cancelled';
            $rows[] = $this->row('external_service_'.$service->id, 'Serviços externos', $service->description ?: 'Serviço externo',
                $service->amount, $included, 'Registado', 'Não identificado', 'external_service', $service->id,
                'admin.external-services.edit', [$service->id], ['Estado: '.(ExternalService::STATUS_SELECT[$service->status] ?? $service->status).'.',
                    $included ? 'O valor registado entra no custo; o estado do serviço não prova o pagamento.' : 'Serviço cancelado: excluído do custo atual.']);
        }
        foreach ($vehicle->generic_payments as $payment) {
            $rows[] = $this->row('generic_payment_'.$payment->id, 'Despesas diversas', $payment->expense_label ?: 'Despesa da viatura',
                $payment->amount, true, 'Registado', $payment->getRawOriginal('paid_at') ? 'Pagamento registado' : 'Data de pagamento por confirmar',
                'vehicle', $vehicle->id, 'admin.vehicles.edit', [$vehicle->id], ['Despesa #'.$payment->id.'.', 'Entra também no resumo de aquisição; aparece uma única vez neste detalhe do custo global.']);
        }
        foreach ($vehicle->supplier_payments as $payment) {
            $payments[] = $this->row('supplier_payment_'.$payment->id, 'Pagamentos da compra', 'Pagamento ao fornecedor #'.$payment->id,
                $payment->amount, false, 'Registado', $payment->getRawOriginal('paid_at') ? 'Pagamento registado' : 'Data de pagamento por confirmar',
                'vehicle', $vehicle->id, 'admin.vehicles.edit', [$vehicle->id], ['Informação de liquidação; a compra já está incluída no custo e não é somada novamente.', 'Se houve transferência interna, este pagamento não identifica por si só qual a etapa da compra liquidada.']);
        }

        $departments = Department::query()->whereRaw('LOWER(name) IN (?, ?)', ['oficina', 'stand'])->get()->keyBy('id');
        $operations = $departments->isEmpty() ? collect() : AccountOperation::query()
            ->where('vehicle_id', $vehicle->id)->whereIn('department_id', $departments->keys())
            ->where('movement_type', AccountOperation::TYPE_OUTCOME)->orderBy('id')->get();
        foreach ($operations as $operation) {
            $departmentName = $departments[$operation->department_id]->name;
            $rows[] = $this->row('account_operation_'.$operation->id, 'Caixa '.$departmentName,
                $operation->description ?: 'Saída de caixa', $operation->total, true, 'Registado', 'Saída de caixa registada',
                'account_operation', $operation->id, null, [], ['Movimento #'.$operation->id.'.', 'Uma saída de caixa pode liquidar uma despesa já registada noutra origem; conferir a correspondência.']);
        }
        if ($operations->isNotEmpty() && ($repairPartsCount || $orderItemsCount || $vehicle->generic_payments->isNotEmpty() || $vehicle->external_services->where('status', '!=', 'cancelled')->isNotEmpty())) {
            $warnings['cash_expense_overlap'] = 'Existem saídas de caixa e outras despesas nesta viatura. Confirme se alguma saída paga uma despesa já contada. A existência de ambas não prova duplicação.';
        }
        $minutes = (float) $vehicle->repairs->flatMap(fn ($repair) => $repair->workLogs)->sum('duration_minutes');
        if ($minutes > 0 || $vehicle->paintingJobs->isNotEmpty()) {
            $warnings['labor_not_costed'] = 'Existem registos de trabalho ou pintura. O custo atual não converte automaticamente tempos e materiais em euros; podem existir lançamentos manuais noutras origens.';
        }
        if ($profitability['has_workshop_transfer'] ?? false) {
            $warnings['workshop_margin_label'] = 'Com transferência interna, a fórmula atual de “Margem Oficina” coincide com os custos da oficina. A designação e a regra precisam de confirmação; não foram alteradas.';
        }

        $missingGroups = collect($rows)->concat($payments)->where('amount_missing', true)->pluck('group')->unique()->values();
        if ($missingGroups->isNotEmpty()) {
            $warnings['missing_source_values'] = 'Há montantes por confirmar em: '.$missingGroups->implode(', ').'. O zero apresentado por ausência de valor não confirma custo nem pagamento nulo.';
        }

        $sourceTotal = round(collect($rows)->where('included', true)->sum('amount'), 2);
        $currentTotal = (float) $profitability['global_cost'];
        $reconciles = abs($sourceTotal - $currentTotal) < 0.01;
        if (! $reconciles) {
            $warnings['source_reconciliation'] = 'O detalhe não coincide com o total atual. Reveja os registos antes de tirar conclusões; o total original foi mantido.';
        }

        return [
            'current_total' => $currentTotal,
            'acquisition_total' => (float) $vehicle->acquisition_expenses_total,
            'source_total' => $sourceTotal,
            'reconciles' => $reconciles,
            'rows' => $rows,
            'payment_rows' => $payments,
            'warnings' => collect($warnings)->map(fn ($message, $code) => compact('code', 'message'))->values()->all(),
        ];
    }

    private function row(string $id, string $group, string $label, $amount, bool $included, string $valueStatus, string $paymentStatus,
        string $sourceType, $sourceId, ?string $sourceRoute, array $sourceParameters = [], array $notes = []): array
    {
        $amountMissing = $amount === null;
        if ($amountMissing) {
            $valueStatus = 'Por confirmar';
            $notes[] = $included
                ? 'Sem montante registado: o cálculo atual usa zero; isto não confirma custo nulo.'
                : 'Sem montante registado: o zero apresentado não confirma um valor nulo.';
            if (in_array($paymentStatus, ['Pago', 'Pagamento registado', 'Saída de caixa registada'], true)) {
                $paymentStatus = 'Montante do pagamento por confirmar';
            }
        }

        return [
            'id' => $id, 'group' => $group, 'label' => $label, 'amount' => (float) $amount,
            'amount_missing' => $amountMissing,
            'included' => $included, 'value_status' => $valueStatus, 'payment_status' => $paymentStatus,
            'source_type' => $sourceType, 'source_id' => $sourceId, 'source_route' => $sourceRoute,
            'source_parameters' => $sourceParameters, 'notes' => $notes,
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartPaymentRequest;
use App\Http\Requests\UpdatePartPaymentRequest;
use App\Models\PartOrder;
use App\Models\PartPayment;
use App\Models\Suplier;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartPaymentController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('part_payment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate(['export' => ['nullable', 'in:csv']]);
        \App\Support\AmountRange::validate($request);

        $query = PartPayment::with(['part_order.vehicle.brand', 'suplier', 'paid_by'])->orderByDesc('id');
        foreach (['amount_min' => '>=', 'amount_max' => '<='] as $field => $operator) {
            if ($request->filled($field)) $query->where('amount', $operator, $request->input($field));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('suplier_id')) {
            $query->where('suplier_id', $request->suplier_id);
        }

        if ($request->boolean('overdue')) {
            $query->whereDate('due_date', '<', now()->toDateString())->whereNotIn('payment_status', ['paid', 'cancelled']);
        }

        if ($request->input('export') === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['ID', 'Encomenda', 'Fornecedor', 'Valor', 'Vencimento', 'Estado', 'Data pagamento', 'Método', 'Condição', 'Referência', 'Notas'], ';', '"', '');
                foreach ($query->lazy(200) as $payment) {
                    $supplier = (string) ($payment->suplier?->name ?? '');
                    if (preg_match('/^\s*[=+@-]/u', $supplier)) $supplier = "'" . $supplier;
                    $status = PartPayment::STATUS_SELECT[$payment->payment_status] ?? $payment->payment_status;
                    if (preg_match('/^\s*[=+@-]/u', (string) $status)) $status = "'" . $status;
                    $row = [$payment->id, $payment->part_order_id, $supplier,
                        number_format((float) $payment->amount, 2, ',', ''), $payment->due_date?->format('Y-m-d'),
                        $status, $payment->payment_date?->format('Y-m-d'),
                        PartPayment::METHOD_SELECT[$payment->payment_method] ?? $payment->payment_method,
                        PartPayment::CONDITION_SELECT[$payment->payment_condition] ?? $payment->payment_condition,
                        $payment->reference, $payment->notes];
                    foreach ([6,7,8,9,10] as $index) {
                        $row[$index] = (string) $row[$index];
                        if (preg_match('/^\s*[=+@-]/u', $row[$index])) $row[$index] = "'" . $row[$index];
                    }
                    fputcsv($out, $row, ';', '"', '');
                }
                fclose($out);
            }, 'pagamentos-pecas-' . now('Europe/Lisbon')->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $partPayments = $query->paginate(50)->appends($request->query());
        $supliers = Suplier::orderBy('name')->pluck('name', 'id');

        return view('admin.partPayments.index', compact('partPayments', 'supliers'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('part_payment_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.partPayments.create', $this->formData(null, $request));
    }

    public function store(StorePartPaymentRequest $request)
    {
        $payment = PartPayment::create($request->validated());

        return redirect()->route('admin.part-payments.edit', $payment)->with('message', 'Pagamento criado com sucesso.');
    }

    public function edit(PartPayment $partPayment)
    {
        abort_if(Gate::denies('part_payment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.partPayments.edit', $this->formData($partPayment));
    }

    public function update(UpdatePartPaymentRequest $request, PartPayment $partPayment)
    {
        $partPayment->update($request->validated());

        return redirect()->route('admin.part-payments.edit', $partPayment)->with('message', 'Pagamento atualizado com sucesso.');
    }

    public function show(PartPayment $partPayment)
    {
        abort_if(Gate::denies('part_payment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partPayment->load(['part_order.vehicle.brand', 'suplier', 'paid_by']);

        return view('admin.partPayments.show', compact('partPayment'));
    }

    public function destroy(PartPayment $partPayment)
    {
        abort_if(Gate::denies('part_payment_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partPayment->delete();

        return redirect()->route('admin.part-payments.index');
    }

    private function formData(?PartPayment $partPayment = null, ?Request $request = null): array
    {
        return [
            'partPayment' => $partPayment,
            'partOrders' => PartOrder::with('vehicle.brand')->orderByDesc('id')->limit(500)->get()->mapWithKeys(function (PartOrder $order) {
                $vehicle = $order->vehicle;
                $label = '#' . $order->id . ' - ' . trim(implode(' ', array_filter([
                    $vehicle?->license ?: $vehicle?->foreign_license,
                    $vehicle?->brand?->name,
                    $vehicle?->model,
                ])));
                return [$order->id => $label];
            }),
            'supliers' => Suplier::orderBy('name')->pluck('name', 'id'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'selectedOrderId' => $request?->integer('part_order_id') ?: $partPayment?->part_order_id,
        ];
    }
}

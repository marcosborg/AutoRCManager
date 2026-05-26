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

        $query = PartPayment::with(['part_order.vehicle.brand', 'suplier', 'paid_by'])->orderByDesc('id');

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('suplier_id')) {
            $query->where('suplier_id', $request->suplier_id);
        }

        if ($request->boolean('overdue')) {
            $query->whereDate('due_date', '<', now()->toDateString())->whereNotIn('payment_status', ['paid', 'cancelled']);
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

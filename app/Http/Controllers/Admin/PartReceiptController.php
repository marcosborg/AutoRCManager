<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StorePartReceiptRequest;
use App\Http\Requests\UpdatePartReceiptRequest;
use App\Models\PartOrder;
use App\Models\PartReceipt;
use App\Models\User;
use App\Services\PartReceiptService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartReceiptController extends Controller
{
    use MediaUploadingTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('part_receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partReceipts = PartReceipt::with(['part_order.vehicle.brand', 'received_by'])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(50);

        return view('admin.partReceipts.index', compact('partReceipts'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('part_receipt_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.partReceipts.create', $this->formData(null, $request));
    }

    public function store(StorePartReceiptRequest $request)
    {
        $receipt = app(PartReceiptService::class)->save($request->safe()->except('attachments'));
        $this->storeAttachments($receipt, $request);

        return redirect()->route('admin.part-receipts.edit', $receipt)->with('message', 'Rececao registada com sucesso.');
    }

    public function edit(PartReceipt $partReceipt)
    {
        abort_if(Gate::denies('part_receipt_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partReceipt->load(['part_order.vehicle.brand', 'received_by', 'media']);

        return view('admin.partReceipts.edit', $this->formData($partReceipt));
    }

    public function update(UpdatePartReceiptRequest $request, PartReceipt $partReceipt)
    {
        $partReceipt = app(PartReceiptService::class)->save($request->safe()->except('attachments'), $partReceipt);
        $this->storeAttachments($partReceipt, $request);

        return redirect()->route('admin.part-receipts.edit', $partReceipt)->with('message', 'Rececao atualizada com sucesso.');
    }

    public function show(PartReceipt $partReceipt)
    {
        abort_if(Gate::denies('part_receipt_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $partReceipt->load(['part_order.vehicle.brand', 'received_by', 'media']);

        return view('admin.partReceipts.show', compact('partReceipt'));
    }

    public function destroy(PartReceipt $partReceipt)
    {
        abort_if(Gate::denies('part_receipt_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        app(PartReceiptService::class)->save(['part_order_id' => $partReceipt->part_order_id, 'receipt_revision' => app(PartReceiptService::class)->revision($partReceipt)], $partReceipt, true);

        return redirect()->route('admin.part-receipts.index');
    }

    private function formData(?PartReceipt $partReceipt = null, ?Request $request = null): array
    {
        $selectedOrderId = old('part_order_id', $request?->integer('part_order_id') ?: $partReceipt?->part_order_id);
        return [
            'receiptOrder' => PartOrder::with('items')->find($selectedOrderId),
            'partReceipt' => $partReceipt,
            'partOrders' => PartOrder::with('vehicle.brand')->orderByDesc('id')->limit(500)->get()->mapWithKeys(function (PartOrder $order) {
                $vehicle = $order->vehicle;
                $label = '#' . $order->id . ' - ' . trim(implode(' ', array_filter([
                    $vehicle?->license ?: $vehicle?->foreign_license,
                    $vehicle?->brand?->name,
                    $vehicle?->model,
                ])));
                return [$order->id => $label];
            }),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'selectedOrderId' => $selectedOrderId,
        ];
    }

    private function storeAttachments(PartReceipt $receipt, Request $request): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $receipt->addMedia($file)->toMediaCollection('attachments');
        }
    }

}

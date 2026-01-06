<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\ReceiveSupplierOrderItemRequest;
use App\Http\Requests\MassDestroySupplierOrderRequest;
use App\Http\Requests\StoreSupplierOrderItemRequest;
use App\Http\Requests\StoreSupplierOrderRequest;
use App\Http\Requests\UpdateSupplierOrderRequest;
use App\Http\Requests\UpdateSupplierOrderItemRequest;
use App\Models\AccountCategory;
use App\Models\AccountItem;
use App\Models\Repair;
use App\Models\Suplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierOrderController extends Controller
{
    use MediaUploadingTrait;
    public function index()
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $supplierOrders = SupplierOrder::with(['suplier', 'repair'])
            ->orderBy('order_date', 'desc')
            ->get();

        return view('admin.supplierOrders.index', compact('supplierOrders'));
    }

    public function summary()
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $orders = SupplierOrder::with(['suplier', 'items.account_category'])
            ->orderBy('order_date', 'desc')
            ->get();

        $suppliers = $orders->groupBy('suplier_id');

        return view('admin.supplierOrders.summary', compact('suppliers'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('repair_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $repairs = Repair::pluck('id', 'id')->prepend(trans('global.pleaseSelect'), '');
        $selectedRepairId = $request->query('repair_id');

        return view('admin.supplierOrders.create', compact('supliers', 'repairs', 'selectedRepairId'));
    }

    public function store(StoreSupplierOrderRequest $request)
    {
        $data = $request->except(['invoice_attachment']);
        $order = SupplierOrder::create($data);

        if ($request->hasFile('invoice_attachment')) {
            $order->addMediaFromRequest('invoice_attachment')->toMediaCollection('invoice_attachment');
        }

        return redirect()->route('admin.supplier-orders.edit', $order->id);
    }

    public function edit(SupplierOrder $supplierOrder)
    {
        abort_if(Gate::denies('repair_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $repairs = Repair::pluck('id', 'id')->prepend(trans('global.pleaseSelect'), '');
        $supplierOrder->load(['suplier', 'repair', 'items.account_category', 'media']);

        $account_categories = AccountCategory::where('account_department_id', 2)->get();

        return view('admin.supplierOrders.edit', compact('supplierOrder', 'supliers', 'repairs', 'account_categories'));
    }

    public function update(UpdateSupplierOrderRequest $request, SupplierOrder $supplierOrder)
    {
        $supplierOrder->update($request->except(['invoice_attachment', 'clear_invoice_attachment']));

        if ($request->boolean('clear_invoice_attachment')) {
            $supplierOrder->clearMediaCollection('invoice_attachment');
        }

        if ($request->hasFile('invoice_attachment')) {
            $supplierOrder->clearMediaCollection('invoice_attachment');
            $supplierOrder->addMediaFromRequest('invoice_attachment')->toMediaCollection('invoice_attachment');
        }

        return redirect()->route('admin.supplier-orders.edit', $supplierOrder->id);
    }

    public function show(SupplierOrder $supplierOrder)
    {
        abort_if(Gate::denies('repair_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $supplierOrder->load(['suplier', 'repair', 'items.account_category', 'media']);

        return view('admin.supplierOrders.show', compact('supplierOrder'));
    }

    public function destroy(SupplierOrder $supplierOrder)
    {
        abort_if(Gate::denies('repair_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $supplierOrder->delete();

        return back();
    }

    public function massDestroy(MassDestroySupplierOrderRequest $request)
    {
        $orders = SupplierOrder::find(request('ids'));

        foreach ($orders as $order) {
            $order->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeItem(StoreSupplierOrderItemRequest $request)
    {
        SupplierOrderItem::create([
            'supplier_order_id' => $request->input('supplier_order_id'),
            'account_category_id' => $request->input('account_category_id'),
            'item_name' => $request->input('item_name'),
            'qty_ordered' => $request->input('qty_ordered'),
            'unit_price' => $request->input('unit_price'),
        ]);

        return back();
    }

    public function updateItem(UpdateSupplierOrderItemRequest $request, SupplierOrderItem $item)
    {
        $item->update([
            'account_category_id' => $request->input('account_category_id'),
            'item_name' => $request->input('item_name'),
            'qty_ordered' => $request->input('qty_ordered'),
            'qty_received' => $request->input('qty_received', 0),
            'unit_price' => $request->input('unit_price'),
        ]);

        return back();
    }

    public function receiveItem(ReceiveSupplierOrderItemRequest $request, SupplierOrderItem $item)
    {
        $qtyReceived = (float) $request->input('qty_received');
        $item->qty_received = (float) $item->qty_received + $qtyReceived;
        $item->save();

        AccountItem::firstOrCreate([
            'name' => $item->item_name,
            'account_category_id' => $item->account_category_id,
        ], [
            'type' => 'outcome',
            'total' => 0,
        ]);

        return back();
    }
}

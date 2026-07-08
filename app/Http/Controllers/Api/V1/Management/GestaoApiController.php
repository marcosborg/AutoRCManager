<?php

namespace App\Http\Controllers\Api\V1\Management;

use App\Domain\Consignments\ConsignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\GeneralState;
use App\Models\LotPayment;
use App\Models\ManagementAlert;
use App\Models\Repair;
use App\Models\Vehicle;
use App\Models\VehicleConsignment;
use App\Models\VehicleGroup;
use App\Models\VehicleTradeIn;
use App\Services\VehicleLotService;
use App\Services\ManagementAlertService;
use App\Services\VehicleProfitabilityService;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class GestaoApiController extends Controller
{
    public function dashboard(ManagementAlertService $alerts)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $alerts->syncPendingApprovals();

        $states = GeneralState::query()
            ->withCount('vehicles')
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (GeneralState $state) => [
                'id' => $state->id,
                'name' => $state->name,
                'vehicles_count' => $state->vehicles_count,
            ]);

        return response()->json([
            'states' => $states,
            'totals' => [
                'vehicles' => Vehicle::count(),
                'consignments' => VehicleConsignment::where('status', ConsignmentStatus::ACTIVE)->count(),
                'approval_lots' => VehicleGroup::whereNull('approved_at')->count(),
                'approval_payments' => LotPayment::where('approval_status', LotPayment::STATUS_PENDING)->count(),
                'trade_ins' => VehicleTradeIn::where('status', VehicleTradeIn::STATUS_PENDING)->count(),
                'workshop' => Repair::count(),
                'unread_alerts' => ManagementAlert::whereNull('read_at')->count(),
            ],
        ]);
    }

    public function alerts(ManagementAlertService $alertService)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $alertService->syncPendingApprovals();

        $alerts = ManagementAlert::query()
            ->latest('event_at')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ManagementAlert $alert) => $this->alertPayload($alert));

        return response()->json([
            'data' => $alerts,
            'unread_count' => ManagementAlert::whereNull('read_at')->count(),
        ]);
    }

    public function readAlert(Request $request, ManagementAlert $alert)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $alert->update([
            'read_at' => $alert->read_at ?: now(),
            'read_by_id' => $alert->read_by_id ?: $request->user()?->id,
        ]);

        return response()->json(['data' => $this->alertPayload($alert->fresh())]);
    }

    public function readAllAlerts(Request $request)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        ManagementAlert::whereNull('read_at')->update([
            'read_at' => now(),
            'read_by_id' => $request->user()?->id,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Avisos marcados como lidos.',
            'unread_count' => 0,
        ]);
    }

    public function vehiclesByState()
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => GeneralState::query()
                ->withCount('vehicles')
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (GeneralState $state) => [
                    'id' => $state->id,
                    'name' => $state->name,
                    'vehicles_count' => $state->vehicles_count,
                ]),
        ]);
    }

    public function vehicles(Request $request)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::with(['general_state', 'brand', 'client', 'suplier', 'media'])
            ->when($request->filled('general_state_id'), fn ($query) => $query->where('general_state_id', $request->integer('general_state_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('license', 'like', "%{$search}%")
                        ->orWhere('foreign_license', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Vehicle $vehicle) => $this->vehicleListPayload($vehicle));

        return response()->json(['data' => $vehicles]);
    }

    public function vehicle(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->loadMissing(['general_state', 'brand', 'client', 'suplier', 'media', 'consignments.from_unit', 'consignments.to_unit', 'repairs.repair_state', 'source_trade_in']);

        return response()->json(['data' => $this->vehicleDetailPayload($vehicle)]);
    }

    public function centralRegister(Vehicle $vehicle, VehicleProfitabilityService $service)
    {
        abort_if(Gate::denies('vehicle_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => $service->build($vehicle),
        ]);
    }

    public function consignments()
    {
        abort_if(Gate::denies('vehicle_consignment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => VehicleConsignment::with(['vehicle.brand', 'from_unit', 'to_unit'])
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (VehicleConsignment $consignment) => [
                    'id' => $consignment->id,
                    'vehicle' => $this->vehicleLabel($consignment->vehicle),
                    'vehicle_id' => $consignment->vehicle_id,
                    'from_unit' => $consignment->from_unit->name ?? null,
                    'to_unit' => $consignment->to_destination_label ?: null,
                    'reference_value' => (float) $consignment->reference_value,
                    'starts_at' => optional($consignment->starts_at)->format('Y-m-d H:i'),
                    'ends_at' => optional($consignment->ends_at)->format('Y-m-d H:i'),
                    'status' => $consignment->status,
                ]),
        ]);
    }

    public function tradeIns(Request $request)
    {
        abort_if(Gate::denies('vehicle_trade_in_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => VehicleTradeIn::with(['sold_vehicle.brand', 'created_vehicle.brand', 'created_by', 'converted_by'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (VehicleTradeIn $tradeIn) => [
                    'id' => $tradeIn->id,
                    'license' => $tradeIn->license,
                    'amount' => (float) $tradeIn->amount,
                    'status' => $tradeIn->status,
                    'notes' => $tradeIn->notes,
                    'sold_vehicle' => $this->vehicleLabel($tradeIn->sold_vehicle),
                    'created_vehicle' => $this->vehicleLabel($tradeIn->created_vehicle),
                    'created_by' => $tradeIn->created_by->name ?? null,
                    'created_at' => optional($tradeIn->created_at)->format('Y-m-d H:i'),
                    'converted_at' => optional($tradeIn->converted_at)->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function workshop()
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'data' => Repair::with(['vehicle.brand', 'repair_state'])
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (Repair $repair) => [
                    'id' => $repair->id,
                    'vehicle_id' => $repair->vehicle_id,
                    'vehicle' => $this->vehicleLabel($repair->vehicle),
                    'state' => $repair->repair_state->name ?? null,
                    'timestamp' => $repair->timestamp,
                    'created_at' => optional($repair->created_at)->format('Y-m-d H:i'),
                ]),
        ]);
    }

    public function approvalsRafael()
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'payments' => LotPayment::with(['lot.customer', 'payment_method', 'creator'])
                ->where('approval_status', LotPayment::STATUS_PENDING)
                ->latest()
                ->get()
                ->map(fn (LotPayment $payment) => $this->approvalPaymentPayload($payment)),
            'lots' => VehicleGroup::with(['customer'])
                ->whereNull('approved_at')
                ->latest()
                ->get()
                ->map(fn (VehicleGroup $lot) => $this->approvalLotPayload($lot)),
        ]);
    }

    public function approveLot(Request $request, VehicleGroup $vehicleGroup)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleGroup->update([
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $this->approvalLotPayload($vehicleGroup->fresh(['customer']))]);
    }

    public function approvePayment(Request $request, LotPayment $lotPayment, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->approvePayment($lotPayment, (int) $request->user()->id);

        return response()->json(['data' => $this->approvalPaymentPayload($lotPayment->fresh(['lot.customer', 'payment_method', 'creator']))]);
    }

    public function rejectPayment(Request $request, LotPayment $lotPayment, VehicleLotService $service)
    {
        abort_if(Gate::denies('vehicle_lot_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->rejectPayment($lotPayment, (int) $request->user()->id, $data['rejection_reason']);

        return response()->json(['data' => $this->approvalPaymentPayload($lotPayment->fresh(['lot.customer', 'payment_method', 'creator']))]);
    }

    private function alertPayload(ManagementAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'type' => $alert->type,
            'title' => $alert->title,
            'message' => $alert->message,
            'subject_type' => $alert->subject_type,
            'subject_id' => $alert->subject_id,
            'event_at' => optional($alert->event_at)->format('Y-m-d H:i'),
            'read_at' => optional($alert->read_at)->format('Y-m-d H:i'),
        ];
    }

    private function vehicleListPayload(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'label' => $this->vehicleLabel($vehicle),
            'license' => $vehicle->license,
            'foreign_license' => $vehicle->foreign_license,
            'brand' => $vehicle->brand->name ?? null,
            'model' => $vehicle->model,
            'state' => $vehicle->general_state->name ?? null,
            'client' => $vehicle->client->name ?? null,
            'pvp' => (float) ($vehicle->pvp ?? 0),
            'sale_date' => $vehicle->sale_date,
            'cover_photo' => $this->vehicleCoverPhotoPayload($vehicle),
        ];
    }

    private function vehicleDetailPayload(Vehicle $vehicle): array
    {
        return $this->vehicleListPayload($vehicle) + [
            'version' => $vehicle->version,
            'year' => $vehicle->year,
            'month' => $vehicle->month,
            'fuel' => $vehicle->fuel,
            'kilometers' => $vehicle->kilometers,
            'supplier' => $vehicle->suplier->name ?? null,
            'purchase_price' => (float) ($vehicle->purchase_price ?? 0),
            'total_price' => (float) ($vehicle->total_price ?? 0),
            'minimum_price' => (float) ($vehicle->minimum_price ?? 0),
            'created_at' => optional($vehicle->created_at)->format('Y-m-d H:i'),
            'consignments_count' => $vehicle->consignments->count(),
            'repairs_count' => $vehicle->repairs->count(),
            'source_trade_in' => $vehicle->source_trade_in ? [
                'id' => $vehicle->source_trade_in->id,
                'license' => $vehicle->source_trade_in->license,
                'amount' => (float) $vehicle->source_trade_in->amount,
                'status' => $vehicle->source_trade_in->status,
            ] : null,
        ];
    }

    private function approvalPaymentPayload(LotPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'lot_id' => $payment->vehicle_group_id,
            'lot' => $payment->lot->name ?? null,
            'customer' => $payment->lot->customer->name ?? null,
            'payment_method' => $payment->payment_method->name ?? null,
            'amount' => (float) $payment->amount,
            'invoiced_amount' => (float) $payment->invoiced_amount,
            'bank_amount' => (float) $payment->bank_amount,
            'cash_amount' => (float) $payment->cash_amount,
            'cash_2_amount' => (float) $payment->cash_2_amount,
            'approval_status' => $payment->approval_status,
            'created_by' => $payment->creator->name ?? null,
            'created_at' => optional($payment->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function approvalLotPayload(VehicleGroup $lot): array
    {
        return [
            'id' => $lot->id,
            'name' => $lot->name,
            'customer' => $lot->customer->name ?? null,
            'effective_total' => (float) $lot->effective_total,
            'approved_at' => optional($lot->approved_at)->format('Y-m-d H:i'),
        ];
    }

    private function vehicleLabel(?Vehicle $vehicle): string
    {
        if (! $vehicle) {
            return '';
        }

        $parts = array_filter([
            $vehicle->license ?: $vehicle->foreign_license,
            $vehicle->brand->name ?? null,
            $vehicle->model,
        ]);

        return $parts ? implode(' - ', $parts) : ('Viatura #' . $vehicle->id);
    }

    private function vehicleCoverPhotoPayload(Vehicle $vehicle): ?array
    {
        $media = $vehicle->getFirstMedia('photos') ?: $vehicle->getFirstMedia('inicial');

        if (! $media) {
            return null;
        }

        return $this->mediaPayload($media);
    }

    private function mediaPayload(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumb' => $media->getUrl('thumb') ?: $media->getUrl(),
            'preview' => $media->getUrl('preview') ?: $media->getUrl(),
            'name' => $media->name,
        ];
    }
}

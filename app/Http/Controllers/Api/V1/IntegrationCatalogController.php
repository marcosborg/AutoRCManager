<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExternalService;
use App\Models\GeneralState;
use App\Models\PaintingJob;
use App\Models\PartOrder;
use App\Models\PartPayment;
use App\Models\PartReceipt;
use App\Models\VehicleConsignment;
use App\Models\VehicleGroup;
use App\Models\VehicleTradeIn;
use App\Models\WorkshopIntervention;
use App\Models\WorkshopState;
use App\Support\RolePreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IntegrationCatalogController extends Controller
{
    private const SHOW_PERMISSIONS = [
        'consignments' => ['vehicle_consignment_show'],
        'vehicle-groups' => ['vehicle_group_show', 'vehicle_lot_show'],
        'external-services' => ['external_service_access'],
        'painting-jobs' => ['painting_job_show'],
        'part-orders' => ['part_order_show'],
        'general-states' => ['general_state_show'],
        'part-payments' => ['part_payment_show'],
        'part-receipts' => ['part_receipt_show'],
    ];

    private const RESOURCES = [
        'consignments' => [VehicleConsignment::class, ['vehicle_consignment_access'], ['id', 'vehicle_id', 'from_unit_id', 'to_unit_id', 'to_unit_name', 'starts_at', 'ends_at', 'status', 'created_at', 'updated_at']],
        'trade-ins' => [VehicleTradeIn::class, ['vehicle_trade_in_access', 'vehicle_trade_in_convert'], ['id', 'sold_vehicle_id', 'created_vehicle_id', 'license', 'status', 'notes', 'rejection_reason', 'converted_at', 'rejected_at', 'created_at', 'updated_at']],
        'vehicle-groups' => [VehicleGroup::class, ['vehicle_group_access', 'vehicle_lot_access'], ['id', 'customer_id', 'name', 'type', 'distribution_mode', 'status', 'approved_by', 'approved_at', 'notes', 'created_at', 'updated_at']],
        'external-services' => [ExternalService::class, ['external_service_access'], ['id', 'vehicle_id', 'suplier_id', 'requested_by_id', 'description', 'priority', 'status', 'requested_delivery_days', 'expected_date', 'completed_date', 'notes', 'created_at', 'updated_at']],
        'painting-jobs' => [PaintingJob::class, ['painting_job_access'], ['id', 'vehicle_id', 'legacy_repair_id', 'painter_id', 'status', 'license', 'entry_date', 'exit_date', 'completed_at', 'created_at', 'updated_at']],
        'part-orders' => [PartOrder::class, ['part_order_access'], ['id', 'repair_id', 'vehicle_id', 'requested_by_id', 'technician_id', 'suplier_id', 'priority', 'status', 'expected_delivery_date', 'actual_delivery_date', 'notes', 'created_at', 'updated_at']],
        'workshop-interventions' => [WorkshopIntervention::class, ['workshop_planning_access'], ['id', 'repair_id', 'type_id', 'title', 'description', 'planned_start_date', 'planned_end_date', 'status', 'completed_at', 'created_at', 'updated_at']],
        'general-states' => [GeneralState::class, ['general_state_access'], ['id', 'name', 'position', 'created_at', 'updated_at']],
        'workshop-states' => [WorkshopState::class, ['workshop_state_access'], ['id', 'name', 'position', 'is_active', 'is_default', 'created_at', 'updated_at']],
        'part-payments' => [PartPayment::class, ['part_payment_access'], ['id', 'part_order_id', 'suplier_id', 'payment_method', 'payment_condition', 'amount', 'payment_date', 'due_date', 'reference', 'payment_status', 'created_at', 'updated_at']],
        'part-receipts' => [PartReceipt::class, ['part_receipt_access'], ['id', 'part_order_id', 'received_at', 'received_location', 'received_by_id', 'signature_name', 'observations', 'created_at', 'updated_at']],
    ];

    public function index(Request $request, string $resource)
    {
        [$model, , $fields] = $this->definition($resource);
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'updated_since' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'max:100'],
        ]);

        $query = $model::query()->select($fields);
        if (isset($data['updated_since'])) {
            $query->where('updated_at', '>=', $data['updated_since']);
        }
        if (isset($data['status']) && in_array('status', $fields, true)) {
            $query->where('status', $data['status']);
        }
        if ($resource === 'trade-ins' && ! $this->canManageTradeIns($request)) {
            abort_if(isset($data['status']) && $data['status'] !== VehicleTradeIn::STATUS_CONVERTED, 403);
            $query->where('status', VehicleTradeIn::STATUS_CONVERTED);
        }
        if ($resource === 'painting-jobs' && Gate::denies('painting_job_create')) {
            $query->where('painter_id', $request->user()->id);
        }

        return response()->json($query->orderBy('id')->paginate($data['per_page'] ?? 25));
    }

    public function show(Request $request, string $resource, int $id)
    {
        [$model, , $fields] = $this->definition($resource, true);
        $query = $model::query()->select($fields);
        if ($resource === 'trade-ins' && ! $this->canManageTradeIns($request)) {
            $query->where('status', VehicleTradeIn::STATUS_CONVERTED);
        }
        if ($resource === 'painting-jobs' && Gate::denies('painting_job_create')) {
            $query->where('painter_id', $request->user()->id);
        }

        return response()->json(['data' => $query->findOrFail($id)]);
    }

    private function definition(string $resource, bool $detail = false): array
    {
        abort_unless(array_key_exists($resource, self::RESOURCES), 404);
        $definition = self::RESOURCES[$resource];
        $abilities = $detail ? (self::SHOW_PERMISSIONS[$resource] ?? $definition[1]) : $definition[1];
        $allowed = collect($abilities)->contains(fn ($ability) => Gate::allows($ability));
        if ($resource === 'trade-ins') {
            $allowed = $allowed || $this->canManageTradeIns(request());
        }
        abort_unless($allowed, 403);

        return $definition;
    }

    private function canManageTradeIns(Request $request): bool
    {
        return Gate::allows('vehicle_trade_in_convert')
            || RolePreview::hasAnyEffectiveRole($request->user(), ['Admin', 'Gestão', 'Gestao', 'Stand Adm']);
    }
}

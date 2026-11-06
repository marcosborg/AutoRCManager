@if($tradeIn->sold_vehicle_id && $tradeIn->status !== \App\Models\VehicleTradeIn::STATUS_REJECTED && \App\Http\Controllers\Admin\TradeInDeliveryDeclarationController::allowed())
    <a class="btn btn-xs btn-default" style="margin-bottom: 6px;" href="{{ route('admin.vehicle-trade-ins.delivery.edit', $tradeIn) }}">Preparar declaração de entrega</a>
@endif

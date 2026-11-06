@if(\App\Http\Controllers\Admin\TradeInDeliveryDeclarationController::allowed())
    @foreach($vehicle->trade_ins as $deliveryTradeIn)
        @if($deliveryTradeIn->sold_vehicle_id && $deliveryTradeIn->status !== \App\Models\VehicleTradeIn::STATUS_REJECTED)
            <p><a class="btn btn-default" href="{{ route('admin.vehicle-trade-ins.delivery.edit', $deliveryTradeIn) }}">Preparar declaração de entrega da retoma — {{ $deliveryTradeIn->license }}</a></p>
        @endif
    @endforeach
@endif

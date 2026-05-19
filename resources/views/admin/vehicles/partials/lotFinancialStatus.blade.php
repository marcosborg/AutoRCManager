@if(isset($vehicleFinancialStatus))
    @if($vehicleFinancialStatus['lot'])
        <div class="alert alert-info">
            <strong>Viatura de lote:</strong>
            esta viatura pertence ao lote {{ $vehicleFinancialStatus['lot']->name }}.
            @if($vehicleFinancialStatus['lot']->type === 'unitario' && $vehicleFinancialStatus['itemPrice'] !== null)
                <span>Preco atribuido: &euro;{{ number_format($vehicleFinancialStatus['itemPrice'], 2, ',', '.') }}.</span>
            @endif
            @if(($vehicleFinancialStatus['itemRegistration'] ?? 0) > 0 || ($vehicleFinancialStatus['itemTow'] ?? 0) > 0)
                <span>
                    Registo: &euro;{{ number_format($vehicleFinancialStatus['itemRegistration'] ?? 0, 2, ',', '.') }};
                    Reboque: &euro;{{ number_format($vehicleFinancialStatus['itemTow'] ?? 0, 2, ',', '.') }}.
                </span>
            @endif
            <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicle-groups.show', $vehicleFinancialStatus['lot']->id) }}">
                Ver lote
            </a>
        </div>
    @endif
@endif

@if(isset($vehicleFinancialStatus))
    <div class="panel panel-default">
        <div class="panel-heading">
            Estado financeiro do lote
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-2"><strong>Estado:</strong> {{ $vehicleFinancialStatus['label'] }}</div>
                <div class="col-md-2"><strong>Venda:</strong> &euro;{{ number_format($vehicleFinancialStatus['target'], 2, ',', '.') }}</div>
                <div class="col-md-2"><strong>Recebido:</strong> &euro;{{ number_format($vehicleFinancialStatus['paid'], 2, ',', '.') }}</div>
                <div class="col-md-2"><strong>Faturado:</strong> &euro;{{ number_format($vehicleFinancialStatus['invoiced'], 2, ',', '.') }}</div>
                <div class="col-md-2"><strong>Caixa:</strong> &euro;{{ number_format($vehicleFinancialStatus['cash'], 2, ',', '.') }}</div>
                <div class="col-md-2"><strong>Saldo:</strong> &euro;{{ number_format($vehicleFinancialStatus['balance'], 2, ',', '.') }}</div>
            </div>
            @if($vehicleFinancialStatus['lot'])
                <hr>
                <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicle-groups.show', $vehicleFinancialStatus['lot']->id) }}">
                    Ver lote {{ $vehicleFinancialStatus['lot']->name }}
                </a>
            @endif
        </div>
    </div>
@endif

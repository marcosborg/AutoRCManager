@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Retomas pendentes</div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Venda</th>
                        <th>Retoma</th>
                        <th>Valor</th>
                        <th>Cliente</th>
                        <th>Criado por</th>
                        <th>Checklist</th>
                        <th>Documentos</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tradeIns as $tradeIn)
                        <tr>
                            <td>
                                <a href="{{ route('admin.vehicles.edit', $tradeIn->sold_vehicle_id) }}">
                                    {{ $tradeIn->sold_vehicle->license ?? $tradeIn->sold_vehicle->foreign_license ?? ('#' . $tradeIn->sold_vehicle_id) }}
                                </a>
                                <div class="text-muted small">{{ $tradeIn->sold_vehicle->brand->name ?? '' }} {{ $tradeIn->sold_vehicle->model ?? '' }}</div>
                            </td>
                            <td>{{ $tradeIn->license }}</td>
                            <td>{{ number_format((float) $tradeIn->amount, 2, ',', '.') }} EUR</td>
                            <td>{{ $tradeIn->sold_vehicle->client->name ?? '-' }}</td>
                            <td>{{ $tradeIn->created_by->name ?? '-' }}</td>
                            <td>
                                <div class="small">
                                    <div>{{ $tradeIn->has_purchase_sale_rgpd ? 'Sim' : 'Nao' }} - Compra/Venda + RGPD</div>
                                    <div>{{ $tradeIn->has_ipo ? 'Sim' : 'Nao' }} - IPO</div>
                                    <div>{{ $tradeIn->has_internal_invoice ? 'Sim' : 'Nao' }} - Fatura interna</div>
                                    <div>{{ $tradeIn->has_reservation_extinction_authorization ? 'Sim' : 'Nao' }} - Extincao reserva</div>
                                </div>
                            </td>
                            <td>
                                @foreach(\App\Models\VehicleTradeIn::DOCUMENT_COLLECTIONS as $collection => $label)
                                    @foreach($tradeIn->getMedia($collection) as $media)
                                        <a href="{{ $media->getUrl() }}" target="_blank" class="btn btn-xs btn-default" style="margin-bottom:2px;">{{ $label }}</a>
                                    @endforeach
                                @endforeach
                            </td>
                            <td style="min-width: 220px;">
                                <form method="POST" action="{{ route('admin.vehicle-trade-ins.convert', $tradeIn) }}" style="display:inline-block">
                                    @csrf
                                    <button class="btn btn-xs btn-success" type="submit">Converter em viatura</button>
                                </form>
                                <form method="POST" action="{{ route('admin.vehicle-trade-ins.reject', $tradeIn) }}" style="margin-top: 6px;">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <input class="form-control" name="rejection_reason" placeholder="Motivo rejeicao" required>
                                        <span class="input-group-btn">
                                            <button class="btn btn-danger" type="submit">Rejeitar</button>
                                        </span>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">Sem retomas pendentes.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $tradeIns->links() }}
        </div>
    </div>
</div>
@endsection

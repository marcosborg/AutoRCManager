@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Historico de retomas</div>
        <div class="panel-body">
            <form method="GET" class="row" style="margin-bottom: 12px;">
                <div class="col-md-3">
                    <label>Estado</label>
                    <select class="form-control" name="status">
                        <option value="">Todos</option>
                        @foreach(\App\Models\VehicleTradeIn::STATUS_SELECT as $status => $label)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Pesquisar matricula</label>
                    <input class="form-control" type="text" name="search" value="{{ request('search') }}" placeholder="Matricula vendida ou retomada">
                </div>
                <div class="col-md-2">
                    <label>Data inicio</label>
                    <input class="form-control" type="date" name="date_start" value="{{ request('date_start') }}">
                </div>
                <div class="col-md-2">
                    <label>Data fim</label>
                    <input class="form-control" type="date" name="date_end" value="{{ request('date_end') }}">
                </div>
                <div class="col-md-1" style="padding-top: 25px;">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                </div>
                <div class="col-md-1" style="padding-top: 25px;">
                    <a class="btn btn-default" href="{{ route('admin.vehicle-trade-ins.index') }}">Limpar</a>
                </div>
                <div class="col-md-12">
                    <div class="text-muted small" style="margin-top: 6px;">
                        O intervalo filtra por
                        @if(($dateField ?? 'created_at') === 'converted_at')
                            data de conversao.
                        @elseif(($dateField ?? 'created_at') === 'rejected_at')
                            data de rejeicao.
                        @else
                            data de criacao.
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Venda</th>
                        <th>Retoma</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Cliente</th>
                        <th>Criado por</th>
                        <th>Convertido/rejeitado</th>
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
                            <td>{{ \App\Models\VehicleTradeIn::STATUS_SELECT[$tradeIn->status] ?? $tradeIn->status }}</td>
                            <td>{{ $tradeIn->sold_vehicle->client->name ?? '-' }}</td>
                            <td>{{ $tradeIn->created_by->name ?? '-' }}</td>
                            <td>
                                @if($tradeIn->status === \App\Models\VehicleTradeIn::STATUS_CONVERTED)
                                    <div>{{ optional($tradeIn->converted_at)->format('Y-m-d H:i') }}</div>
                                    <div class="text-muted small">{{ $tradeIn->converted_by->name ?? '-' }}</div>
                                    @if($tradeIn->created_vehicle_id)
                                        <a href="{{ route('admin.vehicles.edit', $tradeIn->created_vehicle_id) }}">
                                            Viatura criada #{{ $tradeIn->created_vehicle_id }}
                                        </a>
                                        <div class="text-muted small">{{ $tradeIn->created_vehicle->license ?? '' }} {{ $tradeIn->created_vehicle->brand->name ?? '' }} {{ $tradeIn->created_vehicle->model ?? '' }}</div>
                                    @endif
                                @elseif($tradeIn->status === \App\Models\VehicleTradeIn::STATUS_REJECTED)
                                    <div>{{ optional($tradeIn->rejected_at)->format('Y-m-d H:i') }}</div>
                                    <div class="text-muted small">{{ $tradeIn->rejection_reason ?: '-' }}</div>
                                @else
                                    <span class="label label-warning">Pendente</span>
                                @endif
                            </td>
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
                                @if($tradeIn->status === \App\Models\VehicleTradeIn::STATUS_PENDING)
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
                                @else
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicles.edit', $tradeIn->sold_vehicle_id) }}">Abrir venda</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted">Sem retomas.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $tradeIns->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection

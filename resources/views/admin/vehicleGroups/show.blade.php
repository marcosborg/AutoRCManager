@extends('layouts.admin')

@section('content')
@php
    $registrationTotal = (float) $vehicleGroup->items->sum('registration_amount');
    $towTotal = (float) $vehicleGroup->items->sum('tow_amount');
@endphp
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            Lote: {{ $vehicleGroup->name }}
            @canany(['vehicle_group_edit', 'vehicle_lot_edit'])
                <a href="{{ route('admin.vehicle-groups.edit', $vehicleGroup->id) }}" class="btn btn-xs btn-info pull-right">Editar lote</a>
            @endcanany
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Cliente:</strong> {{ $vehicleGroup->customer->name ?? '-' }}</div>
                <div class="col-md-2"><strong>Tipo:</strong> {{ $vehicleGroup->type === 'unitario' ? 'Discriminado' : 'Global' }}</div>
                <div class="col-md-2"><strong>Estado:</strong> {{ $vehicleGroup->status }}</div>
                <div class="col-md-2"><strong>Viaturas:</strong> {{ $vehicleGroup->items->count() }}</div>
                <div class="col-md-3"><strong>Total lote:</strong> &euro;{{ number_format($financial['target'], 2, ',', '.') }}</div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-3"><strong>Subtotal venda:</strong> &euro;{{ number_format($vehicleGroup->total_amount ?? 0, 2, ',', '.') }}</div>
                <div class="col-md-3"><strong>Registo:</strong> &euro;{{ number_format($registrationTotal, 2, ',', '.') }}</div>
                <div class="col-md-3"><strong>Reboque:</strong> &euro;{{ number_format($towTotal, 2, ',', '.') }}</div>
            </div>
            @if($canApproveLots && !$vehicleGroup->approved_at)
                <hr>
                <form method="POST" action="{{ route('admin.vehicle-groups.approve', $vehicleGroup->id) }}">
                    @csrf
                    <button class="btn btn-success" type="submit">Aprovar lote</button>
                </form>
            @elseif($vehicleGroup->approved_at)
                <hr>
                <div><strong>Aprovado por:</strong> {{ $vehicleGroup->approver->name ?? '-' }} em {{ $vehicleGroup->approved_at }}</div>
            @endif
            @if($vehicleGroup->notes)
                <hr>
                <div><strong>Observacoes:</strong> {{ $vehicleGroup->notes }}</div>
            @endif
        </div>
    </div>

    @include('admin.vehicleGroups.partials.financial-summary')

    <div class="panel panel-default">
        <div class="panel-heading">Viaturas do lote</div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Viatura</th>
                        <th>Estado operacional</th>
                        @if($vehicleGroup->type === 'unitario')
                            <th>Preco atribuido</th>
                        @endif
                        <th>Registo</th>
                        <th>Reboque</th>
                        <th>Total viatura</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicleGroup->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('admin.vehicles.show', $item->vehicle_id) }}">
                                    {{ $item->vehicle->license ?? $item->vehicle->foreign_license ?? ('#' . $item->vehicle_id) }}
                                </a>
                                <div class="text-muted small">{{ $item->vehicle->brand->name ?? '' }} {{ $item->vehicle->model ?? '' }}</div>
                            </td>
                            <td>{{ $item->vehicle->general_state->name ?? '-' }}</td>
                            @if($vehicleGroup->type === 'unitario')
                                <td>&euro;{{ number_format($item->adjusted_price ?? 0, 2, ',', '.') }}</td>
                            @endif
                            <td>&euro;{{ number_format($item->registration_amount ?? 0, 2, ',', '.') }}</td>
                            <td>&euro;{{ number_format($item->tow_amount ?? 0, 2, ',', '.') }}</td>
                            <td>
                                @if($vehicleGroup->type === 'unitario')
                                    &euro;{{ number_format($item->sale_target, 2, ',', '.') }}
                                @else
                                    &euro;{{ number_format((float) ($item->registration_amount ?? 0) + (float) ($item->tow_amount ?? 0), 2, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $vehicleGroup->type === 'unitario' ? 6 : 5 }}" class="text-muted">Sem viaturas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.vehicleGroups.partials.payment-management', ['paymentReturnTo' => 'show'])
</div>
@endsection

@section('scripts')
@parent
@include('admin.vehicleGroups.partials.payment-scripts')
@endsection

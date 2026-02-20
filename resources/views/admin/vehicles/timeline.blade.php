@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Timeline - {{ $vehicle->license ?? $vehicle->foreign_license ?? $vehicle->id }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <a class="btn btn-default" href="{{ route('admin.vehicles.show', $vehicle->id) }}">
                            {{ trans('global.back_to_list') }}
                        </a>
                        @can('vehicle_access')
                            <a class="btn btn-success" href="{{ route('admin.vehicles.timeline.export.pdf', $vehicle->id) }}">
                                Exportar PDF
                            </a>
                        @endcan
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="well well-sm text-center">
                                <div><strong>Total custos</strong></div>
                                <div class="lead">â‚¬{{ number_format($totalCost, 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="well well-sm text-center">
                                <div><strong>Total receitas</strong></div>
                                <div class="lead">â‚¬{{ number_format($totalRevenue, 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="well well-sm text-center">
                                <div><strong>Resultado</strong></div>
                                <div class="lead">â‚¬{{ number_format($result, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted">
                        Valores informativos, nao contabilisticos. Sem impostos ou amortizacoes.
                    </p>

                    <ul class="timeline" style="list-style: none; padding-left: 0;">
                        @forelse($events as $event)
                            @php
                                $dateStart = $event['date_start']
                                    ? $event['date_start']->format(config('panel.date_format') . ' ' . config('panel.time_format'))
                                    : '';
                                $dateEnd = $event['date_end']
                                    ? $event['date_end']->format(config('panel.date_format') . ' ' . config('panel.time_format'))
                                    : null;
                                $amount = $event['amount'];
                                $amountLabel = null;
                                if (! is_null($amount)) {
                                    $amountLabel = number_format((float) $amount, 2, ',', '.');
                                }
                            @endphp
                            <li style="margin-bottom: 15px;">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <strong>{{ $event['title'] }}</strong>
                                        <span class="text-muted" style="margin-left: 10px;">
                                            {{ $dateStart }}@if($dateEnd) - {{ $dateEnd }}@endif
                                        </span>
                                    </div>
                                    <div class="panel-body">
                                        <div><strong>Tipo:</strong> {{ $event['type'] }}</div>
                                        <div><strong>Descricao:</strong> {{ $event['description'] }}</div>
                                        @if($event['unit'])
                                            <div><strong>Unidade:</strong> {{ $event['unit'] }}</div>
                                        @endif
                                        @if(! is_null($amountLabel))
                                            <div><strong>Valor:</strong> â‚¬{{ $amountLabel }}</div>
                                        @endif
                                        @php
                                            $relatedModel = $event['related_model'];
                                            $relatedId = $event['related_id'];
                                            $link = null;

                                            if ($relatedModel === 'VehicleConsignment') {
                                                $link = route('admin.vehicle-consignments.show', $relatedId);
                                            } elseif ($relatedModel === 'Repair') {
                                                $link = route('admin.repairs.show', $relatedId);
                                            }
                                        @endphp
                                        @if($link)
                                            <a class="btn btn-xs btn-primary" href="{{ $link }}">
                                                {{ trans('global.view') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li>
                                <div class="alert alert-info">
                                    Sem eventos para esta viatura.
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection



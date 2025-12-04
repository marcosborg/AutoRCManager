@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Dashboard
                </div>

                <div class="panel-body">
                    @if(session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="d-flex justify-content-between" style="margin-bottom: 10px;">
                        <h4 style="margin: 0;">Posicoes GPS mais recentes por tracker</h4>
                        <a class="btn btn-xs btn-primary" href="{{ route('admin.gps.positions') }}" target="_blank">Ver JSON</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tracker</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>Velocidade (km/h)</th>
                                    <th>Fix</th>
                                    <th>Tensao (V)</th>
                                    <th>Reportado em</th>
                                    <th>Recebido em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($positions as $position)
                                    <tr>
                                        <td>{{ $position->tracker_id }}</td>
                                        <td>{{ number_format($position->latitude, 6) }}</td>
                                        <td>{{ number_format($position->longitude, 6) }}</td>
                                        <td>{{ $position->speed_kph }}</td>
                                        <td>
                                            @if($position->fix_valid)
                                                <span class="label label-success">A</span>
                                            @else
                                                <span class="label label-default">V</span>
                                            @endif
                                        </td>
                                        <td>{{ $position->voltage !== null ? number_format($position->voltage, 2) : '—' }}</td>
                                        <td>{{ optional($position->reported_at)->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ optional($position->created_at)->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Ainda sem posicoes guardadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent

@endsection

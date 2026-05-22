@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleStateTransfer.title') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.id') }}</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.vehicle') }}</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.from_state') }}</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.to_state') }}</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.fuel_level') }}</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.user') }}</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.created_at') }}</th>
                                    <th>Verificado</th>
                                    <th>{{ trans('cruds.vehicleStateTransfer.fields.snapshot') }}</th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                    <tr id="transfer-{{ $transfer->id }}">
                                        <td>{{ $transfer->id }}</td>
                                        <td>{{ $transfer->vehicle->license ?? '-' }}</td>
                                        <td>{{ $transfer->from_general_state->name ?? '-' }}</td>
                                        <td>{{ $transfer->to_general_state->name ?? '-' }}</td>
                                        <td>{{ $transfer->fuel_level ?? '-' }}</td>
                                        <td>{{ $transfer->user->name ?? '-' }}</td>
                                        <td>{{ $transfer->created_at }}</td>
                                        <td>
                                            @if($transfer->checked_at)
                                                <span class="label label-success">Sim</span>
                                                <div class="small text-muted">{{ $transfer->checked_by->name ?? '-' }}</div>
                                            @else
                                                <span class="label label-danger">Nao</span>
                                            @endif
                                        </td>
                                        <td style="width: 120px;">
                                            <a class="btn btn-xs btn-default" data-toggle="collapse" href="#snapshot-{{ $transfer->id }}" aria-expanded="false">
                                                Ver JSON
                                            </a>
                                        </td>
                                        <td>
                                            @if(!$transfer->checked_at)
                                                <form method="POST" action="{{ route('admin.vehicle-state-transfers.check', $transfer) }}">
                                                    @csrf
                                                    <button class="btn btn-xs btn-success" type="submit">Verificado</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="snapshot-{{ $transfer->id }}">
                                        <td colspan="10">
                                            <pre style="white-space: pre-wrap;">{{ json_encode($transfer->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">Sem historico.</td>
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

@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Viaturas eliminadas
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped table-hover datatable datatable-DeletedVehicles">
                        <thead>
                            <tr>
                                <th>Eliminado em</th>
                                <th>{{ trans('cruds.vehicle.fields.general_state') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.license') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.foreign_license') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.our_registration') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.brand') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.model') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.suplier') }}</th>
                                <th>{{ trans('cruds.vehicle.fields.client') }}</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicles as $vehicle)
                                <tr>
                                    <td>{{ optional($vehicle->deleted_at)->format(config('panel.date_format') . ' H:i') }}</td>
                                    <td>{{ $vehicle->general_state->name ?? '' }}</td>
                                    <td>{{ $vehicle->license ?? '' }}</td>
                                    <td>{{ $vehicle->foreign_license ?? '' }}</td>
                                    <td>{{ $vehicle->our_registration ?? '' }}</td>
                                    <td>{{ $vehicle->brand->name ?? '' }}</td>
                                    <td>{{ $vehicle->model ?? '' }}</td>
                                    <td>{{ $vehicle->suplier->name ?? '' }}</td>
                                    <td>{{ $vehicle->client->name ?? '' }}</td>
                                    <td>
                                        @can('vehicle_show')
                                            <a class="btn btn-xs btn-primary" href="{{ route('admin.vehicles.deleted.show', $vehicle->id) }}">
                                                {{ trans('global.view') }}
                                            </a>
                                        @endcan
                                        @can('vehicle_edit')
                                            <a class="btn btn-xs btn-info" href="{{ route('admin.vehicles.deleted.edit', $vehicle->id) }}">
                                                {{ trans('global.edit') }}
                                            </a>
                                            <form action="{{ route('admin.vehicles.restore', $vehicle->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-success">
                                                    <i class="fas fa-recycle"></i>
                                                    Recuperar
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
$(function () {
  $('.datatable-DeletedVehicles').DataTable({
    buttons: [],
    columnDefs: [
      {
        targets: -1,
        orderable: false,
        searchable: false
      }
    ],
    order: [[0, 'desc']],
    pageLength: 100,
    select: false
  });
});
</script>
@endsection

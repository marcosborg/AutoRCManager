@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.vehicleConsignment.title_singular') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <a class="btn btn-default" href="{{ route('admin.vehicle-consignments.index') }}">
                            {{ trans('global.back_to_list') }}
                        </a>
                    </div>
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.id') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->id }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.vehicle') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->vehicle->license ?? $vehicleConsignment->vehicle->foreign_license ?? $vehicleConsignment->vehicle->id ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.from_unit') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->from_unit->name ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.to_unit') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->to_unit->name ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.reference_value') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->reference_value }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.starts_at') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->starts_at }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.ends_at') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->ends_at }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    {{ trans('cruds.vehicleConsignment.fields.status') }}
                                </th>
                                <td>
                                    {{ $vehicleConsignment->status }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="form-group">
                        <a class="btn btn-default" href="{{ route('admin.vehicle-consignments.index') }}">
                            {{ trans('global.back_to_list') }}
                        </a>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

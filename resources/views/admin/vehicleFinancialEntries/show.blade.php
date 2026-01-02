@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.vehicleFinancialEntry.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-financial-entries.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $vehicleFinancialEntry->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.vehicle') }}
                                    </th>
                                    <td>
                                        {{ $vehicleFinancialEntry->vehicle->license ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.entry_type') }}
                                    </th>
                                    <td>
                                        {{ $vehicleFinancialEntry->entry_type === 'cost' ? 'Custo' : 'Receita' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.category') }}
                                    </th>
                                    <td>
                                        {{ $vehicleFinancialEntry->category }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.amount') }}
                                    </th>
                                    <td>
                                        {{ number_format((float) $vehicleFinancialEntry->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.entry_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicleFinancialEntry->entry_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleFinancialEntry.fields.notes') }}
                                    </th>
                                    <td>
                                        {{ $vehicleFinancialEntry->notes }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-financial-entries.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

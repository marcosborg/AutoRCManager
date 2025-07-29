@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.accountOperation.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.account-operations.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.vehicle') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->vehicle->license ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.account_item') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->account_item->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.qty') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->qty }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.total') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->total }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.payment_method') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->payment_method->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.accountOperation.fields.date') }}
                                    </th>
                                    <td>
                                        {{ $accountOperation->date }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.account-operations.index') }}">
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
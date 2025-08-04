@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.generalState.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.general-states.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.generalState.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $generalState->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.generalState.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $generalState->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.generalState.fields.notification') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $generalState->notification ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.generalState.fields.emails') }}
                                    </th>
                                    <td>
                                        {{ $generalState->emails }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.generalState.fields.message') }}
                                    </th>
                                    <td>
                                        {!! $generalState->message !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.generalState.fields.position') }}
                                    </th>
                                    <td>
                                        {{ $generalState->position }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.general-states.index') }}">
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
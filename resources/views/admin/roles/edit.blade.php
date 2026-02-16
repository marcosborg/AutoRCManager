@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.role.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.roles.update", [$role->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                            <label class="required" for="title">{{ trans('cruds.role.fields.title') }}</label>
                            <input class="form-control" type="text" name="title" id="title" value="{{ old('title', $role->title) }}" required>
                            @if($errors->has('title'))
                                <span class="help-block" role="alert">{{ $errors->first('title') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span>
                        </div>
                        @php
                            $selectedPermissions = old('permissions', $role->permissions->pluck('id')->toArray());
                        @endphp
                        @include('admin.roles.partials.permissions-matrix', [
                            'permissions' => $permissions,
                            'selectedPermissions' => $selectedPermissions,
                        ])
                        @php
                            $selectedUsers = old('users', $role->users->pluck('id')->toArray());
                        @endphp
                        @include('admin.roles.partials.users-checklist', [
                            'users' => $users,
                            'selectedUsers' => $selectedUsers,
                        ])
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection

@include('admin.roles.partials.form-scripts')

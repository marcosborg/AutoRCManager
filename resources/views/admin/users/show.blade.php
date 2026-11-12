@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.user.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.users.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.user.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $user->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.user.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $user->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.user.fields.email') }}
                                    </th>
                                    <td>
                                        {{ $user->email }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Telemóvel
                                    </th>
                                    <td>
                                        {{ $user->mobile_phone ?: '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.user.fields.email_verified_at') }}
                                    </th>
                                    <td>
                                        {{ $user->email_verified_at }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.user.fields.roles') }}
                                    </th>
                                    <td>
                                        @foreach($user->roles as $key => $roles)
                                            <span class="label label-info">{{ $roles->title }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <h3>Resumo dos principais acessos</h3>
                        <p>Consulta das autorizações atuais, sem alterar os acessos. Algumas operações dependem também do estado da viatura, da atribuição da lead e das regras de cada área.</p>
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>Operação</th><th>Autorização</th><th>Grupos com esta permissão</th></tr></thead>
                            <tbody>
                                @foreach($accessReview as $access)
                                    <tr>
                                        <td>{{ $access['title'] }}</td>
                                        <td>{{ $access['allowed'] ? 'Permitido' : 'Não permitido' }}</td>
                                        <td>{{ implode(', ', $access['roles']) ?: 'Sem permissão de grupo' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="text-muted">As restrições adicionais prevalecem sobre as permissões dos grupos. Podem existir autorizações específicas fora dos grupos.</p>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.users.index') }}">
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

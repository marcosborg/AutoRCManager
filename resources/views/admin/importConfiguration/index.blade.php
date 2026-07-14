@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">Alertas de Importação / Adjudicação</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.import-configuration.tolls-recipient.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group {{ $errors->has('user_ids') || $errors->has('user_ids.*') ? 'has-error' : '' }}">
                            <label class="required" for="tolls-recipient-users">Responsáveis pelos alertas</label>
                            <select class="form-control select2" name="user_ids[]" id="tolls-recipient-users" multiple required>
                                @foreach($users as $id => $name)
                                    <option value="{{ $id }}" {{ collect(old('user_ids', $selectedTollsRecipientIds))->contains(fn ($selectedId) => (string) $selectedId === (string) $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('user_ids'))
                                <span class="help-block">{{ $errors->first('user_ids') }}</span>
                            @endif
                            <p class="help-block">Todos os utilizadores selecionados recebem individualmente cada tarefa.</p>
                        </div>
                        <button class="btn btn-danger" type="submit">Gravar responsáveis</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">Empresas compradoras</div>
                <div class="panel-body">
                    @can('purchasing_company_manage')
                        <form id="purchasing-company-config-create" class="form-inline" style="margin-bottom: 20px;">
                            <div class="form-group">
                                <label class="sr-only" for="configuration-company-name">Nome</label>
                                <input class="form-control" id="configuration-company-name" type="text" placeholder="Nova empresa" required>
                            </div>
                            <button class="btn btn-success" type="submit">Criar empresa</button>
                            <span class="help-block purchasing-company-feedback" style="display:inline-block; margin-left:10px;"></span>
                        </form>
                    @endcan

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr><th>Nome</th><th>Ativa</th><th>&nbsp;</th></tr>
                            </thead>
                            <tbody>
                                @foreach($companies as $company)
                                    <tr>
                                        <td>
                                            <form id="purchasing-company-{{ $company->id }}" method="POST" action="{{ route('admin.purchasing-companies.update', $company) }}">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                            <input class="form-control" form="purchasing-company-{{ $company->id }}" name="name" value="{{ $company->name }}" required>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" form="purchasing-company-{{ $company->id }}" name="active" value="0">
                                            <input type="checkbox" form="purchasing-company-{{ $company->id }}" name="active" value="1" {{ $company->active ? 'checked' : '' }}>
                                        </td>
                                        <td><button class="btn btn-xs btn-info" form="purchasing-company-{{ $company->id }}" type="submit">Gravar</button></td>
                                    </tr>
                                @endforeach
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
<script>
    $(function () {
        $('#purchasing-company-config-create').on('submit', function (event) {
            event.preventDefault();
            const $form = $(this);
            const $feedback = $form.find('.purchasing-company-feedback');

            $.ajax({
                method: 'POST',
                url: @json(route('admin.purchasing-companies.store')),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { name: $('#configuration-company-name').val() }
            }).done(function () {
                window.location.reload();
            }).fail(function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                $feedback.text(errors && errors.name ? errors.name[0] : 'Não foi possível criar a empresa.');
            });
        });
    });
</script>
@endsection

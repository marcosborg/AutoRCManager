@extends('layouts.admin')
@section('content')
<div class="content">
    <p><a href="{{ route('admin.vehicles.index') }}">← Voltar aos veículos</a></p>
    <div class="panel panel-default">
        <div class="panel-heading"><h1 class="panel-title">Conferir documentação</h1></div>
        <div class="panel-body">
            <p>Encontre confirmações por preencher na ficha e anexos que ainda precisam de identificação.</p>
            <p class="help-block">“Por confirmar” não significa que o documento seja obrigatório ou esteja em falta. Pode existir em papel. Identificar um ficheiro não confirma a sua receção.</p>
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="GET" action="{{ route('admin.vehicles.documents.review') }}">
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="review-scope">Viaturas</label>
                        <select id="review-scope" name="scope" class="form-control" required>
                            <option value="stock" {{ $scope === 'stock' ? 'selected' : '' }}>Em stock</option>
                            <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>Todas, incluindo vendidas</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="review-license">Matrícula nacional ou estrangeira</label>
                        <input id="review-license" class="form-control" name="license" value="{{ $filters['license'] ?? '' }}" maxlength="100" placeholder="Pesquisar matrícula">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="review-company">Empresa de destino</label>
                        <select id="review-company" class="form-control" name="destination_company">
                            <option value="">Todas as empresas</option>
                            @foreach(\App\Models\Vehicle::DESTINATION_COMPANIES + ['unassigned' => 'Por confirmar'] as $code => $label)
                                <option value="{{ $code }}" {{ ($filters['destination_company'] ?? '') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="review-reason">O que conferir</label>
                        <select id="review-reason" class="form-control" name="reason">
                            <option value="any" {{ $reason === 'any' ? 'selected' : '' }}>Qualquer pendência</option>
                            <option value="checks" {{ $reason === 'checks' ? 'selected' : '' }}>Confirmações por preencher</option>
                            <option value="unidentified" {{ $reason === 'unidentified' ? 'selected' : '' }}>Anexos por identificar</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Consultar</button>
                <a class="btn btn-default" href="{{ route('admin.vehicles.documents.review') }}">Limpar filtros</a>
            </form>
            @if($scope === 'stock')
                <p class="help-block">Stock segue o critério atual do sistema: viaturas sem data de venda nem estado de vendida. Inclui oficina, aluguer e adjudicação.</p>
            @endif
            @if($vehicles !== null)
                <hr>
                <p role="status"><strong>{{ $vehicles->total() }}</strong> viatura(s) com pendências nesta consulta. As viaturas eliminadas não são incluídas.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Viatura</th><th>Empresa de destino</th><th>Estado</th><th>Confirmações por preencher</th><th>Anexos por identificar</th><th>Ação</th></tr></thead>
                        <tbody>
                        @forelse($vehicles as $vehicle)
                            <tr data-vehicle-id="{{ $vehicle->id }}">
                                <td><strong>{{ $vehicle->license ?: ($vehicle->foreign_license ?: 'Sem matrícula — #'.$vehicle->id) }}</strong>
                                    @if($vehicle->license && $vehicle->foreign_license)<br><small>{{ $vehicle->foreign_license }}</small>@endif
                                    <br>{{ $vehicle->brand->name ?? '' }} {{ $vehicle->model }}
                                </td>
                                <td>{{ \App\Models\Vehicle::DESTINATION_COMPANIES[$vehicle->destination_company] ?? 'Por confirmar' }}</td>
                                <td>{{ $vehicle->general_state->name ?? 'Por confirmar' }}</td>
                                <td>
                                    @php($pendingChecks = \App\Support\VehicleDocuments::pendingChecks($vehicle))
                                    @if($pendingChecks)
                                        <ul style="padding-left:18px; margin-bottom:0;">@foreach($pendingChecks as $label)<li>{{ $label }}</li>@endforeach</ul>
                                    @else
                                        @if(collect(array_keys(\App\Support\VehicleDocuments::checks()))->contains(fn ($field) => \App\Support\VehicleDocuments::notApplicable($vehicle, $field)))
                                            Nenhuma — confirmações recebidas ou dispensadas com motivo
                                        @else
                                            Nenhuma — confirmações preenchidas na ficha
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $vehicle->unidentified_count }} {{ $vehicle->unidentified_count == 1 ? 'anexo' : 'anexos' }}</td>
                                <td>@can('vehicle_edit')
                                    <a class="btn btn-default btn-sm" href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-documents">Abrir documentos</a>
                                @else
                                    <span class="text-muted">Sem permissão para editar a ficha</span>
                                @endcan</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Nenhuma viatura corresponde aos filtros escolhidos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $vehicles->links('pagination::bootstrap-4') }}
            @else
                <p class="help-block">Escolha as viaturas e carregue em Consultar.</p>
            @endif
        </div>
    </div>
</div>
@endsection

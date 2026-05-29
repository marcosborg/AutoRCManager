@extends('layouts.admin')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
    $movementTypeLabels = [
        'income' => 'Entrada',
        'outcome' => 'Saída',
    ];
@endphp

<div class="content">
    @if(session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-bottom: 0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        @foreach($summary['boxes'] as $box)
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ $box['name'] }}</div>
                    <div class="panel-body">
                        <div><strong>Entradas filtradas:</strong> {{ $money($box['income']) }}</div>
                        <div><strong>Saídas filtradas:</strong> {{ $money($box['outcome']) }}</div>
                        <h4 style="margin-bottom: 0;">{{ $money($box['balance']) }}</h4>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-warning">
                <div class="panel-heading">Por contabilizar</div>
                <div class="panel-body">
                    <div><strong>Movimentos:</strong> {{ $pendingAccounting['count'] }}</div>
                    <h4 style="margin-bottom: 0;">{{ $money($pendingAccounting['total']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">Novo movimento</div>
        <div class="panel-body">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#cash-movement" aria-controls="cash-movement" role="tab" data-toggle="tab">Entrada / Saída</a>
                </li>
                <li role="presentation">
                    <a href="#cash-transfer" aria-controls="cash-transfer" role="tab" data-toggle="tab">Transferência entre caixas</a>
                </li>
            </ul>

            <div class="tab-content" style="padding-top: 15px;">
                <div role="tabpanel" class="tab-pane active" id="cash-movement">
                    <form method="POST" action="{{ route('admin.cash.movements.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required" for="movement_type">Tipo</label>
                                    <select class="form-control" name="movement_type" id="movement_type" required>
                                        <option value="income" {{ old('movement_type') === 'income' ? 'selected' : '' }}>Entrada</option>
                                        <option value="outcome" {{ old('movement_type', 'outcome') === 'outcome' ? 'selected' : '' }}>Saída</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required" for="date">Data</label>
                                    <input class="form-control" type="date" name="date" id="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required" for="description">Descrição</label>
                                    <input class="form-control" type="text" name="description" id="description" value="{{ old('description') }}" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required" for="total">Valor</label>
                                    <input class="form-control" type="number" name="total" id="total" value="{{ old('total') }}" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="checkbox" style="margin-top: 28px;">
                                    <label>
                                        <input type="checkbox" name="is_accounted" value="1" {{ old('is_accounted') ? 'checked' : '' }}>
                                        Contabilizado
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                @include('admin.cash.partials.select-with-modal', [
                                    'label' => 'Departamento',
                                    'name' => 'department_id',
                                    'id' => 'department_id',
                                    'items' => $departments,
                                    'oldValue' => old('department_id'),
                                    'modalTarget' => '#department-modal',
                                ])
                            </div>
                            <div class="col-md-3">
                                @include('admin.cash.partials.select-with-modal', [
                                    'label' => 'Categoria',
                                    'name' => 'cash_category_id',
                                    'id' => 'cash_category_id',
                                    'items' => $cashCategories,
                                    'oldValue' => old('cash_category_id'),
                                    'modalTarget' => '#category-modal',
                                ])
                            </div>
                            <div class="col-md-3">
                                @include('admin.cash.partials.select-with-modal', [
                                    'label' => 'Caixa',
                                    'name' => 'cash_box_id',
                                    'id' => 'cash_box_id',
                                    'items' => $cashBoxes,
                                    'oldValue' => old('cash_box_id'),
                                    'modalTarget' => '#cash-box-modal',
                                ])
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="payment_method_id">Método</label>
                                    <select class="form-control select2" name="payment_method_id" id="payment_method_id">
                                        <option value="">Selecione</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method->id }}" {{ (string) old('payment_method_id') === (string) $method->id ? 'selected' : '' }}>
                                                {{ $method->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vehicle_id">Viatura</label>
                                    <select class="form-control select2" name="vehicle_id" id="vehicle_id">
                                        <option value="">Sem viatura</option>
                                        @foreach($vehicles as $vehicle)
                                            @php($vehicleLabel = trim(($vehicle->license ?: $vehicle->foreign_license ?: 'Viatura #' . $vehicle->id) . ' ' . ($vehicle->brand->name ?? '') . ' ' . ($vehicle->model ?? '')))
                                            <option value="{{ $vehicle->id }}" {{ (string) old('vehicle_id') === (string) $vehicle->id ? 'selected' : '' }}>
                                                {{ $vehicleLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="notes">Observações</label>
                                    <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Registar movimento</button>
                    </form>
                </div>

                <div role="tabpanel" class="tab-pane" id="cash-transfer">
                    <form method="POST" action="{{ route('admin.cash.transfers.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required" for="transfer_date">Data</label>
                                    <input class="form-control" type="date" name="date" id="transfer_date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="required" for="from_cash_box_id">De</label>
                                    <select class="form-control" name="from_cash_box_id" id="from_cash_box_id" required>
                                        @foreach($cashBoxes as $box)
                                            <option value="{{ $box->id }}">{{ $box->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="required" for="to_cash_box_id">Para</label>
                                    <select class="form-control" name="to_cash_box_id" id="to_cash_box_id" required>
                                        @foreach($cashBoxes as $box)
                                            <option value="{{ $box->id }}">{{ $box->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required" for="transfer_total">Valor</label>
                                    <input class="form-control" type="number" name="total" id="transfer_total" step="0.01" min="0.01" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                @include('admin.cash.partials.select-with-modal', [
                                    'label' => 'Departamento',
                                    'name' => 'department_id',
                                    'id' => 'transfer_department_id',
                                    'items' => $departments,
                                    'oldValue' => old('department_id'),
                                    'modalTarget' => '#department-modal',
                                ])
                            </div>
                            <div class="col-md-3">
                                @include('admin.cash.partials.select-with-modal', [
                                    'label' => 'Categoria',
                                    'name' => 'cash_category_id',
                                    'id' => 'transfer_cash_category_id',
                                    'items' => $cashCategories,
                                    'oldValue' => old('cash_category_id'),
                                    'modalTarget' => '#category-modal',
                                ])
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transfer_notes">Observações</label>
                                    <textarea class="form-control" name="notes" id="transfer_notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Registar transferência</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">Pesquisa avançada</div>
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.cash.index') }}">
                <div class="row">
                    <div class="col-md-2">
                        <label>Departamento</label>
                        <select class="form-control" name="department_id">
                            <option value="">Todos</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Categoria</label>
                        <select class="form-control" name="cash_category_id">
                            <option value="">Todas</option>
                            @foreach($cashCategories as $category)
                                <option value="{{ $category->id }}" {{ (string) request('cash_category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Caixa</label>
                        <select class="form-control" name="cash_box_id">
                            <option value="">Todas</option>
                            @foreach($cashBoxes as $box)
                                <option value="{{ $box->id }}" {{ (string) request('cash_box_id') === (string) $box->id ? 'selected' : '' }}>{{ $box->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Tipo</label>
                        <select class="form-control" name="movement_type">
                            <option value="">Todos</option>
                            @foreach($movementTypeLabels as $type => $label)
                                <option value="{{ $type }}" {{ request('movement_type') === $type ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Contabilizado</label>
                        <select class="form-control" name="accounted">
                            <option value="">Todos</option>
                            <option value="1" {{ request('accounted') === '1' ? 'selected' : '' }}>Contabilizados</option>
                            <option value="0" {{ request('accounted') === '0' ? 'selected' : '' }}>Não contabilizados</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Texto livre</label>
                        <input class="form-control" type="text" name="q" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-md-2">
                        <label>Data de</label>
                        <input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Data até</label>
                        <input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Valor min.</label>
                        <input class="form-control" type="number" step="0.01" name="amount_min" value="{{ request('amount_min') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Valor max.</label>
                        <input class="form-control" type="number" step="0.01" name="amount_max" value="{{ request('amount_max') }}">
                    </div>
                    <div class="col-md-4" style="padding-top: 24px;">
                        <button class="btn btn-default" type="submit">Filtrar</button>
                        <a class="btn btn-link" href="{{ route('admin.cash.index') }}">Limpar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            Histórico de saldos por caixa
            @if(request('date_from') || request('date_to'))
                <small class="text-muted">
                    {{ request('date_from') ?: 'início' }} a {{ request('date_to') ?: 'hoje' }}
                </small>
            @else
                <small class="text-muted">todos os movimentos</small>
            @endif
        </div>
        <div class="panel-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Caixa</th>
                        <th class="text-right">Saldo inicial</th>
                        <th class="text-right">Entradas no período</th>
                        <th class="text-right">Saídas no período</th>
                        <th class="text-right">Saldo do período</th>
                        <th class="text-right">Saldo final</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['history'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ $money($row['opening_balance']) }}</td>
                            <td class="text-right text-success">{{ $money($row['period_income']) }}</td>
                            <td class="text-right text-danger">{{ $money($row['period_outcome']) }}</td>
                            <td class="text-right">{{ $money($row['period_balance']) }}</td>
                            <td class="text-right"><strong>{{ $money($row['closing_balance']) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Sem saldos para apresentar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">Movimentos</div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Departamento</th>
                                <th>Categoria</th>
                                <th>Caixa</th>
                                <th class="text-right">Valor</th>
                                <th>Contab.</th>
                                <th>Transferência</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                @php($type = $movement->effective_type)
                                <tr class="{{ request('highlight') == $movement->id ? 'warning' : '' }}">
                                    <td>{{ $movement->date }}</td>
                                    <td>{{ $movementTypeLabels[$type] ?? '-' }}</td>
                                    <td>
                                        <strong>{{ $movement->display_description }}</strong>
                                        @if($movement->notes)
                                            <br><small>{{ $movement->notes }}</small>
                                        @endif
                                        @if($movement->vehicle)
                                            <br><small>Viatura: {{ $movement->vehicle->license ?: $movement->vehicle->foreign_license ?: '#'.$movement->vehicle->id }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $movement->department->name ?? $movement->account_item->account_category->account_department->name ?? '-' }}</td>
                                    <td>{{ $movement->cash_category->name ?? $movement->account_item->account_category->name ?? '-' }}</td>
                                    <td>{{ $movement->cash_box->name ?? '-' }}</td>
                                    <td class="text-right {{ $type === 'income' ? 'text-success' : 'text-danger' }}">{{ $money($movement->total) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.cash.movements.accounted', $movement->id) }}">
                                            @csrf
                                            <input type="hidden" name="is_accounted" value="{{ $movement->is_accounted ? 0 : 1 }}">
                                            <button class="btn btn-xs {{ $movement->is_accounted ? 'btn-success' : 'btn-default' }}" type="submit">
                                                {{ $movement->is_accounted ? 'Sim' : 'Não' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        @if($movement->transfer_group_id)
                                            <a href="{{ route('admin.cash.index', ['transfer_group_id' => $movement->transfer_group_id]) }}">ver par</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-muted">Sem movimentos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $movements->links() }}
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">Totais por departamento</div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Departamento</th>
                                <th class="text-right">Entradas</th>
                                <th class="text-right">Saídas</th>
                                <th class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['departments'] as $department)
                                <tr>
                                    <td>{{ $department['name'] }}</td>
                                    <td class="text-right text-success">{{ $money($department['income']) }}</td>
                                    <td class="text-right text-danger">{{ $money($department['outcome']) }}</td>
                                    <td class="text-right">{{ $money($department['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Sem totais.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.cash.partials.reference-modal', [
    'modalId' => 'department-modal',
    'title' => 'Novo departamento',
    'formId' => 'department-create-form',
    'route' => route('admin.cash.departments.store'),
    'selectTargets' => ['#department_id', '#transfer_department_id', 'select[name="department_id"]'],
])

@include('admin.cash.partials.reference-modal', [
    'modalId' => 'category-modal',
    'title' => 'Nova categoria',
    'formId' => 'category-create-form',
    'route' => route('admin.cash.categories.store'),
    'selectTargets' => ['#cash_category_id', '#transfer_cash_category_id', 'select[name="cash_category_id"]'],
])

@include('admin.cash.partials.reference-modal', [
    'modalId' => 'cash-box-modal',
    'title' => 'Nova caixa',
    'formId' => 'cash-box-create-form',
    'route' => route('admin.cash.boxes.store'),
    'selectTargets' => ['#cash_box_id', '#from_cash_box_id', '#to_cash_box_id', 'select[name="cash_box_id"]'],
])
@endsection

@section('scripts')
@parent
<script>
    $(function () {
        $('.select2').select2({ width: '100%' });

        $('.js-reference-form').on('submit', function (event) {
            event.preventDefault();

            var $form = $(this);
            var $modal = $form.closest('.modal');
            var targets = String($form.data('select-targets')).split('|');
            var $error = $modal.find('.js-reference-error');

            $error.hide().text('');

            $.ajax({
                method: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            }).done(function (item) {
                targets.forEach(function (target) {
                    var $selects = $(target);
                    $selects.each(function () {
                        var $select = $(this);
                        if (!$select.find('option[value="' + item.id + '"]').length) {
                            $select.append(new Option(item.name, item.id, false, false));
                        }
                        $select.val(item.id).trigger('change');
                    });
                });

                $form[0].reset();
                $modal.modal('hide');
            }).fail(function (xhr) {
                var message = 'Não foi possível criar o registo.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    message = xhr.responseJSON.errors[firstKey][0];
                }
                $error.text(message).show();
            });
        });
    });
</script>
@endsection

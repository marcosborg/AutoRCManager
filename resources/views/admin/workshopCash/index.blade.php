@extends('layouts.admin')

@section('content')
@php($money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €')
<div class="content">
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="panel panel-primary">
        <div class="panel-heading">Caixa da Oficina</div>
        <div class="panel-body"><h2 style="margin: 0;">Saldo atual: {{ $money($balance) }}</h2></div>
    </div>

    <div class="row">
        @can('workshop_cash_transfer')
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Reforçar Caixa da Oficina</div>
                    <div class="panel-body">
                        <form method="POST" action="{{ route('admin.workshop-cash.transfers.store') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="to_cash_box_id" value="{{ $workshopBox->id }}">
                            <div class="form-group"><label>Caixa de origem</label><select class="form-control" name="from_cash_box_id" required>@foreach($cashBoxes as $box)<option value="{{ $box->id }}">{{ $box->name }}</option>@endforeach</select></div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label>Valor</label><input class="form-control" type="number" name="total" min="0.01" step="0.01" required></div></div>
                                <div class="col-md-6"><div class="form-group"><label>Data e hora</label><input class="form-control" type="datetime-local" name="occurred_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div></div>
                            </div>
                            <div class="form-group"><label>Observações</label><textarea class="form-control" name="notes"></textarea></div>
                            <div class="form-group"><label>Comprovativos <small>(recomendado)</small></label><input class="form-control" type="file" name="proofs[]" accept="image/*,application/pdf" multiple></div>
                            <button class="btn btn-primary" type="submit">Registar reforço</button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('workshop_cash_expense')
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Nova saída</div>
                    <div class="panel-body">
                        <form method="POST" action="{{ route('admin.workshop-cash.expenses.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group"><label>Categoria</label><select class="form-control" name="cash_category_id" required>@foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label>Valor</label><input class="form-control" type="number" name="total" min="0.01" step="0.01" required></div></div>
                                <div class="col-md-6"><div class="form-group"><label>Data</label><input class="form-control" type="date" name="date" value="{{ now()->format('Y-m-d') }}" required></div></div>
                            </div>
                            <div class="form-group"><label>Observações</label><textarea class="form-control" name="notes"></textarea></div>
                            <div class="form-group"><label class="required">Comprovativos</label><input class="form-control" type="file" name="proofs[]" accept="image/*,application/pdf" multiple required></div>
                            <button class="btn btn-danger" type="submit">Registar saída</button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    @can('workshop_cash_category_manage')
        <div class="panel panel-default">
            <div class="panel-heading">Categorias da Oficina</div>
            <div class="panel-body">
                <form class="form-inline" method="POST" action="{{ route('admin.workshop-cash.categories.store') }}" style="margin-bottom: 15px;">@csrf<input class="form-control" name="name" placeholder="Nova categoria" required> <button class="btn btn-success">Adicionar</button></form>
                <div class="row">@foreach($categories as $category)<div class="col-md-4"><form class="form-inline" method="POST" action="{{ route('admin.workshop-cash.categories.update', $category) }}">@csrf @method('PUT')<input class="form-control" name="name" value="{{ $category->name }}" required><select class="form-control" name="is_active"><option value="1" {{ $category->is_active ? 'selected' : '' }}>Ativa</option><option value="0" {{ !$category->is_active ? 'selected' : '' }}>Inativa</option></select><button class="btn btn-default">Guardar</button></form></div>@endforeach</div>
            </div>
        </div>
    @endcan

    <div class="panel panel-default">
        <div class="panel-heading">Histórico</div>
        <div class="panel-body">
            <form class="form-inline" method="GET" style="margin-bottom: 15px;">
                <select class="form-control" name="movement_type"><option value="">Entradas e saídas</option><option value="income" {{ request('movement_type') === 'income' ? 'selected' : '' }}>Entradas</option><option value="outcome" {{ request('movement_type') === 'outcome' ? 'selected' : '' }}>Saídas</option></select>
                <select class="form-control" name="cash_category_id"><option value="">Todas as categorias</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ (string) request('cash_category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select>
                <select class="form-control" name="created_by_id"><option value="">Todos os utilizadores</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ (string) request('created_by_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select>
                <input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}"><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}">
                <button class="btn btn-default">Filtrar</button>
            </form>
            <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Data</th><th>Tipo</th><th>Origem / Categoria</th><th>Utilizador</th><th>Observações</th><th>Comprovativos</th><th class="text-right">Valor</th></tr></thead><tbody>
                @forelse($movements as $movement)
                    @php($transfer = $movement->cash_transfer)
                    <tr><td>{{ $transfer?->occurred_at?->format('d/m/Y H:i') ?? $movement->date }}</td><td>{{ $movement->movement_type === 'income' ? 'Entrada' : 'Saída' }}</td><td>{{ $transfer ? ($transfer->from_cash_box->name.' → '.$transfer->to_cash_box->name) : ($movement->cash_category->name ?? $movement->description) }}</td><td>{{ $transfer?->created_by?->name ?? $movement->created_by?->name ?? '-' }}</td><td>{{ $movement->notes ?: '-' }}</td><td>@php($proofs = $transfer ? $transfer->proofs : $movement->proofs) @forelse($proofs as $proof)<a href="{{ $proof->getUrl() }}" target="_blank" rel="noopener">{{ $proof->file_name }}</a><br>@empty - @endforelse</td><td class="text-right {{ $movement->movement_type === 'income' ? 'text-success' : 'text-danger' }}">{{ $money($movement->total) }}</td></tr>
                @empty<tr><td colspan="7">Sem movimentos.</td></tr>@endforelse
            </tbody></table></div>{{ $movements->links() }}
        </div>
    </div>
</div>
@endsection

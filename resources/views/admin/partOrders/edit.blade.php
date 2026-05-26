@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Editar encomenda #{{ $partOrder->id }}</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.part-orders.update', $partOrder) }}">
                @csrf
                @method('PUT')
                @include('admin.partOrders.partials.form')
            </form>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">Cotações por peça</div>
        <div class="panel-body">
            @foreach($partOrder->items as $item)
                <h4>{{ $item->description }} <small>{{ $item->reference }}</small></h4>
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Fornecedor</th><th>Valor</th><th>Prazo</th><th>Selecionada</th><th></th></tr></thead>
                    <tbody>
                        @foreach($item->quotes as $quote)
                            <tr>
                                <td>{{ $quote->suplier->name ?? '-' }}</td>
                                <td>{{ number_format((float) $quote->quoted_price, 2, ',', '.') }}</td>
                                <td>{{ $quote->estimated_delivery_days }}</td>
                                <td>{{ $quote->selected ? 'Sim' : 'Nao' }}</td>
                                <td>
                                    @if(! $quote->selected)
                                        <form method="POST" action="{{ route('admin.part-orders.items.quotes.select', [$partOrder, $item, $quote]) }}">
                                            @csrf
                                            <button class="btn btn-xs btn-success" type="submit">Selecionar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <form method="POST" action="{{ route('admin.part-orders.items.quotes.store', [$partOrder, $item]) }}" class="row">
                    @csrf
                    <div class="col-md-3"><select class="form-control select2" name="suplier_id" required><option value="">Fornecedor</option>@foreach($supliers as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="quoted_price" placeholder="Valor"></div>
                    <div class="col-md-2"><input class="form-control" type="number" name="estimated_delivery_days" placeholder="Prazo dias"></div>
                    <div class="col-md-3"><input class="form-control" name="notes" placeholder="Notas"></div>
                    <div class="col-md-2"><button class="btn btn-default" type="submit">Adicionar cotacao</button></div>
                </form>
                <hr>
            @endforeach
        </div>
    </div>
</div>
@endsection

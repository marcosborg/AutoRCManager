@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Resumo de notas por fornecedor
                </div>
                <div class="panel-body">
                    @forelse($suppliers as $suplierId => $orders)
                        @php
                            $suplier = $orders->first()->suplier;
                            $totalOrdered = 0;
                            $totalReceived = 0;
                            foreach ($orders as $order) {
                                foreach ($order->items as $item) {
                                    $totalOrdered += (float) $item->qty_ordered;
                                    $totalReceived += (float) $item->qty_received;
                                }
                            }
                        @endphp
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                {{ $suplier->name ?? 'Fornecedor' }}
                                <span class="pull-right">Encomendado: {{ number_format($totalOrdered, 2, ',', '.') }} | Recebido: {{ number_format($totalReceived, 2, ',', '.') }}</span>
                            </div>
                            <div class="panel-body">
                                @foreach($orders as $order)
                                    <h5>Nota #{{ $order->id }} - {{ $order->order_date }}</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Categoria</th>
                                                    <th>Item</th>
                                                    <th class="text-right">Qtd</th>
                                                    <th class="text-right">Recebido</th>
                                                    <th class="text-right">Preco</th>
                                                    <th>&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($order->items as $item)
                                                    <tr>
                                                        <td>{{ $item->account_category->name ?? '-' }}</td>
                                                        <td>{{ $item->item_name }}</td>
                                                        <td class="text-right">{{ number_format((float) $item->qty_ordered, 2, ',', '.') }}</td>
                                                        <td class="text-right">{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                                                        <td class="text-right">
                                                            {{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2, ',', '.') : '-' }}
                                                        </td>
                                                        <td>
                                                            <form method="POST" action="{{ route('admin.supplier-orders.items.receive', $item->id) }}" style="display: inline-block;">
                                                                @csrf
                                                                <div class="input-group input-group-sm" style="width: 180px;">
                                                                    <input type="number" step="0.01" name="qty_received" class="form-control" placeholder="Dar baixa">
                                                                    <span class="input-group-btn">
                                                                        <button class="btn btn-primary" type="submit">Baixar</button>
                                                                    </span>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-muted text-center">Sem itens registados.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Sem notas de encomenda.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

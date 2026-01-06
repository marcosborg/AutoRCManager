@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.supplierOrder.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.supplier-orders.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>{{ trans('cruds.supplierOrder.fields.id') }}</th>
                                    <td>{{ $supplierOrder->id }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('cruds.supplierOrder.fields.suplier') }}</th>
                                    <td>{{ $supplierOrder->suplier->name ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('cruds.supplierOrder.fields.repair') }}</th>
                                    <td>{{ $supplierOrder->repair->id ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('cruds.supplierOrder.fields.order_date') }}</th>
                                    <td>{{ $supplierOrder->order_date }}</td>
                                </tr>
                                <tr>
                                    <th>{{ trans('cruds.supplierOrder.fields.notes') }}</th>
                                    <td>{{ $supplierOrder->notes }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Itens
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Categoria</th>
                                                <th>Item</th>
                                                <th class="text-right">Qtd</th>
                                                <th class="text-right">Recebido</th>
                                                <th class="text-right">Preco</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($supplierOrder->items as $item)
                                                <tr>
                                                    <td>{{ $item->account_category->name ?? '-' }}</td>
                                                    <td>{{ $item->item_name }}</td>
                                                    <td class="text-right">{{ number_format((float) $item->qty_ordered, 2, ',', '.') }}</td>
                                                    <td class="text-right">{{ number_format((float) $item->qty_received, 2, ',', '.') }}</td>
                                                    <td class="text-right">
                                                        {{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2, ',', '.') : '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-muted text-center">Sem itens registados.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.supplier-orders.index') }}">
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

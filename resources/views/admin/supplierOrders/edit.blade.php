@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.supplierOrder.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.supplier-orders.update", [$supplierOrder->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('suplier') ? 'has-error' : '' }}">
                            <label class="required" for="suplier_id">{{ trans('cruds.supplierOrder.fields.suplier') }}</label>
                            <select class="form-control select2" name="suplier_id" id="suplier_id" required>
                                @foreach($supliers as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('suplier_id') ? old('suplier_id') : $supplierOrder->suplier->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('suplier'))
                                <span class="help-block" role="alert">{{ $errors->first('suplier') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('repair') ? 'has-error' : '' }}">
                            <label for="repair_id">{{ trans('cruds.supplierOrder.fields.repair') }}</label>
                            <select class="form-control select2" name="repair_id" id="repair_id">
                                @foreach($repairs as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('repair_id') ? old('repair_id') : $supplierOrder->repair->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('repair'))
                                <span class="help-block" role="alert">{{ $errors->first('repair') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('order_date') ? 'has-error' : '' }}">
                            <label class="required" for="order_date">{{ trans('cruds.supplierOrder.fields.order_date') }}</label>
                            <input class="form-control date" type="text" name="order_date" id="order_date" value="{{ old('order_date', $supplierOrder->order_date) }}" required>
                            @if($errors->has('order_date'))
                                <span class="help-block" role="alert">{{ $errors->first('order_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <label for="notes">{{ trans('cruds.supplierOrder.fields.notes') }}</label>
                            <textarea class="form-control" name="notes" id="notes">{{ old('notes', $supplierOrder->notes) }}</textarea>
                            @if($errors->has('notes'))
                                <span class="help-block" role="alert">{{ $errors->first('notes') }}</span>
                            @endif
                        </div>
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

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Itens da nota
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.supplier-orders.items.store') }}">
                        @csrf
                        <input type="hidden" name="supplier_order_id" value="{{ $supplierOrder->id }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="account_category_id">Categoria</label>
                                <select class="form-control select2" name="account_category_id" id="account_category_id" required>
                                    <option selected disabled>Selecionar categoria</option>
                                    @foreach ($account_categories as $account_category)
                                        <option value="{{ $account_category->id }}">{{ $account_category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="item_name">Item</label>
                                <input class="form-control" type="text" name="item_name" id="item_name" required>
                            </div>
                            <div class="col-md-2">
                                <label for="qty_ordered">Qtd encomendada</label>
                                <input class="form-control" type="number" step="0.01" name="qty_ordered" id="qty_ordered" required>
                            </div>
                            <div class="col-md-2">
                                <label for="unit_price">Preco unitario</label>
                                <input class="form-control" type="number" step="0.01" name="unit_price" id="unit_price">
                            </div>
                            <div class="col-md-2" style="margin-top: 25px;">
                                <button class="btn btn-success btn-block" type="submit">Adicionar</button>
                            </div>
                        </div>
                    </form>

                    <hr>

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
                                @forelse($supplierOrder->items as $item)
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

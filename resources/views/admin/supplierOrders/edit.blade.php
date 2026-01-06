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
                        <div class="form-group {{ $errors->has('invoice_total_confirmed') ? 'has-error' : '' }}">
                            <label for="invoice_total_confirmed">Valor da fatura confirmado</label>
                            <input class="form-control" type="number" step="0.01" name="invoice_total_confirmed" id="invoice_total_confirmed" value="{{ old('invoice_total_confirmed', $supplierOrder->invoice_total_confirmed) }}">
                            @if($errors->has('invoice_total_confirmed'))
                                <span class="help-block" role="alert">{{ $errors->first('invoice_total_confirmed') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('parts_total_confirmed') ? 'has-error' : '' }}">
                            <label for="parts_total_confirmed">Valor das peças confirmado</label>
                            <input class="form-control" type="number" step="0.01" name="parts_total_confirmed" id="parts_total_confirmed" value="{{ old('parts_total_confirmed', $supplierOrder->parts_total_confirmed) }}">
                            @if($errors->has('parts_total_confirmed'))
                                <span class="help-block" role="alert">{{ $errors->first('parts_total_confirmed') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('invoice_attachment') ? 'has-error' : '' }}">
                            <label for="invoice_attachment">Fatura (anexo)</label>
                            <input class="form-control" type="file" name="invoice_attachment" id="invoice_attachment">
                            @if($supplierOrder->invoice_attachment)
                                <p class="help-block" style="margin-top:10px;">
                                    <a href="{{ $supplierOrder->invoice_attachment->getUrl() }}" target="_blank">Ver fatura atual</a>
                                </p>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="clear_invoice_attachment" value="1">
                                        Remover fatura atual
                                    </label>
                                </div>
                            @endif
                            @if($errors->has('invoice_attachment'))
                                <span class="help-block" role="alert">{{ $errors->first('invoice_attachment') }}</span>
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
                    @php
                        $orderedValue = $supplierOrder->items->reduce(function ($carry, $item) {
                            return $carry + ((float) $item->qty_ordered * (float) ($item->unit_price ?? 0));
                        }, 0);
                        $receivedValue = $supplierOrder->items->reduce(function ($carry, $item) {
                            return $carry + ((float) $item->qty_received * (float) ($item->unit_price ?? 0));
                        }, 0);
                        $confirmedInvoice = (float) ($supplierOrder->invoice_total_confirmed ?? 0);
                        $confirmedParts = (float) ($supplierOrder->parts_total_confirmed ?? 0);
                    @endphp
                    <div class="alert alert-info">
                        <div><strong>Total calculado (Qtd encomendada x Preço):</strong> {{ number_format($orderedValue, 2, ',', '.') }}</div>
                        <div><strong>Total calculado (Qtd recebida x Preço):</strong> {{ number_format($receivedValue, 2, ',', '.') }}</div>
                        <div><strong>Valor fatura confirmado:</strong> {{ $supplierOrder->invoice_total_confirmed !== null ? number_format($confirmedInvoice, 2, ',', '.') : '-' }}</div>
                        <div><strong>Valor peças confirmado:</strong> {{ $supplierOrder->parts_total_confirmed !== null ? number_format($confirmedParts, 2, ',', '.') : '-' }}</div>
                        @if($supplierOrder->invoice_total_confirmed !== null && $supplierOrder->parts_total_confirmed !== null)
                            <div><strong>Diferença confirmada:</strong> {{ number_format($confirmedInvoice - $confirmedParts, 2, ',', '.') }}</div>
                        @endif
                    </div>

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
                                    <th style="width: 15%;">Categoria</th>
                                    <th>Item</th>
                                    <th class="text-right" style="width: 10%;">Qtd encomendada</th>
                                    <th class="text-right" style="width: 10%;">Qtd recebida</th>
                                    <th class="text-right" style="width: 10%;">Preco</th>
                                    <th style="width: 25%;">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplierOrder->items as $item)
                                    @php $formId = 'item-update-' . $item->id; @endphp
                                    <tr>
                                        <td>
                                            <select name="account_category_id" class="form-control input-sm" form="{{ $formId }}">
                                                @foreach ($account_categories as $account_category)
                                                    <option value="{{ $account_category->id }}" {{ (int) $item->account_category_id === (int) $account_category->id ? 'selected' : '' }}>
                                                        {{ $account_category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="item_name" class="form-control input-sm" form="{{ $formId }}" value="{{ $item->item_name }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="qty_ordered" class="form-control input-sm text-right" form="{{ $formId }}" value="{{ $item->qty_ordered }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="qty_received" class="form-control input-sm text-right" form="{{ $formId }}" value="{{ $item->qty_received }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="unit_price" class="form-control input-sm text-right" form="{{ $formId }}" value="{{ $item->unit_price }}">
                                        </td>
                                        <td>
                                            <form id="{{ $formId }}" method="POST" action="{{ route('admin.supplier-orders.items.update', $item->id) }}" style="display:none;">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="submit" form="{{ $formId }}" class="btn btn-primary">Guardar</button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.supplier-orders.items.receive', $item->id) }}" style="display: inline-block; margin-left:10px;">
                                                @csrf
                                                <div class="input-group input-group-sm" style="width: 200px;">
                                                    <input type="number" step="0.01" name="qty_received" class="form-control" placeholder="Dar baixa">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="submit">Baixar</button>
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

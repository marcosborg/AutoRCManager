@extends('layouts.admin')
@section('content')
<div class="content">
    @can('part_payment_create')<p><a class="btn btn-success" href="{{ route('admin.part-payments.create') }}">Novo pagamento</a></p>@endcan
    <div class="panel panel-default"><div class="panel-heading">Pagamentos de Peças</div><div class="panel-body">
        <form method="GET" class="row" style="margin-bottom:15px;">
            <div class="col-md-3"><select class="form-control" name="payment_status"><option value="">Estado</option>@foreach(App\Models\PartPayment::STATUS_SELECT as $key => $label)<option value="{{ $key }}" {{ request('payment_status') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><select class="form-control select2" name="suplier_id"><option value="">Fornecedor</option>@foreach($supliers as $id => $label)<option value="{{ $id }}" {{ (string) request('suplier_id') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label><input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }}> Vencidos</label></div>
            <div class="col-md-2"><button class="btn btn-default" type="submit">Filtrar</button></div>
        </form>
        <table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Encomenda</th><th>Fornecedor</th><th>Valor</th><th>Vencimento</th><th>Estado</th><th></th></tr></thead><tbody>
        @foreach($partPayments as $payment)<tr><td>#{{ $payment->id }}</td><td>#{{ $payment->part_order_id }}</td><td>{{ $payment->suplier->name ?? '-' }}</td><td>{{ number_format((float) $payment->amount, 2, ',', '.') }}</td><td>{{ optional($payment->due_date)->format('Y-m-d') ?: '-' }}</td><td>@include('admin.partOrders.partials.badge', ['value' => $payment->payment_status, 'label' => App\Models\PartPayment::STATUS_SELECT[$payment->payment_status] ?? $payment->payment_status])</td><td><a class="btn btn-xs btn-primary" href="{{ route('admin.part-payments.show', $payment) }}">{{ trans('global.view') }}</a> <a class="btn btn-xs btn-info" href="{{ route('admin.part-payments.edit', $payment) }}">{{ trans('global.edit') }}</a></td></tr>@endforeach
        </tbody></table>{{ $partPayments->links() }}
    </div></div>
</div>
@endsection

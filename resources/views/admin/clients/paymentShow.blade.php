@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            Recebimento do cliente
        </div>
        <div class="panel-body">
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th>Cliente</th>
                        <td>
                            <a href="{{ route('admin.clients.edit', $client->id) }}">{{ $client->name }}</a>
                        </td>
                    </tr>
                    <tr>
                        <th>Data</th>
                        <td>{{ $payment->paid_at }}</td>
                    </tr>
                    <tr>
                        <th>M&eacute;todo</th>
                        <td>{{ $payment->payment_method->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Valor</th>
                        <td>{{ number_format((float) $payment->amount, 2, ',', '.') }} EUR</td>
                    </tr>
                    <tr>
                        <th>Notas</th>
                        <td>{{ $payment->notes }}</td>
                    </tr>
                    <tr>
                        <th>Comprovativo</th>
                        <td>
                            @forelse($payment->proof_file as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                            @empty
                                <span class="text-muted">Sem comprovativo.</span>
                            @endforelse
                        </td>
                    </tr>
                </tbody>
            </table>

            <a class="btn btn-default" href="{{ route('admin.clients.edit', $client->id) }}">Voltar</a>
        </div>
    </div>
</div>
@endsection

@php
    $lead = $notification->lead;
    $seller = $notification->user;
@endphp

<p>O envio da lead por WhatsApp falhou, por isso segue a copia por email.</p>

<p>
    <strong>Vendedor:</strong> {{ optional($seller)->name ?: '-' }}<br>
    <strong>Lead:</strong> {{ optional($lead)->full_name ?: '-' }}<br>
    <strong>Telefone:</strong> {{ optional($lead)->phone ?: '-' }}<br>
    <strong>Email:</strong> {{ optional($lead)->email ?: '-' }}<br>
    <strong>Interesse:</strong> {{ optional($lead)->vehicle_interest ?: '-' }}<br>
    <strong>Orcamento:</strong> {{ optional($lead)->budget ?: '-' }}<br>
    <strong>Compra:</strong> {{ optional($lead)->financing ?: '-' }}<br>
    <strong>Retoma:</strong> {{ optional($lead)->trade_in ?: '-' }}
</p>

@if($failureReason)
    <p><strong>Falha WhatsApp:</strong> {{ $failureReason }}</p>
@endif

@if($lead)
    <p><a href="{{ route('admin.leads.show', $lead) }}">Abrir lead</a></p>
@endif

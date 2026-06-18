<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lead #{{ $lead->id }} - Car 7</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f5f7; color: #1f2933; }
        .wrap { max-width: 780px; margin: 0 auto; padding: 18px; }
        .panel { background: #fff; border: 1px solid #d9e2ec; border-radius: 6px; margin-bottom: 14px; }
        .head { padding: 16px 18px; border-bottom: 1px solid #d9e2ec; }
        .body { padding: 18px; }
        h1 { margin: 0; font-size: 22px; }
        h2 { margin: 0 0 12px; font-size: 17px; }
        dl { display: grid; grid-template-columns: 150px 1fr; gap: 10px 14px; margin: 0; }
        dt { font-weight: bold; color: #52606d; }
        dd { margin: 0; }
        .msg { border-left: 3px solid #9fb3c8; padding: 8px 10px; margin-bottom: 10px; background: #f8fafc; white-space: pre-wrap; }
        .sender { font-weight: bold; color: #334e68; }
        .muted { color: #7b8794; font-size: 12px; }
        @media (max-width: 620px) { dl { grid-template-columns: 1fr; gap: 4px; } dt { margin-top: 8px; } }
    </style>
</head>
<body>
<main class="wrap">
    <section class="panel">
        <div class="head">
            <h1>Lead #{{ $lead->id }}</h1>
            <div class="muted">Link valido ate {{ optional($accessToken->expires_at)->format('Y-m-d H:i') }}</div>
        </div>
        <div class="body">
            <dl>
                <dt>Nome</dt><dd>{{ $lead->full_name ?: trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: '-' }}</dd>
                <dt>Telefone</dt><dd>{{ $lead->phone ?: '-' }}</dd>
                <dt>Email</dt><dd>{{ $lead->email ?: '-' }}</dd>
                <dt>Interesse</dt><dd>{{ $lead->vehicle_interest ?: '-' }}</dd>
                <dt>Orcamento</dt><dd>{{ $lead->budget ?: '-' }}</dd>
                <dt>Compra</dt><dd>{{ $lead->financing ?: '-' }}</dd>
                <dt>Retoma</dt><dd>{{ $lead->trade_in ?: '-' }}</dd>
                <dt>Prazo compra</dt><dd>{{ data_get($lead->raw_data, 'purchase_timeline') ?: data_get($lead->raw_data, 'qualification.purchase_timeline') ?: '-' }}</dd>
                <dt>Visita</dt><dd>{{ data_get($lead->raw_data, 'wants_visit') ?: data_get($lead->raw_data, 'qualification.wants_visit') ?: '-' }}</dd>
                <dt>Vendedor</dt><dd>{{ $lead->assigned_user->name ?? $accessToken->user->name ?? '-' }}</dd>
            </dl>
        </div>
    </section>

    @if($messages->isNotEmpty())
        <section class="panel">
            <div class="head"><h2>Historico da conversa IA</h2></div>
            <div class="body">
                @foreach($messages as $message)
                    <div class="msg">
                        <div class="sender">{{ ucfirst($message['sender'] ?? '-') }} <span class="muted">{{ $message['created_at'] ?? '' }}</span></div>
                        {{ $message['message'] ?? '' }}
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</main>
</body>
</html>

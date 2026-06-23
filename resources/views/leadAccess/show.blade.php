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
        .actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 16px; }
        .btn { display: block; text-align: center; text-decoration: none; padding: 12px 14px; border-radius: 6px; font-weight: bold; }
        .btn-call { background: #0f766e; color: #fff; }
        .btn-whatsapp { background: #15803d; color: #fff; }
        .btn-disabled { background: #d9e2ec; color: #52606d; pointer-events: none; }
        .chat { background: #eef2f6; border-radius: 6px; padding: 14px; }
        .bubble-row { display: flex; margin-bottom: 10px; }
        .bubble-row.customer { justify-content: flex-start; }
        .bubble-row.assistant, .bubble-row.human { justify-content: flex-end; }
        .bubble { max-width: 82%; border-radius: 8px; padding: 9px 11px; white-space: pre-wrap; line-height: 1.35; box-shadow: 0 1px 2px rgba(15, 23, 42, .08); }
        .customer .bubble { background: #fff; border: 1px solid #d9e2ec; }
        .assistant .bubble { background: #dcfce7; border: 1px solid #bbf7d0; }
        .human .bubble { background: #dbeafe; border: 1px solid #bfdbfe; }
        .sender { display: block; margin-bottom: 4px; font-weight: bold; color: #334e68; font-size: 12px; }
        .muted { color: #7b8794; font-size: 12px; }
        @media (max-width: 620px) {
            .wrap { padding: 10px; }
            dl { grid-template-columns: 1fr; gap: 4px; }
            dt { margin-top: 8px; }
            .actions { grid-template-columns: 1fr; }
            .bubble { max-width: 92%; }
        }
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
            <div class="actions">
                @if($callUrl)
                    <a class="btn btn-call" href="{{ $callUrl }}">Telefonar agora</a>
                @else
                    <span class="btn btn-disabled">Sem telefone</span>
                @endif

                @if($whatsappUrl)
                    <a class="btn btn-whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Falar no WhatsApp</a>
                @else
                    <span class="btn btn-disabled">Sem WhatsApp</span>
                @endif
            </div>
        </div>
    </section>

    @if($messages->isNotEmpty())
        <section class="panel">
            <div class="head"><h2>Historico da conversa IA</h2></div>
            <div class="body">
                <div class="chat">
                    @foreach($messages as $message)
                        @php
                            $sender = $message['sender'] ?? '-';
                            $label = \App\Models\ChatMessage::SENDER_SELECT[$sender] ?? ucfirst($sender);
                            $class = in_array($sender, ['customer', 'assistant', 'human'], true) ? $sender : 'customer';
                        @endphp
                        <div class="bubble-row {{ $class }}">
                            <div class="bubble">
                                <span class="sender">{{ $label }} <span class="muted">{{ $message['created_at'] ?? '' }}</span></span>
                                {{ $message['message'] ?? '' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <section class="panel">
            <div class="head"><h2>Historico da conversa IA</h2></div>
            <div class="body">
                <div class="muted">Esta lead nao tem conversa IA associada.</div>
            </div>
        </section>
    @endif
</main>
</body>
</html>

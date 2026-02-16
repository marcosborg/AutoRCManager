<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Timeline</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        .totals { margin-top: 12px; }
        .footer { margin-top: 16px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>Timeline da Viatura</h1>
    <div class="meta">
        <div><strong>Matricula:</strong> {{ $vehicle->license ?? $vehicle->foreign_license ?? $vehicle->id }}</div>
        <div><strong>Modelo:</strong> {{ $vehicle->brand->name ?? '' }} {{ $vehicle->model ?? '' }}</div>
        <div>
            <strong>Periodo:</strong>
            @if($startsAt && $endsAt)
                {{ $startsAt->format(config('panel.date_format')) }} - {{ $endsAt->format(config('panel.date_format')) }}
            @else
                N/A
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data(s)</th>
                <th>Tipo</th>
                <th>Descricao</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td>
                        {{ $event['date_start']->format(config('panel.date_format') . ' ' . config('panel.time_format')) }}
                        @if($event['date_end'])
                            - {{ $event['date_end']->format(config('panel.date_format') . ' ' . config('panel.time_format')) }}
                        @endif
                    </td>
                    <td>{{ $event['type'] }}</td>
                    <td>{{ $event['description'] }}</td>
                    <td>
                        @if(! is_null($event['amount']))
                            €{{ number_format((float) $event['amount'], 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Sem eventos para esta viatura.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <div><strong>Total custos:</strong> €{{ number_format($totalCost, 2, ',', '.') }}</div>
        <div><strong>Total receitas:</strong> €{{ number_format($totalRevenue, 2, ',', '.') }}</div>
        <div><strong>Resultado:</strong> €{{ number_format($result, 2, ',', '.') }}</div>
    </div>

    <div class="footer">
        Valores informativos. Nao incluem impostos ou amortizacoes.
    </div>
</body>
</html>

<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Declaração de entrega de veículo</title>
<style>
@page { margin: 18mm 20mm; }
body { font-family: 'DejaVu Serif', serif; font-size: 11pt; line-height: 1.55; color: #111; }
.logo { text-align:center; margin-bottom:25px; } .logo img { width:220px; }
h1 { font-size:16pt; text-align:center; margin:0 0 26px; }
p { text-align:justify; overflow-wrap:break-word; } .value { border-bottom:1px solid #555; }
.faults { white-space:pre-wrap; overflow-wrap:break-word; margin-bottom:25px; }
.date { text-align:right; margin-top:25px; }
.signatures { width:100%; margin-top:50px; table-layout:fixed; page-break-inside:avoid; text-align:center; }
.signatures td { width:50%; padding:0 18px; } .line { border-top:1px solid #333; padding-top:5px; }
.toolbar { font:14px Arial,sans-serif; background:#f5f5f5; padding:15px; margin-bottom:20px; } .toolbar a { margin-right:18px; }
@media print { .toolbar { display:none; } }
</style></head><body>
@unless($pdf ?? false)
<div class="toolbar"><a href="{{ route('admin.vehicle-trade-ins.delivery.edit', $tradeIn) }}">Alterar dados</a><a href="{{ route('admin.vehicle-trade-ins.delivery.pdf', $tradeIn) }}">Descarregar PDF</a><button onclick="window.print()">Imprimir</button>
@if(!empty($missingFields))<p>Campos por preencher: {{ implode(', ', $missingFields) }}. Complete-os antes de assinar.</p>@endif</div>
@endunless
@php
    $value = fn ($key, $blank = '________________________') => trim((string) ($data[$key] ?? '')) !== '' ? $data[$key] : $blank;
    $date = !empty($data['delivery_date']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $data['delivery_date'])->locale('pt')->translatedFormat('d \d\e F \d\e Y') : '_______ de ____________________ de __________';
@endphp
<div class="logo"><img alt="CAR7 Automóveis" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('documents/car7/image1.png'))) }}"></div>
<h1>DECLARAÇÃO DE ENTREGA DE VEÍCULO</h1>
<p>Eu, <span class="value">{{ $value('name') }}</span>, residente em <span class="value">{{ $value('address') }}</span>, código postal <span class="value">{{ $value('zip', '________ - ________') }}</span>, localidade <span class="value">{{ $value('location') }}</span>, portador do Cartão de Cidadão / Bilhete de Identidade nº <span class="value">{{ $value('identity') }}</span>, contribuinte nº <span class="value">{{ $value('vat') }}</span>, declaro para todos os devidos e legais efeitos que, na data abaixo indicada, entreguei a viatura com a matrícula <span class="value">{{ $value('license') }}</span>, marca <span class="value">{{ $value('brand') }}</span>, modelo <span class="value">{{ $value('model') }}</span>, da qual sou proprietário, no Stand de Automóveis Car7 (Geração Determinada Automóveis Lda), situado na Nacional 109, Rua da Pardala, 3880-728 Ovar, a título de retoma no âmbito da aquisição de outra viatura.</p>
<p>Por sua vez, a Geração Determinada Automóveis Lda declara que é responsável por qualquer tipo de coima ou dano causado pela viatura durante o período em que esta se encontra na sua posse.</p>
<p>Esta viatura deu entrada com <span class="value">{{ $value('kilometers') }}</span> km.</p>
<p>Declaro que a mesma se encontra consoante confirmado com o vendedor (avarias, danos visíveis e não visíveis).</p>
<p><strong>Avarias e danos a informar pelo cliente:</strong></p>
<div class="faults">{{ $value('faults', '__________________________________________________________________'."\n".'__________________________________________________________________') }}</div>
<p class="date">Ovar, {{ $date }}.</p>
<table class="signatures"><tr><td><div class="line">O Proprietário</div></td><td><div class="line">O Stand</div></td></tr></table>
</body></html>

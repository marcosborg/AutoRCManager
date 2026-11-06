<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Termo de responsabilidade</title>
<style>
@page { margin: 17mm 20mm; }
body { font-family: 'DejaVu Serif', serif; font-size: 11pt; line-height: 1.3; color: #111; }
.logo { text-align:center; margin-bottom:16px; } .logo img { width:220px; }
h1 { font-size:17pt; text-align:center; margin:0 0 20px; }
p { text-align:justify; overflow-wrap:break-word; } ol { padding-left:25px; font-weight:bold; } li { margin-bottom:12px; text-align:justify; }
h2 { text-align:center; font-size:14pt; margin:24px 0 18px; }
.vehicle p { margin:14px 0; } .value { border-bottom:1px solid #555; }
.signatures { width:100%; margin-top:45px; table-layout:fixed; page-break-inside:avoid; text-align:center; }
.signatures td { width:50%; padding:0 18px; } .line { border-top:1px solid #333; padding-top:5px; }
.toolbar { font:14px Arial,sans-serif; background:#f5f5f5; padding:15px; margin-bottom:20px; } .toolbar a { margin-right:18px; }
@media print { .toolbar { display:none; } }
</style></head><body>
@unless($pdf ?? false)
<div class="toolbar"><a href="{{ route('admin.vehicles.documents.responsibility.edit', $vehicle) }}">Alterar dados</a><a href="{{ route('admin.vehicles.documents.responsibility.pdf', $vehicle) }}">Descarregar PDF</a><button onclick="window.print()">Imprimir</button>
@if(!empty($missingFields))<p>Campos por preencher: {{ implode(', ', $missingFields) }}. Complete-os antes de assinar.</p>@endif</div>
@endunless
@php
    $value = fn ($key, $blank = '________________________') => trim((string) ($data[$key] ?? '')) !== '' ? $data[$key] : $blank;
    $date = fn ($key) => !empty($data[$key]) ? \Carbon\Carbon::createFromFormat('Y-m-d', $data[$key])->format('d / m / Y') : '____ / ____ / ________';
@endphp
<div class="logo"><img alt="CAR7 Automóveis" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('documents/car7/image1.png'))) }}"></div>
<h1>TERMO DE RESPONSABILIDADE</h1>
<p>Eu abaixo assinado <span class="value">{{ $value('name') }}</span> portador do Bilhete de identidade / Cartão Cidadão nº <span class="value">{{ $value('identity') }}</span> contribuinte nº <span class="value">{{ $value('vat') }}</span>, declaro para todos os devidos e legais efeitos de forma irrevogável assumir o cumprimento das seguintes obrigações.</p>
<ol><li>A responsabilidade Civil e Criminal, pelos danos causados a terceiros ou no próprio veículo, bem como a responsabilidade contra ordenacional proveniente de multas e coimas e possíveis scuts devido à passagem da viatura nos pórticos;</li>
<li>Obrigação de celebrar e manter atualizado, nos termos legalmente prescritos o seguro automóvel obrigatório, assim como o imposto único de circulação.</li></ol>
<p>Mais confirmo que é minha e igual à que consta no meu Cartão de Identidade / Cartão de Cidadão a assinatura que constará no requerimento de Declaração para o Registo de Propriedade do veículo e nos demais documentos necessários ao registo de propriedade e que desonera o vendedor de qualquer responsabilidade Civil ou Criminal.</p>
<div class="vehicle"><h2>Dados da Viatura</h2>
<p>Matrícula <span class="value">{{ $value('license') }}</span> &nbsp; Ano: {{ $value('year', '________') }} &nbsp; Mês: {{ $value('month', '________') }}</p>
<p>Chassi nº <span class="value">{{ $value('vin', '________________________________________________') }}</span></p>
<p>Dia de levantamento: {{ $date('pickup_date') }} às {{ $value('pickup_time', '____ : ____') }} horas.</p>
<p>Dia de Entrega: {{ $date('return_date') }} às {{ $value('return_time', '____ : ____') }} horas.</p></div>
<table class="signatures"><tr><td><div class="line">O Cliente</div></td><td><div class="line">O Stand</div></td></tr></table>
</body></html>

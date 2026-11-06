<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Declaração de compra e venda</title>
<style>
@page { size:A4; margin:10mm 14mm; }
body { font-family:DejaVu Serif,serif; font-size:9pt; line-height:1.15; color:#000; }
.logo { text-align:center; } .logo img { width:45mm; height:auto; }
h1 { font-size:16pt; text-align:center; margin:4mm 0 0; } .subtitle { text-align:center; font-weight:bold; margin:0 0 3mm; }
h2 { font-size:10pt; margin:3mm 0 2mm; } p { margin:1mm 0; overflow-wrap:break-word; word-wrap:break-word; }
table { width:100%; table-layout:fixed; border-collapse:collapse; } td { padding:1mm 3mm 1mm 0; vertical-align:top; word-wrap:break-word; }
.money { width:70%; margin-left:30%; } .money td:last-child { text-align:right; }
.rgpd { font-size:9pt; text-align:justify; line-height:1.25; } .signatures { margin-top:7mm; } .signatures td { padding-top:3mm; }
.footer { font-size:7pt; } .section { page-break-inside:avoid; }
.toolbar { font-family:Arial,sans-serif; padding:12px; background:#eee; margin-bottom:12px; } .toolbar a,.toolbar button { margin-right:15px; }
@media screen { body { max-width:760px; margin:24px auto; padding:15px; } }
@media print { .toolbar { display:none; } }
</style></head><body>
@if(empty($pdf))<div class="toolbar"><a href="{{ route('admin.vehicles.documents.sale.edit', $vehicle) }}">Rever dados</a><a href="{{ route('admin.vehicles.documents.sale.pdf', $vehicle) }}">Descarregar PDF</a><button onclick="window.print()">Imprimir</button><p>Confira antes de usar. Não foi registado qualquer pagamento ou assinatura. Depois da assinatura, anexe a cópia em Documentos da viatura, com o tipo Declaração de venda assinada.</p></div>@endif
<div class="logo"><img alt="Car7 Automóveis" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('documents/car7/image1.png'))) }}"></div>
<h1>DECLARAÇÃO DE COMPRA E VENDA</h1><p class="subtitle">Serve a presente como prova de sinalização</p>
<div class="section"><h2>Dados do cliente:</h2>
<p>Nome: {{ $data['name'] }}</p><table><tr><td>BI/CC: {{ $data['identity'] ?: '________________' }}</td><td>NIF: {{ $data['vat'] }}</td></tr></table>
<p>Morada: {{ $data['address'] }}</p><p>Código Postal: {{ $data['zip'] ?: '_______-____' }} &nbsp; {{ $data['location'] }}</p>
<p>Contacto: {{ $data['phone'] }} &nbsp; Email: {{ $data['email'] }}</p></div>
<div class="section"><h2>Dados da viatura:</h2><table><tr><td>Marca: {{ $data['brand'] }}</td><td>Modelo: {{ $data['model'] }}</td></tr><tr><td>Ano: {{ $data['year'] }}</td><td>Matrícula: {{ $data['license'] }}</td></tr></table>
<p>Garantia: SIM {{ $data['guarantee'] === 'yes' ? '[X]' : '[ ]' }} &nbsp; NÃO {{ $data['guarantee'] === 'no' ? '[X]' : '[ ]' }} &nbsp; Meses: {{ $data['months'] ?? '—' }}</p>
<p>Nº Chassi: {{ $data['vin'] ?: '________________________________' }}</p>
<table class="money">@foreach(\App\Http\Controllers\Admin\VehicleSaleDeclarationController::AMOUNTS as $key => $label)@if($key !== 'trade_value')<tr><td>{{ $label }}:</td><td>{{ number_format((float) $data[$key], 2, ',', '.') }} €</td></tr>@endif @endforeach</table></div>
<div class="section"><h2>Retoma:</h2><p>Marca: {{ $data['trade_brand'] ?: '________________' }} &nbsp; Matrícula: {{ $data['trade_license'] ?: '________________' }} &nbsp; Valor: {{ number_format((float) $data['trade_value'], 2, ',', '.') }} €</p></div>
<div class="section"><h2>RGPD</h2>
<p class="rgpd">Para os efeitos previstos no artigo 13.º do Regulamento Geral de Proteção de Dados (RGPD), (EU)2016/679 do Parlamento Europeu e do Conselho de 27 de abril de 2016, <strong>declaro que fui informado(a)</strong> e que pelo presente meio <strong>expresso o meu consentimento</strong> sobre o tratamento dos meus dados pessoais, para os fins de concretização de todos os procedimentos relacionados com a aquisição do veículo automóvel em associação aos dados contratuais relacionados com o mesmo veículo, bem como para fins de marketing, identificação e reconhecimento de clientes, aceitando que tais dados sejam tratados pela Geração Determinada – Automóveis, Lda., sociedade comercial por quotas com o número único de matrícula e de pessoa coletiva 509 054 943, com sede na Avenida da República, nº 1326, 6, sala 68, 4430-192 Vila Nova de Gaia, durante um período de cinco anos a contar da presente data.</p></div>
<div class="section"><p style="margin-top:6mm">Data: {{ \Carbon\Carbon::createFromFormat('Y-m-d', $data['document_date'])->format('d/m/Y') }}</p>
<table class="signatures"><tr><td>_____________________________<br>(O Stand)</td><td>_____________________________<br>(O Cliente)</td></tr></table>
<p class="footer">Estrada nacional 109 (KM32) Lugar da Pardala|3880-728 Ovar</p></div>
</body></html>

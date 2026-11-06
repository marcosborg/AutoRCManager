<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Declaração de garantia automóvel</title>
<style>
@page { size:A4; margin:12mm 14mm; }
body { font-family:DejaVu Serif,serif; font-size:10pt; line-height:1.15; color:#000; }
.logo { text-align:center; } .logo img { width:50mm; height:auto; }
h1 { font-size:15pt; text-align:center; margin:3mm 0 4mm; } h2 { font-size:12pt; margin:3mm 0 2mm; }
p { margin:1mm 0; word-wrap:break-word; } .exclusions-title { margin-top:3mm; }
.exclusions { font-size:9pt; font-weight:bold; margin:1mm 0 3mm; padding-left:6mm; } li { margin:0.4mm 0; }
.section { page-break-inside:avoid; } table { width:100%; table-layout:fixed; border-collapse:collapse; } td { vertical-align:top; padding:1mm 3mm 1mm 0; word-wrap:break-word; }
.signatures { margin-top:12mm; text-align:center; } .signatures td { padding:3mm; }
.toolbar { font-family:Arial,sans-serif; background:#eee; padding:12px; margin-bottom:15px; } .toolbar a,.toolbar button { margin-right:12px; }
@media screen { body { max-width:760px; margin:24px auto; padding:15px; } } @media print { .toolbar { display:none; } }
</style></head><body>
@if(empty($pdf))<div class="toolbar"><a href="{{ route('admin.vehicles.documents.warranty.edit', $vehicle) }}">Rever dados</a><a href="{{ route('admin.vehicles.documents.warranty.pdf', $vehicle) }}">Descarregar PDF</a><button onclick="window.print()">Imprimir</button><p>Confira os dados e as condições antes de assinar. Os campos não preenchidos ficam em branco.</p></div>@endif
<div class="logo"><img alt="Car7 Automóveis" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('documents/car7/image1.png'))) }}"></div>
<h1>DECLARAÇÃO DE GARANTIA AUTOMÓVEL</h1>
@include('admin.vehicles.partials.warranty-terms')
<div class="section">
<p>A presente garantia tem como data de início o dia: {{ !empty($data['starts_on']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $data['starts_on'])->format('d / m / Y') : '________ / ________ / ________' }}</p>
<p>Data de Conclusão: {{ !empty($data['ends_on']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $data['ends_on'])->format('d / m / Y') : '________ / ________ / ________' }}</p>
<p>Esta Garantia terá também terminado ao serem circulados: {{ isset($data['covered_kilometers']) ? number_format($data['covered_kilometers'], 0, ',', '.') : '___________________' }} quilómetros.</p>
</div><div class="section"><h2>Viatura</h2>
<table><tr><td>Marca: {{ $data['brand'] }}</td><td>Modelo: {{ $data['model'] }}</td><td>Matrícula: {{ $data['license'] }}</td></tr></table>
<p>Chassi nº: {{ $data['vin'] ?: '________________________________' }} &nbsp; Quilómetros: {{ isset($data['kilometers']) ? number_format($data['kilometers'], 0, ',', '.') : '___________________' }}</p>
</div><div class="section"><h2>Dados cliente:</h2>
<p>Nome: {{ $data['name'] }}</p><p>Cartão de Cidadão/ Bilhete de Identidade nº: {{ $data['identity'] ?: '_____________________' }}.</p><p>Número de Contribuinte: {{ $data['vat'] }}.</p>
<table class="signatures"><tr><td>_____________________________<br>O Stand</td><td>_____________________________<br>O Cliente</td></tr></table></div>
</body></html>

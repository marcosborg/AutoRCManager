<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Declaração de circulação</title>
<style>
@page { margin: 18mm 22mm; }
body { font-family: 'DejaVu Serif', serif; font-size: 10pt; line-height: 1.4; color: #111; }
.logo { text-align:center; margin-bottom:16px; } .logo img { width:240px; }
h1 { font-size:17pt; text-align:center; margin:0 0 24px; }
.intro { text-align:justify; } .fields { margin:18px 0 24px 60px; }
.field { margin: 0 0 9px; overflow-wrap:break-word; }
.value { border-bottom:1px solid #333; display:inline-block; min-width:210px; max-width:340px; }
.note { font-size:10pt; line-height:1.4; text-align:justify; }
.signature { text-align:center; margin-top:28px; } .signature-line { margin:0 auto; height:42px; width:300px; border-bottom:1px solid #333; }
.toolbar { font:14px Arial,sans-serif; background:#f5f5f5; padding:15px; margin-bottom:20px; } .toolbar a { margin-right:18px; }
@media print { .toolbar { display:none; } }
</style></head><body>
@unless($pdf ?? false)
<div class="toolbar"><a href="{{ route('admin.vehicles.documents.circulation.edit', $vehicle) }}">Alterar dados</a><a href="{{ route('admin.vehicles.documents.circulation.pdf', $vehicle) }}">Descarregar PDF</a><button onclick="window.print()">Imprimir</button>
@if(!empty($missingFields))<p>Campos por preencher: {{ implode(', ', $missingFields) }}. Complete-os antes de assinar.</p>@endif
</div>
@endunless
<div class="logo"><img alt="CAR7 Automóveis" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('documents/car7/image1.png'))) }}"></div>
<h1>DECLARAÇÃO DE CIRCULAÇÃO</h1>
<p class="intro">Geração Determinada Automóveis, Lda., CAR7, com morada na Estrada Nacional 109 – Lugar da Pardala s/n, 3880-728 S. João de Ovar, declara para os devidos efeitos e em especial para fazer fé perante as autoridades de trânsito, que vendeu em {{ \Carbon\Carbon::createFromFormat('Y-m-d', $data['sold_on'])->format('d / m / Y') }} ao Sr. /Srª <strong>{{ $data['name'] }}</strong> a viatura com as seguintes caraterísticas:</p>
<div class="fields">@foreach(\App\Http\Controllers\Admin\VehicleCirculationDeclarationController::FIELDS as $key => $label)
@if($key !== 'name')<div class="field"><strong>{{ $label }}:</strong> <span class="value">{{ trim((string) ($data[$key] ?? '')) !== '' ? $data[$key] : ' ' }}</span></div>@endif
@endforeach</div>
<p class="note">Nota: o condutor desta viatura não é portador da respetiva documentação em virtude de a mesma se encontrar na conservatória de registo automóvel para efeitos de transferência de propriedade.<br>Declaração sancionada por despacho de secretariado do estado de orçamento de 23 de Fevereiro de 1978.</p>
<div class="signature">Assinatura<div class="signature-line"></div></div>
</body></html>

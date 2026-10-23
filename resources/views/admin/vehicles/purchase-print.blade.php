<!doctype html>
<html lang="pt"><head><meta charset="utf-8"><title>Impresso de compra a particular</title>
<style>
@page { size:A4; margin:20mm; }
body { font-family:DejaVu Sans,Arial,sans-serif; font-size:11px; color:#263238; line-height:1.55; }
h1 { font-size:23px; margin:6px 0 2px; color:#253d57; } h2 { font-size:12px; color:#253d57; margin:0 0 10px; }
.section { margin:18px 0; padding:14px 16px; border:1px solid #d9e0e6; page-break-inside:avoid; }
.label { color:#60707c; font-size:10px; } .value { margin-bottom:7px; overflow-wrap:break-word; word-wrap:break-word; }
.notice { font-size:10px; color:#65737d; } .top { text-transform:uppercase; font-size:9px; letter-spacing:2px; color:#60707c; }
table { width:100%; border-collapse:collapse; table-layout:fixed; } td { vertical-align:top; padding-right:15px; overflow-wrap:break-word; }
.signatures td { padding-top:40px; width:50%; } .line { border-top:1px solid #82909a; padding-top:8px; }
.toolbar { background:#eef2f6; padding:14px; margin-bottom:25px; font-family:Arial,sans-serif; } .toolbar a,.toolbar button { display:inline-block; padding:9px 14px; margin-right:8px; background:white; border:1px solid #bbc7d1; color:#253d57; text-decoration:none; cursor:pointer; }
@media screen { body { max-width:760px; margin:25px auto; padding:0 20px; font-size:13px; } }
@media print { .toolbar { display:none; } }
</style></head><body>
@if(empty($pdf))
<div class="toolbar"><a href="{{ route('admin.vehicles.documents.purchase.edit', $vehicle) }}">← Rever dados</a><a href="{{ route('admin.vehicles.documents.purchase.pdf', $vehicle) }}">Descarregar PDF</a><button onclick="window.print()">Imprimir</button><p>Confira antes de imprimir. Esta preparação não altera a ficha nem confirma o pagamento.</p></div>
@endif
<div class="top">Auto RC · Documentação de compra</div>
<h1>Compra de viatura a particular</h1>
<p class="notice">Modelo para revisão e validação · {{ $vehicle->license ?: $vehicle->foreign_license }}</p>
<div class="section"><h2>01 · Particular vendedor</h2>
<div class="label">Nome completo</div><div class="value">{{ $data['seller_name'] }}</div>
<div class="label">NIF</div><div class="value">{{ $data['seller_nif'] }}</div>
<div class="label">Morada</div><div>{{ $data['seller_address'] }}</div></div>
<div class="section"><h2>02 · Empresa compradora</h2>
<div class="label">Denominação</div><div class="value">{{ $data['buyer_name'] }}</div>
<div class="label">NIF</div><div class="value">{{ $data['buyer_nif'] }}</div>
<div class="label">Morada</div><div>{{ $data['buyer_address'] }}</div></div>
<div class="section"><h2>03 · Viatura e valor acordado</h2>
<table><tr><td><div class="label">Matrícula</div><div class="value">{{ $vehicle->license ?: $vehicle->foreign_license }}</div></td><td><div class="label">Marca e modelo</div><div class="value">{{ $vehicle->brand?->name }} {{ $vehicle->model }}</div></td></tr>
<tr><td><div class="label">Número de quadro</div><div class="value">{{ $data['vin'] ?: 'Não indicado' }}</div></td><td><div class="label">Valor acordado</div><div class="value"><strong>{{ number_format((float) $data['amount'], 2, ',', '.') }} €</strong></div></td></tr>
<tr><td><div class="label">Local</div><div>{{ $data['place'] }}</div></td><td><div class="label">Data da compra</div><div>{{ \Carbon\Carbon::createFromFormat('Y-m-d', $data['purchase_date'])->format('d/m/Y') }}</div></td></tr></table></div>
<p>Identificação das partes, da viatura e do valor acordado para conferência e assinatura.</p>
<table class="signatures"><tr><td><div class="line">O particular vendedor</div></td><td><div class="line">Pela empresa compradora</div></td></tr></table>
<p class="notice" style="margin-top:30px">Este impresso não constitui confirmação de pagamento. Não inclui declaração de quitação nem consentimento RGPD.</p>
</body></html>

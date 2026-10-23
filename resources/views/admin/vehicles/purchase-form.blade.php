@extends('layouts.admin')
@section('content')
<a href="{{ route('admin.vehicles.edit', $vehicle) }}#vehicle-documents">← Voltar aos documentos da viatura</a>
<h1>Preparar impresso de compra a particular</h1>
<p><strong>{{ $vehicle->license ?: $vehicle->foreign_license }} · {{ $vehicle->brand?->name }} {{ $vehicle->model }}</strong></p>
<div class="alert alert-info">Confira o particular vendedor e a empresa compradora separadamente. Os dados deste impresso não alteram a ficha, o cadastro de fornecedores nem os pagamentos.</div>
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Falta conferir:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.vehicles.documents.purchase.prepare', $vehicle) }}">
@csrf
<div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">1. Particular vendedor</h3></div><div class="panel-body">
<p>Escolha quem vende a viatura no cadastro de fornecedores. Confirme os dados sugeridos.</p>
<div class="form-group"><label for="seller_id">Vendedor *</label><select id="seller_id" name="seller_id" class="form-control" required><option value="">Escolher vendedor</option>@foreach($sellers as $seller)<option value="{{ $seller->id }}" @selected((string) old('seller_id', $data['seller_id'] ?? '') === (string) $seller->id)>{{ $seller->name }}{{ $seller->nif ? ' · '.$seller->nif : '' }}</option>@endforeach</select></div>
@can('suplier_create')<p><a href="{{ route('admin.supliers.create') }}" target="_blank" rel="noopener" class="btn btn-default">Criar novo vendedor</a> <button type="button" id="refresh-sellers" class="btn btn-default">Atualizar lista de vendedores</button></p><p class="help-block">Crie o vendedor no separador que abre. Depois volte aqui, atualize a lista e escolha o novo registo. Os restantes campos mantêm-se.</p>@endcan
<p id="seller-feedback" role="status" aria-live="polite"></p>
@foreach(['seller_name' => 'Nome completo do vendedor', 'seller_nif' => 'NIF do vendedor', 'seller_address' => 'Morada do vendedor'] as $field => $label)
<div class="form-group"><label for="{{ $field }}">{{ $label }} *</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $data[$field] ?? '') }}" required maxlength="{{ str_contains($field, 'address') ? 300 : 150 }}"></div>
@endforeach
</div></div>
<div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">2. Empresa compradora</h3></div><div class="panel-body">
<p>Escolha uma das três empresas do grupo que compra a viatura.</p>
<div class="form-group"><label for="buyer_id">Empresa compradora *</label><select id="buyer_id" name="buyer_id" class="form-control" required><option value="">Escolher empresa do grupo</option>@foreach($buyers as $id => $name)<option value="{{ $id }}" @selected(old('buyer_id', $data['buyer_id'] ?? '') === $id)>{{ $name }}</option>@endforeach</select></div>
<p class="help-block">Confirme o NIF e a morada da empresa escolhida. Ao mudar de compradora, estes campos ficam em branco para evitar usar os dados da empresa anterior.</p>
@foreach(['buyer_nif' => 'NIF da empresa compradora', 'buyer_address' => 'Morada da empresa compradora'] as $field => $label)
<div class="form-group"><label for="{{ $field }}">{{ $label }} *</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $data[$field] ?? '') }}" required maxlength="{{ str_contains($field, 'address') ? 300 : 150 }}"></div>
@endforeach
<p class="help-block">Pode completar ou corrigir os dados apenas para este impresso.</p>
</div></div>
<div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">3. Dados da compra</h3></div><div class="panel-body">
<div class="form-group"><label for="vin">Número de quadro (se disponível)</label><input class="form-control" id="vin" name="vin" maxlength="150" value="{{ old('vin', $data['vin'] ?? '') }}"></div>
<div class="row"><div class="col-md-4 form-group"><label for="amount">Valor acordado (€) *</label><input class="form-control" id="amount" name="amount" inputmode="decimal" required value="{{ old('amount', $data['amount'] ?? '') }}"><small>Exemplo: 12500,00</small></div>
<div class="col-md-4 form-group"><label for="purchase_date">Data da compra *</label><input class="form-control" type="date" id="purchase_date" name="purchase_date" required value="{{ old('purchase_date', $data['purchase_date'] ?? '') }}"></div>
<div class="col-md-4 form-group"><label for="place">Local *</label><input class="form-control" id="place" name="place" required maxlength="150" value="{{ old('place', $data['place'] ?? '') }}"></div></div>
<label><input type="checkbox" name="confirmed" value="1" required> Conferi as partes, a viatura e o valor e confirmo que o vendedor é um particular.</label>
<p class="help-block">Modelo para revisão. A impressão não confirma pagamento nem receção de um documento assinado.</p>
<button class="btn btn-primary">Pré-visualizar impresso</button>
</div></div>
</form>
@endsection
@section('scripts')
@parent
<script>
(function () {
    document.getElementById('buyer_id').addEventListener('change', function () {
        document.getElementById('buyer_nif').value = '';
        document.getElementById('buyer_address').value = '';
    });
    let sellers = @json($sellers);
    const select = document.getElementById('seller_id');
    const feedback = document.getElementById('seller-feedback');
    select.addEventListener('change', function () {
        const seller = sellers.find(b => String(b.id) === select.value);
        ['name', 'nif', 'address'].forEach(key => document.getElementById('seller_' + key).value = seller ? (seller[key] || '') : '');
        feedback.textContent = seller ? 'Confira os dados do vendedor selecionado.' : '';
    });
    document.getElementById('refresh-sellers')?.addEventListener('click', async function () {
        this.disabled = true;
        try {
            const response = await fetch(@json(route('admin.purchase-form.sellers')), {headers: {'Accept':'application/json'}});
            if (!response.ok) throw new Error();
            sellers = await response.json();
            const selected = select.value;
            select.replaceChildren(new Option('Escolher vendedor', ''));
            sellers.forEach(b => select.add(new Option(b.name + (b.nif ? ' · ' + b.nif : ''), b.id)));
            select.value = selected;
            feedback.textContent = 'Lista atualizada. Pode escolher o novo vendedor.';
        } catch (_) { feedback.textContent = 'Não foi possível atualizar a lista. Tente novamente; os dados preenchidos mantêm-se.'; }
        finally { this.disabled = false; }
    });
})();
</script>
@endsection

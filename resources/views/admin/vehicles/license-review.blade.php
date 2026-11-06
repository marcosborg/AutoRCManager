@extends('layouts.admin')
@section('content')
<div class="panel panel-default">
    <div class="panel-heading">Conferir matrículas</div>
    <div class="panel-body">
        <p>Confira estes registos com os documentos da viatura. Um formato diferente não significa necessariamente que a matrícula está errada. Nenhum dado é alterado automaticamente.</p>
        @if(session('message'))<p class="alert alert-success">{{ session('message') }}</p>@endif
        <p>Se uma matrícula estrangeira foi escrita no campo normal, confirme-a no documento e use «Assinalar como estrangeira». A matrícula passa para o campo estrangeiro e sai do campo normal. O valor anterior fica no histórico. Não são feitas correções em lote.</p>
        <form method="GET" action="{{ route('admin.vehicles.licenses.review') }}" class="row">
            <div class="form-group col-md-3"><label for="reason">Mostrar</label><select id="reason" name="reason" class="form-control">
                @foreach(['review' => 'Por conferir', 'missing' => 'Sem matrícula', 'format' => 'Formato a conferir', 'foreign' => 'Com matrícula estrangeira'] as $value => $label)
                    <option value="{{ $value }}" @selected($reason === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="form-group col-md-3"><label for="scope">Viaturas</label><select id="scope" name="scope" class="form-control"><option value="all" @selected(($filters['scope'] ?? 'all') === 'all')>Todas</option><option value="stock" @selected(($filters['scope'] ?? 'all') === 'stock')>Em stock</option></select></div>
            <div class="form-group col-md-3"><label for="destination_company">Empresa de destino</label><select id="destination_company" name="destination_company" class="form-control"><option value="">Todas</option>
                @foreach(\App\Models\Vehicle::DESTINATION_COMPANIES + ['unassigned' => 'Por confirmar'] as $value => $label)<option value="{{ $value }}" @selected(($filters['destination_company'] ?? '') === $value)>{{ $label }}</option>@endforeach
            </select></div>
            <div class="form-group col-md-3"><label for="license">Pesquisar matrícula</label><input id="license" name="license" maxlength="100" class="form-control" value="{{ $filters['license'] ?? '' }}"></div>
            <div class="form-group col-md-12"><button class="btn btn-primary">Filtrar</button> <a class="btn btn-default" href="{{ route('admin.vehicles.index') }}">Voltar às viaturas</a></div>
        </form>
        @if($reason === 'foreign')<p class="alert alert-info">Estas viaturas têm uma matrícula estrangeira registada. Não são assinaladas como incorretas por não seguirem o formato português.</p>@endif
        <p>{{ $vehicles->total() }} registo(s) encontrado(s).</p>
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Viatura</th><th>Matrícula</th><th>Matrícula estrangeira</th><th>Empresa</th><th>Observação</th><th></th></tr></thead>
            <tbody>@forelse($vehicles as $vehicle)<tr>
                <td>#{{ $vehicle->id }} — {{ $vehicle->brand?->name }} {{ $vehicle->model }}</td>
                <td>{{ $vehicle->license ?: 'Por preencher' }}</td><td>{{ $vehicle->foreign_license ?: '—' }}</td>
                <td>{{ \App\Models\Vehicle::DESTINATION_COMPANIES[$vehicle->destination_company] ?? 'Por confirmar' }}</td>
                <td>{{ $reason === 'foreign' ? 'Matrícula estrangeira registada' : (trim((string) $vehicle->license) === '' ? 'Sem matrícula registada' : 'Conferir o formato no documento') }}</td>
                <td><a class="btn btn-default btn-sm" href="{{ route('admin.vehicles.edit', $vehicle) }}">Abrir ficha</a>
                    @if(trim((string) $vehicle->license) !== '' && ($reason !== 'foreign' || trim((string) $vehicle->foreign_license) === trim((string) $vehicle->license)))
                    <form method="POST" action="{{ route('admin.vehicles.licenses.foreign', $vehicle) }}" style="margin-top:8px">
                        @csrf
                        <input type="hidden" name="expected_license" value="{{ $vehicle->license }}">
                        <label style="font-weight:normal"><input type="checkbox" name="confirmed" value="1" required> Confirmei no documento que é estrangeira</label><br>
                        <button class="btn btn-default btn-sm">Assinalar como estrangeira</button>
                    </form>
                    @endif
                </td>
            </tr>@empty<tr><td colspan="6">Não existem registos para estes filtros.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $vehicles->links() }}
    </div>
</div>
@endsection

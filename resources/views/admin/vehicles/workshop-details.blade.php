@extends('layouts.admin')
@section('content')
<div class="content">
    <h1>{{ $data['license'] ?: $data['foreign_license'] }} · {{ $data['model'] }}</h1>
    <div class="panel panel-default"><div class="panel-heading">Situação e localização</div><div class="panel-body"><div class="row">
        @foreach(['general_state'=>'Estado geral','workshop_state'=>'Estado da oficina','location'=>'Localização','storage_location'=>'Local de parqueamento'] as $key=>$label)<div class="col-md-3"><strong>{{ $label }}</strong><p>{{ $context[$key] ?: 'Por definir' }}</p></div>@endforeach
    </div></div></div>
    <div class="panel panel-default"><div class="panel-heading">Informação geral da viatura</div><div class="panel-body">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.vehicles.update', $data['id']) }}">@csrf @method('PUT')
        <fieldset @disabled(!$editing)><div class="row">
        @foreach(['license'=>'Matrícula','foreign_license'=>'Matrícula estrangeira','model'=>'Modelo','version'=>'Versão','year'=>'Ano','month'=>'Mês','license_date'=>'Data da matrícula','color'=>'Cor','fuel'=>'Combustível','kilometers'=>'Quilómetros','engine_displacement'=>'Cilindrada','inspec_b'=>'Inspeção B','storage_location'=>'Local de parqueamento','key'=>'Chaves'] as $field=>$label)
        <div class="form-group col-md-3"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $data[$field] ?? '') }}"></div>
        @endforeach
        <div class="form-group col-md-3"><label for="brand_id">Marca</label><select class="form-control" id="brand_id" name="brand_id"><option value="">Por definir</option>@foreach($brands as $id=>$name)<option value="{{ $id }}" @selected((string)old('brand_id',$data['brand_id'])===(string)$id)>{{ $name }}</option>@endforeach</select></div>
        <div class="form-group col-md-3"><label for="transmission">Caixa</label><select id="transmission" name="transmission" class="form-control"><option value="">Por definir</option>@foreach(\App\Models\Vehicle::TRANSMISSION_SELECT as $key=>$label)<option value="{{ $key }}" @selected(old('transmission',$data['transmission'])===$key)>{{ $label }}</option>@endforeach</select></div>
        </div><h4>Acessórios</h4>
        @foreach(['vehicle_manuals'=>'Manuais','cables'=>'Cabo de carregamento','cables_2'=>'Segundo cabo'] as $field=>$label)<label style="margin-right:20px"><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field,$data[$field]))> {{ $label }}</label>@endforeach
        @if($editing)<p><button class="btn btn-primary">Gravar</button></p>@endif
        </fieldset>
    </form></div></div>
    @can('workshop_state_edit')
    <div class="panel panel-default"><div class="panel-heading">Estado da oficina</div><div class="panel-body"><form method="POST" action="{{ route('admin.vehicles.workshop-state.update',$data['id']) }}">@csrf @method('PATCH')<select name="workshop_state_id" class="form-control"><option value="">Por definir</option>@foreach($workshopStates as $id=>$name)<option value="{{ $id }}" @selected((string)$data['workshop_state_id']===(string)$id)>{{ $name }}</option>@endforeach</select><button class="btn btn-default">Guardar estado da oficina</button></form></div></div>
    @endcan
    @can('repair_access')<div class="panel panel-default"><div class="panel-heading">Reparações</div><div class="panel-body">
        @forelse($repairs as $repair)<p><a href="{{ route('admin.repairs.edit',$repair->id) }}">Reparação #{{ $repair->id }} — {{ $repair->work_type }}</a></p>@empty<p>Sem reparações registadas.</p>@endforelse
        <a class="btn btn-default" href="{{ route('admin.repairs.index') }}">Abrir oficina</a>
    </div></div>@endcan
</div>
@endsection

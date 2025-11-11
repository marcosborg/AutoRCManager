@extends('layouts.admin')

@section('content')
<div class="content">

    {{-- ======= Cabeçalho: Viatura ======= --}}
    <div class="panel panel-default">
        <div class="panel-heading">
            Viatura
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('general_state') ? 'has-error' : '' }}">
                        <label class="required" for="general_state_id">{{ trans('cruds.vehicle.fields.general_state') }}</label>
                        <select disabled class="form-control select2" name="general_state_id" id="general_state_id" required>
                            @foreach($general_states as $id => $entry)
                                <option value="{{ $id }}" {{ (old('general_state_id') ? old('general_state_id') : $vehicle->general_state->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                            @endforeach
                        </select>
                        @if($errors->has('general_state'))
                            <span class="help-block" role="alert">{{ $errors->first('general_state') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.general_state_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                        <label for="license">{{ trans('cruds.vehicle.fields.license') }}</label>
                        <input disabled class="form-control" type="text" name="license" id="license" value="{{ old('license', $vehicle->license) }}">
                        @if($errors->has('license'))
                            <span class="help-block" role="alert">{{ $errors->first('license') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.license_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('foreign_license') ? 'has-error' : '' }}">
                        <label for="foreign_license">{{ trans('cruds.vehicle.fields.foreign_license') }}</label>
                        <input disabled class="form-control" type="text" name="foreign_license" id="foreign_license" value="{{ old('foreign_license', $vehicle->foreign_license) }}">
                        @if($errors->has('foreign_license'))
                            <span class="help-block" role="alert">{{ $errors->first('foreign_license') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.foreign_license_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('brand') ? 'has-error' : '' }}">
                        <label for="brand_id">{{ trans('cruds.vehicle.fields.brand') }}</label>
                        <select disabled class="form-control select2" name="brand_id" id="brand_id">
                            @foreach($brands as $id => $entry)
                                <option value="{{ $id }}" {{ (old('brand_id') ? old('brand_id') : $vehicle->brand->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                            @endforeach
                        </select>
                        @if($errors->has('brand'))
                            <span class="help-block" role="alert">{{ $errors->first('brand') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.brand_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('model') ? 'has-error' : '' }}">
                        <label for="model">{{ trans('cruds.vehicle.fields.model') }}</label>
                        <input disabled class="form-control" type="text" name="model" id="model" value="{{ old('model', $vehicle->model) }}">
                        @if($errors->has('model'))
                            <span class="help-block" role="alert">{{ $errors->first('model') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.model_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('version') ? 'has-error' : '' }}">
                        <label for="version">{{ trans('cruds.vehicle.fields.version') }}</label>
                        <input disabled class="form-control" type="text" name="version" id="version" value="{{ old('version', $vehicle->version) }}">
                        @if($errors->has('version'))
                            <span class="help-block" role="alert">{{ $errors->first('version') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.version_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('transmission') ? 'has-error' : '' }}">
                        <label>{{ trans('cruds.vehicle.fields.transmission') }}</label>
                        <select disabled class="form-control" name="transmission" id="transmission">
                            <option value disabled {{ old('transmission', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                            @foreach(App\Models\Vehicle::TRANSMISSION_SELECT as $key => $label)
                                <option value="{{ $key }}" {{ old('transmission', $vehicle->transmission) === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($errors->has('transmission'))
                            <span class="help-block" role="alert">{{ $errors->first('transmission') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.transmission_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('year') ? 'has-error' : '' }}">
                        <label for="year">{{ trans('cruds.vehicle.fields.year') }}</label>
                        <input disabled class="form-control" type="number" name="year" id="year" value="{{ old('year', $vehicle->year) }}" step="1">
                        @if($errors->has('year'))
                            <span class="help-block" role="alert">{{ $errors->first('year') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.year_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('month') ? 'has-error' : '' }}">
                        <label for="month">{{ trans('cruds.vehicle.fields.month') }}</label>
                        <input disabled class="form-control" type="text" name="month" id="month" value="{{ old('month', $vehicle->month) }}">
                        @if($errors->has('month'))
                            <span class="help-block" role="alert">{{ $errors->first('month') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.month_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('license_date') ? 'has-error' : '' }}">
                        <label for="license_date">{{ trans('cruds.vehicle.fields.license_date') }}</label>
                        <input disabled class="form-control date" type="text" name="license_date" id="license_date" value="{{ old('license_date', $vehicle->license_date) }}">
                        @if($errors->has('license_date'))
                            <span class="help-block" role="alert">{{ $errors->first('license_date') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.license_date_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('color') ? 'has-error' : '' }}">
                        <label for="color">{{ trans('cruds.vehicle.fields.color') }}</label>
                        <input disabled class="form-control" type="text" name="color" id="color" value="{{ old('color', $vehicle->color) }}">
                        @if($errors->has('color'))
                            <span class="help-block" role="alert">{{ $errors->first('color') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.color_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('fuel') ? 'has-error' : '' }}">
                        <label for="fuel">{{ trans('cruds.vehicle.fields.fuel') }}</label>
                        <input disabled class="form-control" type="text" name="fuel" id="fuel" value="{{ old('fuel', $vehicle->fuel) }}">
                        @if($errors->has('fuel'))
                            <span class="help-block" role="alert">{{ $errors->first('fuel') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.fuel_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                        <label for="kilometers">{{ trans('cruds.vehicle.fields.kilometers') }}</label>
                        <input disabled class="form-control" type="number" name="kilometers" id="kilometers" value="{{ old('kilometers', $vehicle->kilometers) }}" step="1">
                        @if($errors->has('kilometers'))
                            <span class="help-block" role="alert">{{ $errors->first('kilometers') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.kilometers_helper') }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('inspec_b') ? 'has-error' : '' }}">
                        <label for="inspec_b">{{ trans('cruds.vehicle.fields.inspec_b') }}</label>
                        <input disabled class="form-control" type="text" name="inspec_b" id="inspec_b" value="{{ old('inspec_b', $vehicle->inspec_b) }}">
                        @if($errors->has('inspec_b'))
                            <span class="help-block" role="alert">{{ $errors->first('inspec_b') }}</span>
                        @endif
                        <span class="help-block">{{ trans('cruds.vehicle.fields.inspec_b_helper') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= Colunas principais ======= --}}
    <div class="row">

        {{-- Coluna Esquerda: Aquisição + Venda --}}
        <div class="col-md-4">

            {{-- Aquisição --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Aquisição</strong>
                </div>
                <div class="panel-body">
                    @php
                        $purchasePrice  = (float)($vehicle->purchase_price ?? 0);
                        $purchaseOps    = $operationsByDepartment['aquisition'] ?? collect();
                        $purchaseTotal  = (float)$purchaseOps->sum('total');
                        $purchaseBalance= $purchasePrice - $purchaseTotal;
                    @endphp

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span><strong>Preço de Compra</strong></span>
                        <span><strong>€{{ number_format($purchasePrice, 2, ',', '.') }}</strong></span>
                    </div>

                    <hr>

                    @forelse ($purchaseOps as $op)
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>{{ $op->account_item->name }}</span>
                            <span>€{{ number_format($op->total, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-muted">Nenhuma operação registada.</p>
                    @endforelse

                    <hr>

                    <div class="d-flex justify-content-between py-1">
                        <span><strong>Total Pago</strong></span>
                        <span><strong>€{{ number_format($purchaseTotal, 2, ',', '.') }}</strong></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span><strong>Saldo por pagar</strong></span>
                        <span><strong>€{{ number_format($purchaseBalance, 2, ',', '.') }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- Venda --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Venda</strong>
                </div>
                <div class="panel-body">
                    @php
                        $pvp            = (float)($vehicle->pvp ?? 0);
                        $sales_iuc      = (float)($vehicle->sales_iuc ?? 0);
                        $sales_tow      = (float)($vehicle->sales_tow ?? 0);
                        $sales_transfer = (float)($vehicle->sales_transfer ?? 0);
                        $sales_others   = (float)($vehicle->sales_others ?? 0);

                        $totalFinalVenda = isset($finalTotal)
                            ? (float)$finalTotal
                            : ($pvp + $sales_iuc + $sales_tow + $sales_transfer + $sales_others);

                        $saleOps    = $operationsByDepartment['sale'] ?? collect();
                        $saleTotal  = (float)$saleOps->sum('total');      // recebido
                        $saleBalance= $totalFinalVenda - $saleTotal;       // alvo - recebido
                    @endphp

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>PVP</span>
                        <span>€{{ number_format($pvp, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>IUC</span>
                        <span>€{{ number_format($sales_iuc, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Reboque (Tow)</span>
                        <span>€{{ number_format($sales_tow, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Transferência (Transfer)</span>
                        <span>€{{ number_format($sales_transfer, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Outros</span>
                        <span>€{{ number_format($sales_others, 2, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between py-2" style="border-top:1px dashed #ddd;">
                        <span><strong>Total final de venda</strong></span>
                        <span><strong>€{{ number_format($totalFinalVenda, 2, ',', '.') }}</strong></span>
                    </div>

                    <hr>

                    @forelse ($saleOps as $op)
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span>{{ $op->account_item->name }}</span>
                            <span>€{{ number_format($op->total, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-muted">Nenhum pagamento registado.</p>
                    @endforelse

                    <hr>

                    <div class="d-flex justify-content-between py-1">
                        <span><strong>Total Recebido</strong></span>
                        <span><strong>€{{ number_format($saleTotal, 2, ',', '.') }}</strong></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span><strong>Saldo por receber</strong></span>
                        <span><strong>€{{ number_format($saleBalance, 2, ',', '.') }}</strong></span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Coluna Direita: Oficina + Reconciliação --}}
        <div class="col-md-8">

            {{-- Oficina --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Oficina</strong>
                </div>
                <div class="panel-body">
                    <div class="row">

                        <div class="col-md-6">
                            @php
                                $garageOps   = $operationsByDepartment['garage'] ?? collect();
                                $garageTotal = (float)$garageOps->sum('total');
                            @endphp

                            @forelse ($garageOps as $op)
                                <div class="d-flex justify-content-between border-bottom py-1">
                                    <span>{{ $op->account_item->name }}</span>
                                    <span>€{{ number_format($op->total, 2, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-muted">Nenhuma operação registada.</p>
                            @endforelse

                            <hr>

                            <div class="d-flex justify-content-between py-1">
                                <span><strong>Total Oficina</strong></span>
                                <span><strong>€{{ number_format($garageTotal, 2, ',', '.') }}</strong></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            @php
                                $totalMinutes = (int)$timelogs->sum('rounded_minutes');
                                $hourPrice    = 25;
                                $totalHours   = $totalMinutes / 60;
                                $totalCost    = $totalHours * $hourPrice;
                            @endphp

                            <h5><strong>Registos de Mão de Obra</strong></h5>

                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Mecânico</th>
                                        <th>Início</th>
                                        <th>Fim</th>
                                        <th>Minutos</th>
                                        <th>Custo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($timelogs as $log)
                                        <tr>
                                            <td>{{ $log->user?->name ?? 'Desconhecido' }}</td>
                                            <td>{{ $log->start_time }}</td>
                                            <td>{{ $log->end_time }}</td>
                                            <td>{{ $log->rounded_minutes }}</td>
                                            <td>€{{ number_format(($log->rounded_minutes / 60) * $hourPrice, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3"><strong>Total</strong></td>
                                        <td><strong>{{ $totalMinutes }} min</strong></td>
                                        <td><strong>€{{ number_format($totalCost, 2, ',', '.') }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>

            {{-- Reconciliação --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Reconciliação</strong>
                </div>
                <div class="panel-body">
                    @php
                        // Garantir os valores usados abaixo (recalcula caso não estejam no escopo)
                        $purchaseOps    = $operationsByDepartment['aquisition'] ?? collect();
                        $purchaseTotal  = (float)$purchaseOps->sum('total');
                        $garageOps      = $operationsByDepartment['garage'] ?? collect();
                        $garageTotal    = (float)$garageOps->sum('total');
                        $saleOps        = $operationsByDepartment['sale'] ?? collect();
                        $saleTotal      = (float)$saleOps->sum('total');

                        $pvpR           = (float)($vehicle->pvp ?? 0);
                        $iucR           = (float)($vehicle->sales_iuc ?? 0);
                        $towR           = (float)($vehicle->sales_tow ?? 0);
                        $transferR      = (float)($vehicle->sales_transfer ?? 0);
                        $othersR        = (float)($vehicle->sales_others ?? 0);

                        $totalFinalVendaR = isset($finalTotal)
                            ? (float)$finalTotal
                            : ($pvpR + $iucR + $towR + $transferR + $othersR);

                        $investimentoTotal = $purchaseTotal + $garageTotal + $totalCost;

                        // Lógica de caixa (recebido - investimento)
                        $lucro = $saleTotal - $investimentoTotal;
                        $roi   = $investimentoTotal > 0 ? ($lucro / $investimentoTotal) * 100 : 0;
                    @endphp

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Investimento (Compra)</span>
                        <span>€{{ number_format($purchaseTotal, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Investimento (Oficina)</span>
                        <span>€{{ number_format($garageTotal, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Investimento (Mão de Obra)</span>
                        <span>€{{ number_format($totalCost, 2, ',', '.') }}</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Total Investido</span>
                        <span>€{{ number_format($investimentoTotal, 2, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Valor Final de Venda (alvo)</span>
                        <span>€{{ number_format($totalFinalVendaR, 2, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>Total Recebido</span>
                        <span>€{{ number_format($saleTotal, 2, ',', '.') }}</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span><strong>Lucro / Prejuízo (cash)</strong></span>
                        <span>
                            <strong class="{{ $lucro >= 0 ? 'text-success' : 'text-danger' }}">
                                €{{ number_format($lucro, 2, ',', '.') }}
                            </strong>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span><strong>ROI</strong></span>
                        <span><strong>{{ number_format($roi, 2, ',', '.') }}%</strong></span>
                    </div>


                    @php $lucroTeorico = $totalFinalVendaR - $investimentoTotal; @endphp
                    <div class="d-flex justify-content-between py-1">
                        <span><em>Lucro teórico (alvo - investimento)</em></span>
                        <span><em>€{{ number_format($lucroTeorico, 2, ',', '.') }}</em></span>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection

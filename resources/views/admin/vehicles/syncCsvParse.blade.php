@extends('layouts.admin')

@section('content')

<div class='row'>
    <div class='col-md-12'>
        <div class="panel panel-default">
            <div class="panel-heading">
                Sync CSV de viaturas
            </div>

            <div class="panel-body table-responsive">
                <form class="form-horizontal" method="POST" action="{{ route('admin.vehicles.syncCsv') }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="filename" value="{{ $filename }}" />
                    <input type="hidden" name="hasHeader" value="{{ $hasHeader }}" />

                    <div class="form-group">
                        <label for="general_state_id" class="col-md-2 control-label">General state (default)</label>
                        <div class="col-md-4">
                            <select id="general_state_id" class="form-control" name="general_state_id">
                                <option value="">Auto (primeiro)</option>
                                @foreach($general_states as $state)
                                    <option value="{{ $state->id }}" {{ (string) old('general_state_id', $general_state_id) === (string) $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <label for="delimiter" class="col-md-2 control-label">Delimiter</label>
                        <div class="col-md-2">
                            <input id="delimiter" type="text" class="form-control" name="delimiter" value="{{ old('delimiter', $delimiter) }}" placeholder="Auto">
                        </div>
                    </div>

                    @php
                        $fieldOptions = [
                            'license' => 'matricula/license',
                            'brand' => 'marca/brand',
                            'model' => 'modelo',
                            'general_state_id' => 'general_state_id',
                            'version' => 'versao',
                            'year' => 'ano',
                            'month' => 'mes',
                            'mes_iuc' => 'mes_iuc',
                            'fuel' => 'combustivel',
                            'color' => 'cor',
                            'kilometers' => 'kilometros',
                            'transmission' => 'transmission',
                            'foreign_license' => 'matricula_estrangeira',
                            'inspec_b' => 'inspec_b',
                            'purchase_price' => 'preco_custo',
                        ];
                    @endphp

                    <table class="table">
                        @if(isset($headers))
                            <tr>
                                @foreach($headers as $field)
                                    <th>{{ $field }}</th>
                                @endforeach
                            </tr>
                        @endif
                        @if($lines)
                            @foreach($lines as $line)
                                <tr>
                                    @foreach($line as $field)
                                        <td>{{ $field }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            @foreach($headers as $index => $header)
                                @php
                                    $selected = old('fields.' . $index, $suggestedByIndex[$index] ?? '');
                                @endphp
                                <td>
                                    <select name="fields[{{ $index }}]">
                                        <option value="">Selecionar</option>
                                        @foreach($fieldOptions as $value => $label)
                                            <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    </table>

                    <button type="submit" class="btn btn-primary">
                        Executar sync
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

<div class="panel panel-default" id="vehicle-send-to-workshop-panel">
    <div class="panel-heading">
        Envio para oficina
    </div>
    <div class="panel-body" style="padding: 10px;">
        @if($hasOpenRepairs ?? false)
            <div class="alert alert-warning" style="margin-bottom: 0;">
                Esta viatura ja tem uma intervencao aberta.
            </div>
        @else
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group {{ $errors->has('work_type') ? 'has-error' : '' }}">
                        <label for="workshop_work_type">Tipo de trabalho</label>
                        <select class="form-control" form="vehicle-send-to-workshop-form" name="work_type" id="workshop_work_type" required>
                            <option value="workshop" {{ old('work_type', 'workshop') === 'workshop' ? 'selected' : '' }}>Oficina</option>
                            <option value="painting" {{ old('work_type') === 'painting' ? 'selected' : '' }}>Pintura</option>
                        </select>
                        @if($errors->has('work_type'))<span class="help-block">{{ $errors->first('work_type') }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                        <label for="workshop_kilometers">Kms entrada</label>
                        <input class="form-control" form="vehicle-send-to-workshop-form" type="number" name="kilometers" id="workshop_kilometers" value="{{ old('kilometers', $vehicle->kilometers) }}" min="0" step="1">
                        @if($errors->has('kilometers'))<span class="help-block">{{ $errors->first('kilometers') }}</span>@endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group {{ $errors->has('fuel_level_in_percentage') ? 'has-error' : '' }}">
                        <label for="workshop_fuel_level_in_percentage">Combustivel entrada (%)</label>
                        <div class="workshop-fuel-slider">
                            <input form="vehicle-send-to-workshop-form" type="range" name="fuel_level_in_percentage" id="workshop_fuel_level_in_percentage" value="{{ old('fuel_level_in_percentage', 0) }}" min="0" max="100" step="1">
                            <span id="workshop_fuel_level_in_percentage_value">{{ old('fuel_level_in_percentage', 0) }}%</span>
                        </div>
                        @if($errors->has('fuel_level_in_percentage'))<span class="help-block">{{ $errors->first('fuel_level_in_percentage') }}</span>@endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group {{ $errors->has('expected_completion_date') ? 'has-error' : '' }}">
                        <label for="workshop_expected_completion_date">Data prevista de entrega</label>
                        <input class="form-control date" form="vehicle-send-to-workshop-form" type="text" name="expected_completion_date" id="workshop_expected_completion_date" value="{{ old('expected_completion_date') }}">
                        @if($errors->has('expected_completion_date'))<span class="help-block">{{ $errors->first('expected_completion_date') }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="form-group {{ $errors->has('obs_1') ? 'has-error' : '' }}">
                <label for="workshop_obs_1">Observacoes para oficina</label>
                <textarea class="form-control" form="vehicle-send-to-workshop-form" name="obs_1" id="workshop_obs_1" rows="3">{{ old('obs_1') }}</textarea>
                @if($errors->has('obs_1'))<span class="help-block">{{ $errors->first('obs_1') }}</span>@endif
            </div>
            <button class="btn btn-primary btn-sm" id="send-to-workshop-button" type="button">
                Enviar para oficina
            </button>
        @endif
    </div>
</div>

@section('scripts')
@parent
<script>
    $(function () {
        var $fuelSlider = $('#workshop_fuel_level_in_percentage');
        var $fuelValue = $('#workshop_fuel_level_in_percentage_value');

        $fuelSlider.on('input change', function () {
            $fuelValue.text($(this).val() + '%');
        });

        $('#send-to-workshop-button').on('click', function () {
            var form = document.getElementById('vehicle-send-to-workshop-form');
            if (!form) {
                return;
            }

            if (form.reportValidity && !form.reportValidity()) {
                return;
            }

            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
</script>
@endsection

@section('styles')
@parent
<style>
    .workshop-fuel-slider {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 34px;
    }

    .workshop-fuel-slider input[type="range"] {
        flex: 1;
        min-width: 0;
    }

    .workshop-fuel-slider span {
        width: 42px;
        text-align: right;
        font-weight: 600;
    }
</style>
@endsection

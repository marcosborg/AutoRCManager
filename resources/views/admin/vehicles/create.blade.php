@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.vehicle.title_singular') }}
                </div>
                <div class="panel-body">
                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <strong>Não foi possível criar a viatura.</strong>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="vehicle-create-form" method="POST" action="{{ route("admin.vehicles.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('general_state_id') ? 'has-error' : '' }}">
                                    <label class="required" for="general_state_id">{{ trans('cruds.vehicle.fields.general_state') }}</label>
                                    <select class="form-control select2" name="general_state_id" id="general_state_id" required>
                                        @foreach($general_states as $id => $entry)
                                        <option value="{{ $id }}" {{ old('general_state_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('general_state_id'))
                                    <span class="help-block" role="alert">{{ $errors->first('general_state_id') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.general_state_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                                    <label for="license">{{ trans('cruds.vehicle.fields.license') }}</label>
                                    <input class="form-control" type="text" name="license" id="license" value="{{ old('license', '') }}">
                                    @if($errors->has('license'))
                                        <span class="help-block" role="alert">{{ $errors->first('license') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.license_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('foreign_license') ? 'has-error' : '' }}">
                                    <label for="foreign_license">{{ trans('cruds.vehicle.fields.foreign_license') }}</label>
                                    <input class="form-control" type="text" name="foreign_license" id="foreign_license" value="{{ old('foreign_license', '') }}">
                                    @if($errors->has('foreign_license'))
                                        <span class="help-block" role="alert">{{ $errors->first('foreign_license') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.foreign_license_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('brand_id') ? 'has-error' : '' }}">
                                    <label for="brand_id">{{ trans('cruds.vehicle.fields.brand') }}</label>
                                    <select class="form-control select2" name="brand_id" id="brand_id">
                                        @foreach($brands as $id => $entry)
                                            <option value="{{ $id }}" {{ old('brand_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('brand_id'))
                                        <span class="help-block" role="alert">{{ $errors->first('brand_id') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.brand_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                @include('admin.vehicles.partials.purchasingCompanyField')
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('model') ? 'has-error' : '' }}">
                                    <label for="model">{{ trans('cruds.vehicle.fields.model') }}</label>
                                    <input class="form-control" type="text" name="model" id="model" value="{{ old('model', '') }}">
                                    @if($errors->has('model'))
                                        <span class="help-block" role="alert">{{ $errors->first('model') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.model_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('version') ? 'has-error' : '' }}">
                                    <label for="version">{{ trans('cruds.vehicle.fields.version') }}</label>
                                    <input class="form-control" type="text" name="version" id="version" value="{{ old('version', '') }}">
                                    @if($errors->has('version'))
                                        <span class="help-block" role="alert">{{ $errors->first('version') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.version_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('transmission') ? 'has-error' : '' }}">
                                    <label>{{ trans('cruds.vehicle.fields.transmission') }}</label>
                                    <select class="form-control" name="transmission" id="transmission">
                                        <option value disabled {{ old('transmission', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                                        @foreach(App\Models\Vehicle::TRANSMISSION_SELECT as $key => $label)
                                            <option value="{{ $key }}" {{ old('transmission', '') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
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
                                    <input class="form-control" type="number" name="year" id="year" value="{{ old('year', '') }}" step="1">
                                    @if($errors->has('year'))
                                        <span class="help-block" role="alert">{{ $errors->first('year') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.year_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('month') ? 'has-error' : '' }}">
                                    <label for="month">{{ trans('cruds.vehicle.fields.month') }}</label>
                                    <input class="form-control" type="text" name="month" id="month" value="{{ old('month', '') }}">
                                    @if($errors->has('month'))
                                        <span class="help-block" role="alert">{{ $errors->first('month') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.month_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('license_date') ? 'has-error' : '' }}">
                                    <label for="license_date">{{ trans('cruds.vehicle.fields.license_date') }}</label>
                                    <input class="form-control date" type="text" name="license_date" id="license_date" value="{{ old('license_date') }}">
                                    @if($errors->has('license_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('license_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.license_date_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('mes_iuc') ? 'has-error' : '' }}">
                                    <label class="{{ $iucMonthRequired ? 'required' : '' }}" for="mes_iuc">{{ trans('cruds.vehicle.fields.mes_iuc') }}</label>
                                    <select class="form-control" name="mes_iuc" id="mes_iuc" {{ $iucMonthRequired ? 'required' : '' }}>
                                        <option value></option>
                                        @foreach(['Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'] as $iucMonth)
                                            <option value="{{ $iucMonth }}" {{ old('mes_iuc') === $iucMonth ? 'selected' : '' }}>{{ $iucMonth }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('mes_iuc'))
                                        <span class="help-block" role="alert">{{ $errors->first('mes_iuc') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.mes_iuc_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('color') ? 'has-error' : '' }}">
                                    <label for="color">{{ trans('cruds.vehicle.fields.color') }}</label>
                                    <input class="form-control" type="text" name="color" id="color" value="{{ old('color', '') }}">
                                    @if($errors->has('color'))
                                        <span class="help-block" role="alert">{{ $errors->first('color') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.color_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('fuel') ? 'has-error' : '' }}">
                                    <label for="fuel">{{ trans('cruds.vehicle.fields.fuel') }}</label>
                                    <input class="form-control" type="text" name="fuel" id="fuel" value="{{ old('fuel', '') }}">
                                    @if($errors->has('fuel'))
                                        <span class="help-block" role="alert">{{ $errors->first('fuel') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.fuel_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('kilometers') ? 'has-error' : '' }}">
                                    <label for="kilometers">{{ trans('cruds.vehicle.fields.kilometers') }}</label>
                                    <input class="form-control" type="number" name="kilometers" id="kilometers" value="{{ old('kilometers', '') }}" step="1">
                                    @if($errors->has('kilometers'))
                                        <span class="help-block" role="alert">{{ $errors->first('kilometers') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.kilometers_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('inspec_b') ? 'has-error' : '' }}">
                                    <label for="inspec_b">{{ trans('cruds.vehicle.fields.inspec_b') }}</label>
                                    <input class="form-control" type="text" name="inspec_b" id="inspec_b" value="{{ old('inspec_b', '') }}">
                                    @if($errors->has('inspec_b'))
                                        <span class="help-block" role="alert">{{ $errors->first('inspec_b') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.inspec_b_helper') }}</span>
                                </div>
                            </div>
                        </div>
                        <fieldset>
                            <legend>Fotografias da viatura</legend>
                            <p class="help-block">Pode juntar fotografias já nesta entrada ou mais tarde, ao editar a viatura. Até 10 por grupo, 2 MB por fotografia e 6 MB no total. Formatos: JPG, PNG e GIF.</p>
                            @if($errors->any())
                                <p class="text-warning">Se tinha escolhido fotografias, selecione-as novamente antes de guardar.</p>
                            @endif
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="initial_photo_files">{{ trans('cruds.vehicle.fields.inicial') }}</label>
                                    <input type="file" id="initial_photo_files" name="initial_photo_files[]" accept="image/jpeg,image/png,image/gif" multiple aria-describedby="initial-photos-help">
                                    <p class="help-block" id="initial-photos-help">Registo do estado em que a viatura foi adquirida.</p>
                                    <div id="initial_photo_files-preview" class="row" aria-live="polite"></div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="vehicle_photo_files">Fotos atuais da viatura</label>
                                    <input type="file" id="vehicle_photo_files" name="vehicle_photo_files[]" accept="image/jpeg,image/png,image/gif" multiple aria-describedby="vehicle-photos-help">
                                    <p class="help-block" id="vehicle-photos-help">A primeira fotografia será a capa. Pode alterar a ordem na edição.</p>
                                    <div id="vehicle_photo_files-preview" class="row" aria-live="polite"></div>
                                </div>
                            </div>
                            <p id="photo-upload-error" class="text-danger" role="alert"></p>
                        </fieldset>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
    (function () {
        var inputs = [document.getElementById('initial_photo_files'), document.getElementById('vehicle_photo_files')];
        var error = document.getElementById('photo-upload-error');
        var previewUrls = {};

        function validatePhotos() {
            var totalSize = 0;
            var message = '';
            inputs.forEach(function (input) {
                if (input.files.length > 10) message = 'Escolha até 10 fotografias por grupo.';
                Array.from(input.files).forEach(function (file) {
                    totalSize += file.size;
                    if (file.size > 2 * 1024 * 1024) message = 'Cada fotografia pode ter no máximo 2 MB.';
                    if (!/\.(jpe?g|png|gif)$/i.test(file.name)) message = 'Escolha fotografias JPG, PNG ou GIF.';
                });
            });
            if (totalSize > 6 * 1024 * 1024) message = 'As fotografias não podem ultrapassar 6 MB no total.';
            error.textContent = message;
            return !message;
        }

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var preview = document.getElementById(input.id + '-preview');
                (previewUrls[input.id] || []).forEach(function (url) { URL.revokeObjectURL(url); });
                previewUrls[input.id] = [];
                preview.replaceChildren();
                Array.from(input.files).slice(0, 10).forEach(function (file) {
                    if (!/^image\/(jpeg|png|gif)$/.test(file.type) || file.size > 2 * 1024 * 1024) return;
                    var image = document.createElement('img');
                    image.src = URL.createObjectURL(file);
                    previewUrls[input.id].push(image.src);
                    image.alt = file.name;
                    image.width = 100;
                    image.height = 75;
                    image.style.objectFit = 'cover';
                    image.style.margin = '5px';
                    preview.appendChild(image);
                });
                validatePhotos();
            });
        });
        document.getElementById('vehicle-create-form').addEventListener('submit', function (event) {
            if (!validatePhotos()) {
                event.preventDefault();
                error.scrollIntoView({ block: 'center' });
            }
        });
    })();
</script>
@endsection

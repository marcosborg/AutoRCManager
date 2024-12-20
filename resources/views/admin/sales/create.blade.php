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
                    <form method="POST" action="{{ route("admin.vehicles.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
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
                                <div class="form-group {{ $errors->has('brand') ? 'has-error' : '' }}">
                                    <label for="brand_id">{{ trans('cruds.vehicle.fields.brand') }}</label>
                                    <select class="form-control select2" name="brand_id" id="brand_id">
                                        @foreach($brands as $id => $entry)
                                            <option value="{{ $id }}" {{ old('brand_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
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
                                <div class="form-group {{ $errors->has('vehicle_identification_number_vin') ? 'has-error' : '' }}">
                                    <label for="vehicle_identification_number_vin">{{ trans('cruds.vehicle.fields.vehicle_identification_number_vin') }}</label>
                                    <input class="form-control" type="text" name="vehicle_identification_number_vin" id="vehicle_identification_number_vin" value="{{ old('vehicle_identification_number_vin', '') }}">
                                    @if($errors->has('vehicle_identification_number_vin'))
                                        <span class="help-block" role="alert">{{ $errors->first('vehicle_identification_number_vin') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.vehicle_identification_number_vin_helper') }}</span>
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
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('seller_client') ? 'has-error' : '' }}">
                                    <label for="seller_client_id">{{ trans('cruds.vehicle.fields.seller_client') }}</label>
                                    <select class="form-control select2" name="seller_client_id" id="seller_client_id">
                                        @foreach($seller_clients as $id => $entry)
                                            <option value="{{ $id }}" {{ old('seller_client_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('seller_client'))
                                        <span class="help-block" role="alert">{{ $errors->first('seller_client') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.seller_client_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('buyer_client') ? 'has-error' : '' }}">
                                    <label for="buyer_client_id">{{ trans('cruds.vehicle.fields.buyer_client') }}</label>
                                    <select class="form-control select2" name="buyer_client_id" id="buyer_client_id">
                                        @foreach($buyer_clients as $id => $entry)
                                            <option value="{{ $id }}" {{ old('buyer_client_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('buyer_client'))
                                        <span class="help-block" role="alert">{{ $errors->first('buyer_client') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.buyer_client_helper') }}</span>
                                </div>
                            </div>
                        </div>
                        <h4>Preparação e venda da viatura</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('total_price') ? 'has-error' : '' }}">
                                    <label for="total_price">{{ trans('cruds.vehicle.fields.total_price') }}</label>
                                    <input class="form-control" type="number" name="total_price" id="total_price" value="{{ old('total_price', '') }}" step="0.01">
                                    @if($errors->has('total_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('total_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.total_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('minimum_price') ? 'has-error' : '' }}">
                                    <label for="minimum_price">{{ trans('cruds.vehicle.fields.minimum_price') }}</label>
                                    <input class="form-control" type="number" name="minimum_price" id="minimum_price" value="{{ old('minimum_price', '') }}" step="0.01">
                                    @if($errors->has('minimum_price'))
                                        <span class="help-block" role="alert">{{ $errors->first('minimum_price') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.minimum_price_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('pvp') ? 'has-error' : '' }}">
                                    <label for="pvp">{{ trans('cruds.vehicle.fields.pvp') }}</label>
                                    <input class="form-control" type="number" name="pvp" id="pvp" value="{{ old('pvp', '') }}" step="0.01">
                                    @if($errors->has('pvp'))
                                        <span class="help-block" role="alert">{{ $errors->first('pvp') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.pvp_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('client') ? 'has-error' : '' }}">
                                    <label for="client_id">{{ trans('cruds.vehicle.fields.client') }}</label>
                                    <select class="form-control select2" name="client_id" id="client_id">
                                        @foreach($clients as $id => $entry)
                                            <option value="{{ $id }}" {{ old('client_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('client'))
                                        <span class="help-block" role="alert">{{ $errors->first('client') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.client_helper') }}</span>
                                </div>
                                <label>Novo pagamento</label>
                                <input type="date" id="client_invoice_date" class="form-control" style="margin-bottom: 5px;">
                                <input type="number" id="client_invoice_amount" class="form-control" style="margin-bottom: 5px;">
                                <input type="text" id="client_invoice_obs" class="form-control" style="margin-bottom: 5px;">
                                <button type="button" class="btn btn-success btn-sm" onclick="newClientPayment()">Save</button>
                                <input type="hidden" class="form-control" name="amount_paid" id="client_amount_paid" value="{{ old('client_amount_paid') }}">
                            </div>
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('client_registration') ? 'has-error' : '' }}">
                                    <label for="client_registration">{{ trans('cruds.vehicle.fields.client_registration') }}</label>
                                    <input class="form-control" type="text" name="client_registration" id="client_registration" value="{{ old('client_registration', '') }}">
                                    @if($errors->has('client_registration'))
                                        <span class="help-block" role="alert">{{ $errors->first('client_registration') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.client_registration_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('chekin_documents') ? 'has-error' : '' }}">
                                    <label for="chekin_documents">{{ trans('cruds.vehicle.fields.chekin_documents') }}</label>
                                    <input class="form-control" type="text" name="chekin_documents" id="chekin_documents" value="{{ old('chekin_documents', '') }}">
                                    @if($errors->has('chekin_documents'))
                                        <span class="help-block" role="alert">{{ $errors->first('chekin_documents') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.chekin_documents_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('chekin_date') ? 'has-error' : '' }}">
                                    <label for="chekin_date">{{ trans('cruds.vehicle.fields.chekin_date') }}</label>
                                    <input class="form-control date" type="text" name="chekin_date" id="chekin_date" value="{{ old('chekin_date') }}">
                                    @if($errors->has('chekin_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('chekin_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.chekin_date_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sale_date') ? 'has-error' : '' }}">
                                    <label for="sale_date">{{ trans('cruds.vehicle.fields.sale_date') }}</label>
                                    <input class="form-control date" type="text" name="sale_date" id="sale_date" value="{{ old('sale_date') }}">
                                    @if($errors->has('sale_date'))
                                        <span class="help-block" role="alert">{{ $errors->first('sale_date') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sale_date_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sele_chekout') ? 'has-error' : '' }}">
                                    <label for="sele_chekout">{{ trans('cruds.vehicle.fields.sele_chekout') }}</label>
                                    <input class="form-control date" type="text" name="sele_chekout" id="sele_chekout" value="{{ old('sele_chekout') }}">
                                    @if($errors->has('sele_chekout'))
                                        <span class="help-block" role="alert">{{ $errors->first('sele_chekout') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sele_chekout_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('first_key') ? 'has-error' : '' }}">
                                    <label for="first_key">{{ trans('cruds.vehicle.fields.first_key') }}</label>
                                    <input class="form-control" type="text" name="first_key" id="first_key" value="{{ old('first_key', '') }}">
                                    @if($errors->has('first_key'))
                                        <span class="help-block" role="alert">{{ $errors->first('first_key') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.first_key_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('scuts') ? 'has-error' : '' }}">
                                    <label for="scuts">{{ trans('cruds.vehicle.fields.scuts') }}</label>
                                    <input class="form-control" type="text" name="scuts" id="scuts" value="{{ old('scuts', '') }}">
                                    @if($errors->has('scuts'))
                                        <span class="help-block" role="alert">{{ $errors->first('scuts') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.scuts_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('key') ? 'has-error' : '' }}">
                                    <label for="key">{{ trans('cruds.vehicle.fields.key') }}</label>
                                    <input class="form-control" type="text" name="key" id="key" value="{{ old('key', '') }}">
                                    @if($errors->has('key'))
                                        <span class="help-block" role="alert">{{ $errors->first('key') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.key_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('manuals') ? 'has-error' : '' }}">
                                    <label for="manuals">{{ trans('cruds.vehicle.fields.manuals') }}</label>
                                    <input class="form-control" type="text" name="manuals" id="manuals" value="{{ old('manuals', '') }}">
                                    @if($errors->has('manuals'))
                                        <span class="help-block" role="alert">{{ $errors->first('manuals') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.manuals_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('elements_with_vehicle') ? 'has-error' : '' }}">
                                    <label for="elements_with_vehicle">{{ trans('cruds.vehicle.fields.elements_with_vehicle') }}</label>
                                    <input class="form-control" type="text" name="elements_with_vehicle" id="elements_with_vehicle" value="{{ old('elements_with_vehicle', '') }}">
                                    @if($errors->has('elements_with_vehicle'))
                                        <span class="help-block" role="alert">{{ $errors->first('elements_with_vehicle') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.elements_with_vehicle_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('local') ? 'has-error' : '' }}">
                                    <label for="local">{{ trans('cruds.vehicle.fields.local') }}</label>
                                    <input class="form-control" type="text" name="local" id="local" value="{{ old('local', '') }}">
                                    @if($errors->has('local'))
                                        <span class="help-block" role="alert">{{ $errors->first('local') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.local_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('photos') ? 'has-error' : '' }}">
                                    <label for="photos">{{ trans('cruds.vehicle.fields.photos') }}</label>
                                    <div class="needsclick dropzone" id="photos-dropzone">
                                    </div>
                                    @if($errors->has('photos'))
                                        <span class="help-block" role="alert">{{ $errors->first('photos') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.photos_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('sale_notes') ? 'has-error' : '' }}">
                                    <label for="sale_notes">{{ trans('cruds.vehicle.fields.sale_notes') }}</label>
                                    <textarea class="form-control" name="sale_notes" id="sale_notes">{{ old('sale_notes') }}</textarea>
                                    @if($errors->has('sale_notes'))
                                        <span class="help-block" role="alert">{{ $errors->first('sale_notes') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.vehicle.fields.sale_notes_helper') }}</span>
                                </div>
                            </div>
                        </div>
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
<script>
    var uploadedDocumentsMap = {}
Dropzone.options.documentsDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 2000, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2000
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="documents[]" value="' + response.name + '">')
      uploadedDocumentsMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedDocumentsMap[file.name]
      }
      $('form').find('input[name="documents[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($vehicle) && $vehicle->documents)
          var files =
            {!! json_encode($vehicle->documents) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="documents[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
<script>
    $(document).ready(function () {
  function SimpleUploadAdapter(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
      return {
        upload: function() {
          return loader.file
            .then(function (file) {
              return new Promise(function(resolve, reject) {
                // Init request
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route('admin.vehicles.storeCKEditorImages') }}', true);
                xhr.setRequestHeader('x-csrf-token', window._token);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.responseType = 'json';

                // Init listeners
                var genericErrorText = `Couldn't upload file: ${ file.name }.`;
                xhr.addEventListener('error', function() { reject(genericErrorText) });
                xhr.addEventListener('abort', function() { reject() });
                xhr.addEventListener('load', function() {
                  var response = xhr.response;

                  if (!response || xhr.status !== 201) {
                    return reject(response && response.message ? `${genericErrorText}\n${xhr.status} ${response.message}` : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
                  }

                  $('form').append('<input type="hidden" name="ck-media[]" value="' + response.id + '">');

                  resolve({ default: response.url });
                });

                if (xhr.upload) {
                  xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                      loader.uploadTotal = e.total;
                      loader.uploaded = e.loaded;
                    }
                  });
                }

                // Send request
                var data = new FormData();
                data.append('upload', file);
                data.append('crud_id', '{{ $vehicle->id ?? 0 }}');
                xhr.send(data);
              });
            })
        }
      };
    }
  }

  var allEditors = document.querySelectorAll('.ckeditor');
  for (var i = 0; i < allEditors.length; ++i) {
    ClassicEditor.create(
      allEditors[i], {
        extraPlugins: [SimpleUploadAdapter]
      }
    );
  }
});
</script>

<script>
    var uploadedPhotosMap = {}
Dropzone.options.photosDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 20, // MB
    acceptedFiles: '.jpeg,.jpg,.png,.gif',
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 20,
      width: 4096,
      height: 4096
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="photos[]" value="' + response.name + '">')
      uploadedPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      console.log(file)
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedPhotosMap[file.name]
      }
      $('form').find('input[name="photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($vehicle) && $vehicle->photos)
      var files = {!! json_encode($vehicle->photos) !!}
          for (var i in files) {
          var file = files[i]
          this.options.addedfile.call(this, file)
          this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
          file.previewElement.classList.add('dz-complete')
          $('form').append('<input type="hidden" name="photos[]" value="' + file.file_name + '">')
        }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}

</script>
<script>
    var uploadedInvoiceMap = {}
Dropzone.options.invoiceDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 20, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 20
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="invoice[]" value="' + response.name + '">')
      uploadedInvoiceMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedInvoiceMap[file.name]
      }
      $('form').find('input[name="invoice[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($vehicle) && $vehicle->invoice)
          var files =
            {!! json_encode($vehicle->invoice) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="invoice[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
<script>
    var uploadedInicialMap = {}
Dropzone.options.inicialDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 2000, // MB
    acceptedFiles: '.jpeg,.jpg,.png,.gif',
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2000,
      width: 4096,
      height: 4096
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="inicial[]" value="' + response.name + '">')
      uploadedInicialMap[file.name] = response.name
    },
    removedfile: function (file) {
      console.log(file)
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedInicialMap[file.name]
      }
      $('form').find('input[name="inicial[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($vehicle) && $vehicle->inicial)
      var files = {!! json_encode($vehicle->inicial) !!}
          for (var i in files) {
          var file = files[i]
          this.options.addedfile.call(this, file)
          this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
          file.previewElement.classList.add('dz-complete')
          $('form').append('<input type="hidden" name="inicial[]" value="' + file.file_name + '">')
        }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}

</script>
<script>
    var uploadedWithdrawalAuthorizationFileMap = {}
Dropzone.options.withdrawalAuthorizationFileDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 2000, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2000
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + response.name + '">')
      uploadedWithdrawalAuthorizationFileMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedWithdrawalAuthorizationFileMap[file.name]
      }
      $('form').find('input[name="withdrawal_authorization_file[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($vehicle) && $vehicle->withdrawal_authorization_file)
          var files =
            {!! json_encode($vehicle->withdrawal_authorization_file) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
<script>
    var uploadedWithdrawalDocumentsMap = {}
Dropzone.options.withdrawalDocumentsDropzone = {
    url: '{{ route('admin.vehicles.storeMedia') }}',
    maxFilesize: 2000, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2000
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="withdrawal_documents[]" value="' + response.name + '">')
      uploadedWithdrawalDocumentsMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedWithdrawalDocumentsMap[file.name]
      }
      $('form').find('input[name="withdrawal_documents[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($vehicle) && $vehicle->withdrawal_documents)
          var files =
            {!! json_encode($vehicle->withdrawal_documents) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="withdrawal_documents[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
<script>
    $(() => {
        let amount_paid = $('#amount_paid').val();
        if (amount_paid) {
        try {
            amount_paid = JSON.parse(amount_paid);
            if (Array.isArray(amount_paid)) {
                updatePaymentsList(amount_paid);
            }
        } catch (error) {
            console.error("Erro ao parsear amount_paid ao carregar a página:", error);
        }
    }
    })

    newPayment = () => {
        let amount_paid = $('#amount_paid').val();
        let invoice_date = $('#invoice_date').val();
        let invoice_amount = $('#invoice_amount').val();
        let invoice_obs = $('#invoice_obs').val();
        if (!invoice_date || !invoice_amount) {
            Swal.fire({
                text: "A data e o valor são obrigatórios",
                icon: "error"
            });
        } else {
            let data = {
                invoice_date: invoice_date,
                invoice_amount: invoice_amount,
                invoice_obs: invoice_obs
            };
            // Verifica e trata o valor de amount_paid
            if (amount_paid) {
                try {
                    // Converte para array, assumindo que seja uma string JSON válida
                    amount_paid = JSON.parse(amount_paid);
                    if (!Array.isArray(amount_paid)) {
                        console.warn("amount_paid não é um array válido. Inicializando como array vazio.");
                        amount_paid = [];
                    }
                } catch (error) {
                    console.error("Erro ao parsear amount_paid:", error);
                    amount_paid = [];
                }
            } else {
                // Se o valor do input estiver vazio, inicializa como array vazio
                amount_paid = [];
            }

            // Adiciona o novo dado ao array
            amount_paid.push(data);

            // Atualiza o valor do input para refletir o array atualizado
            $('#amount_paid').val(JSON.stringify(amount_paid));

            updatePaymentsList(amount_paid);

            $('#invoice_date').val('');
            $('#invoice_amount').val('');
            $('#invoice_obs').val('');

            console.log(amount_paid); // Verifica o valor atualizado no console
            
        }
        
    }

    function updatePaymentsList(payments) {
    let paymentsList = `
        <div>
            <label>Payments</label>
            <ul class="list-group">
    `;

    payments.forEach((payment, index) => {
        paymentsList += `
            <li class="list-group-item">
                ${parseFloat(payment.invoice_amount).toFixed(2)}€ em ${payment.invoice_date}.
                ${payment.invoice_obs ? "(" + payment.invoice_obs + ")" : ""}
            </li>
        `;
    });

    paymentsList += `
            </ul>
        </div>
    `;

    // Atualiza o HTML no elemento com id `payments_container`
    $('#payments_container').html(paymentsList);
}
</script>

<script>
    $(() => {
        let client_amount_paid = $('#client_amount_paid').val();
        if (client_amount_paid) {
        try {
            client_amount_paid = JSON.parse(client_amount_paid);
            if (Array.isArray(client_amount_paid)) {
                updateClientPaymentsList(client_amount_paid);
            }
        } catch (error) {
            console.error("Erro ao parsear client_amount_paid ao carregar a página:", error);
        }
    }
    })

    newClientPayment = () => {
        let client_amount_paid = $('#client_amount_paid').val();
        let client_invoice_date = $('#client_invoice_date').val();
        let client_invoice_amount = $('#client_invoice_amount').val();
        let client_invoice_obs = $('#client_invoice_obs').val();
        if (!client_invoice_date || !client_invoice_amount) {
            Swal.fire({
                text: "A data e o valor são obrigatórios",
                icon: "error"
            });
        } else {
            let data = {
                client_invoice_date: client_invoice_date,
                client_invoice_amount: client_invoice_amount,
                client_invoice_obs: client_invoice_obs
            };
            // Verifica e trata o valor de amount_paid
            if (client_amount_paid) {
                try {
                    // Converte para array, assumindo que seja uma string JSON válida
                    client_amount_paid = JSON.parse(client_amount_paid);
                    if (!Array.isArray(client_amount_paid)) {
                        console.warn("client_amount_paid não é um array válido. Inicializando como array vazio.");
                        client_amount_paid = [];
                    }
                } catch (error) {
                    console.error("Erro ao parsear client_amount_paid:", error);
                    client_amount_paid = [];
                }
            } else {
                // Se o valor do input estiver vazio, inicializa como array vazio
                client_amount_paid = [];
            }

            // Adiciona o novo dado ao array
            client_amount_paid.push(data);

            // Atualiza o valor do input para refletir o array atualizado
            $('#client_amount_paid').val(JSON.stringify(client_amount_paid));

            updateClientPaymentsList(client_amount_paid);

            $('#client_invoice_date').val('');
            $('#client_invoice_amount').val('');
            $('#client_invoice_obs').val('');

            console.log(client_amount_paid); // Verifica o valor atualizado no console
            
        }
        
    }

    function updateClientPaymentsList(payments) {
    let paymentsList = `
        <div class="col-md-8">
            <label>Payments</label>
            <ul class="list-group">
    `;

    payments.forEach((payment, index) => {
        paymentsList += `
            <li class="list-group-item">
                ${parseFloat(payment.invoice_amount).toFixed(2)}€ em ${payment.invoice_date}.
                ${payment.invoice_obs ? "(" + payment.invoice_obs + ")" : ""}
            </li>
        `;
    });

    paymentsList += `
            </ul>
        </div>
    `;

    // Atualiza o HTML no elemento com id `payments_container`
    $('#payments_container').html(paymentsList);
}
</script>
@endsection
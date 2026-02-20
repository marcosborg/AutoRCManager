@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.vehicle.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.vehicles.update', [$vehicle->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group {{ $errors->has('general_state') ? 'has-error' : '' }}">
                                    <label class="required" for="general_state_id">{{ trans('cruds.vehicle.fields.general_state') }}</label>
                                    <select class="form-control select2" name="general_state_id" id="general_state_id" required>
                                        @foreach($general_states as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('general_state_id') ? old('general_state_id') : $vehicle->general_state->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('general_state'))
                                    <span class="help-block" role="alert">{{ $errors->first('general_state') }}</span>
                                    @endif

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
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 2000 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="documents[]" value="' + response.name + '">')
            uploadedDocumentsMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedDocumentsMap[file.name]
            $('form').find('input[name="documents[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->documents)
                var files = {!! json_encode($vehicle->documents) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
                    $('form').append('<input type="hidden" name="documents[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    $(document).ready(function () {
        function SimpleUploadAdapter(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
                return {
                    upload: function() {
                        return loader.file.then(function (file) {
                            return new Promise(function(resolve, reject) {
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', '{{ route('admin.vehicles.storeCKEditorImages') }}', true);
                                xhr.setRequestHeader('x-csrf-token', window._token);
                                xhr.setRequestHeader('Accept', 'application/json');
                                xhr.responseType = 'json';

                                var genericErrorText = `Couldn't upload file: ${ file.name }.`;
                                xhr.addEventListener('error', function() { reject(genericErrorText) });
                                xhr.addEventListener('abort', function() { reject() });
                                xhr.addEventListener('load', function() {
                                    var response = xhr.response;
                                    if (!response || xhr.status !== 201) {
                                        return reject(response && response.message
                                            ? `${genericErrorText}\n${xhr.status} ${response.message}`
                                            : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
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
            ClassicEditor.create(allEditors[i], { extraPlugins: [SimpleUploadAdapter] });
        }
    });
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
    var uploadedPhotosMap = {}
    Dropzone.options.photosDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 20,
        acceptedFiles: '.jpeg,.jpg,.png,.gif',
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 20, width: 4096, height: 4096 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="photos[]" value="' + response.name + '">')
            uploadedPhotosMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedPhotosMap[file.name]
            $('form').find('input[name="photos[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->photos)
                var files = {!! json_encode($vehicle->photos) !!}
                for (var i in files) {
                    var file = files[i];
                    this.options.addedfile.call(this, file)
                    this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
                    file.previewElement.classList.add('dz-complete')
                    $('form').append('<input type="hidden" name="photos[]" value="' + file.file_name + '">')
                    const img = file.previewElement.querySelector("img");
                    if (img) {
                        img.style.cursor = "pointer";
                        const a = document.createElement('a');
                        a.href = file.original_url;
                        a.setAttribute('data-lightbox', 'gallery');
                        img.parentNode.insertBefore(a, img);
                        a.appendChild(img);
                    }
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedInvoiceMap = {}
    Dropzone.options.invoiceDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 20,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 20 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="invoice[]" value="' + response.name + '">')
            uploadedInvoiceMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedInvoiceMap[file.name]
            $('form').find('input[name="invoice[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->invoice)
                var files = {!! json_encode($vehicle->invoice) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
                    $('form').append('<input type="hidden" name="invoice[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedPdfsMap = {}
    Dropzone.options.pdfsDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 20,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 20 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="pdfs[]" value="' + response.name + '">')
            uploadedPdfsMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedPdfsMap[file.name]
            $('form').find('input[name="pdfs[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->pdfs)
                var files = {!! json_encode($vehicle->pdfs) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
                    $('form').append('<input type="hidden" name="pdfs[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedInicialMap = {}
    Dropzone.options.inicialDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 2000,
        acceptedFiles: '.jpeg,.jpg,.png,.gif',
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 2000, width: 4096, height: 4096 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="inicial[]" value="' + response.name + '">')
            uploadedInicialMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedInicialMap[file.name]
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
                    const img = file.previewElement.querySelector("img");
                    if (img) {
                        img.style.cursor = "pointer";
                        const a = document.createElement('a');
                        a.href = file.original_url;
                        a.setAttribute('data-lightbox', 'gallery');
                        img.parentNode.insertBefore(a, img);
                        a.appendChild(img);
                    }
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedWithdrawalAuthorizationFileMap = {}
    Dropzone.options.withdrawalAuthorizationFileDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 2000,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 2000 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + response.name + '">')
            uploadedWithdrawalAuthorizationFileMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedWithdrawalAuthorizationFileMap[file.name]
            $('form').find('input[name="withdrawal_authorization_file[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->withdrawal_authorization_file)
                var files = {!! json_encode($vehicle->withdrawal_authorization_file) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
                    $('form').append('<input type="hidden" name="withdrawal_authorization_file[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedWithdrawalDocumentsMap = {}
    Dropzone.options.withdrawalDocumentsDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 2000,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 2000 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="withdrawal_documents[]" value="' + response.name + '">')
            uploadedWithdrawalDocumentsMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedWithdrawalDocumentsMap[file.name]
            $('form').find('input[name="withdrawal_documents[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->withdrawal_documents)
                var files = {!! json_encode($vehicle->withdrawal_documents) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
                    $('form').append('<input type="hidden" name="withdrawal_documents[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

<script>
    var uploadedPaymentComprovantMap = {}
    Dropzone.options.paymentComprovantDropzone = {
        url: '{{ route('admin.vehicles.storeMedia') }}',
        maxFilesize: 5,
        addRemoveLinks: true,
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
        params: { size: 5 },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="payment_comprovant[]" value="' + response.name + '">')
            uploadedPaymentComprovantMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = (typeof file.file_name !== 'undefined') ? file.file_name : uploadedPaymentComprovantMap[file.name]
            $('form').find('input[name="payment_comprovant[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($vehicle) && $vehicle->payment_comprovant)
                var files = {!! json_encode($vehicle->payment_comprovant) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    file.previewElement.onclick = function () { window.open(file.original_url, '_blank'); };
                    $('form').append('<input type="hidden" name="payment_comprovant[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            var nodes = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            for (var i = 0; i < nodes.length; i++) { nodes[i].textContent = message }
        }
    }
</script>

@endsection





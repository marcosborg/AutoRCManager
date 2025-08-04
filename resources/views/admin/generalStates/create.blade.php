@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.generalState.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.general-states.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.generalState.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.generalState.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('notification') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="notification" value="0">
                                <input type="checkbox" name="notification" id="notification" value="1" {{ old('notification', 0) == 1 ? 'checked' : '' }}>
                                <label for="notification" style="font-weight: 400">{{ trans('cruds.generalState.fields.notification') }}</label>
                            </div>
                            @if($errors->has('notification'))
                                <span class="help-block" role="alert">{{ $errors->first('notification') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.generalState.fields.notification_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('emails') ? 'has-error' : '' }}">
                            <label for="emails">{{ trans('cruds.generalState.fields.emails') }}</label>
                            <input class="form-control" type="text" name="emails" id="emails" value="{{ old('emails', '') }}">
                            @if($errors->has('emails'))
                                <span class="help-block" role="alert">{{ $errors->first('emails') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.generalState.fields.emails_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                            <label for="message">{{ trans('cruds.generalState.fields.message') }}</label>
                            <textarea class="form-control ckeditor" name="message" id="message">{!! old('message') !!}</textarea>
                            @if($errors->has('message'))
                                <span class="help-block" role="alert">{{ $errors->first('message') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.generalState.fields.message_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('position') ? 'has-error' : '' }}">
                            <label for="position">{{ trans('cruds.generalState.fields.position') }}</label>
                            <input class="form-control" type="number" name="position" id="position" value="{{ old('position', '') }}" step="1">
                            @if($errors->has('position'))
                                <span class="help-block" role="alert">{{ $errors->first('position') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.generalState.fields.position_helper') }}</span>
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
                xhr.open('POST', '{{ route('admin.general-states.storeCKEditorImages') }}', true);
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
                data.append('crud_id', '{{ $generalState->id ?? 0 }}');
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

@endsection
<script>
    var VehiclePhotoUploads = (function () {
        var zones = [];

        function errorMessage(response) {
            if (response && response.errors && response.errors.file) {
                return [].concat(response.errors.file).join(' ');
            }
            // Avoid showing HTML error pages or server diagnostics in the photo preview.
            return typeof response === 'string' && response.indexOf('<') === -1
                ? response : 'Não foi possível enviar a fotografia. Tente novamente.';
        }

        function attach(zone) {
            zones.push(zone);
            zone.on('error', function (file, response, xhr) {
                if (!file.previewElement || file.retryPhotoButton) return;
                if (!file.accepted || (xhr && [401, 403, 413, 419, 422].indexOf(xhr.status) !== -1)) return;
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-warning btn-xs dz-photo-retry';
                button.textContent = 'Tentar novamente';
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (file.status !== Dropzone.ERROR) return;
                    button.remove();
                    file.retryPhotoButton = null;
                    file.previewElement.classList.remove('dz-error', 'dz-complete');
                    file.status = Dropzone.ADDED;
                    zone.enqueueFile(file);
                });
                file.retryPhotoButton = button;
                file.previewElement.appendChild(button);
            });
            zone.on('success', function (file) {
                if (file.retryPhotoButton) file.retryPhotoButton.remove();
                file.retryPhotoButton = null;
                file.previewElement.classList.remove('dz-error');
            });
        }

        function blockReason() {
            var files = zones.reduce(function (all, zone) { return all.concat(zone.files); }, []);
            if (files.some(function (file) {
                return [Dropzone.ADDED, Dropzone.QUEUED, Dropzone.UPLOADING].indexOf(file.status) !== -1;
            })) return 'Aguarde pelo fim do envio das fotografias antes de gravar.';
            if (files.some(function (file) { return file.status === Dropzone.ERROR; })) {
                return 'Existem fotografias por enviar. Tente novamente ou retire as fotografias com erro antes de gravar. As restantes mantêm-se.';
            }
            return null;
        }

        return { attach: attach, blockReason: blockReason, errorMessage: errorMessage };
    })();
</script>

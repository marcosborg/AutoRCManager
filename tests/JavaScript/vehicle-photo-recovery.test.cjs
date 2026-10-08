const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function setup() {
    const context = {
        Dropzone: { ERROR: 'error', ADDED: 'added', QUEUED: 'queued', UPLOADING: 'uploading' },
        document: { createElement() {
            return { listeners: {}, addEventListener(name, fn) { this.listeners[name] = fn; }, remove() { this.removed = true; } };
        } },
    };
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../resources/views/admin/vehicles/partials/photoUploadRecovery.blade.php'), 'utf8').replace(/<\/?script>/g, ''), context);
    const events = {};
    const zone = { files: [], queued: [], on(name, fn) { events[name] = fn; }, enqueueFile(file) { file.status = 'queued'; this.queued.push(file); } };
    context.VehiclePhotoUploads.attach(zone);
    const file = { accepted: true, status: 'error', previewElement: { classList: { remove() {} }, appendChild() {} } };
    return { api: context.VehiclePhotoUploads, events, zone, file };
}

test('retries only the failed photo once, preserving successful photos', () => {
    const { api, events, zone, file } = setup();
    const successful = { status: 'success', uploadedPhotoName: 'already-stored.jpg' };
    zone.files = [successful, file];
    events.error(file, 'Network error', { status: 0 });
    assert.match(api.blockReason(), /fotografias por enviar/);
    const click = file.retryPhotoButton.listeners.click;
    click({ preventDefault() {}, stopPropagation() {} });
    click({ preventDefault() {}, stopPropagation() {} });
    assert.deepEqual(zone.queued, [file]);
    assert.equal(successful.uploadedPhotoName, 'already-stored.jpg');
    assert.match(api.blockReason(), /Aguarde/);
    file.status = 'success';
    events.success(file);
    assert.equal(api.blockReason(), null);
});

test('a second network failure can be retried; removing a failure permits saving', () => {
    const { api, events, zone, file } = setup();
    zone.files = [file];
    events.error(file, null, { status: 503 });
    file.retryPhotoButton.listeners.click({ preventDefault() {}, stopPropagation() {} });
    file.status = 'error';
    events.error(file, null, { status: 503 });
    assert.ok(file.retryPhotoButton);
    zone.files = [];
    assert.equal(api.blockReason(), null);
});

test('invalid photos and expired sessions are not offered blind retries', () => {
    for (const status of [401, 403, 413, 419, 422]) {
        const { events, file } = setup();
        events.error(file, null, { status });
        assert.equal(file.retryPhotoButton, undefined);
    }
    const { events, file } = setup();
    file.accepted = false;
    events.error(file, 'Invalid file');
    assert.equal(file.retryPhotoButton, undefined);
});

test('server error pages and absent validation fields do not break recovery', () => {
    const { api } = setup();
    assert.match(api.errorMessage({}), /Tente novamente/);
    assert.match(api.errorMessage('<html>Server details</html>'), /Tente novamente/);
    assert.equal(api.errorMessage({ errors: { file: ['Imagem inválida'] } }), 'Imagem inválida');
});

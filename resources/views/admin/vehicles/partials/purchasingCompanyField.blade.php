@php
    $selectedPurchasingCompany = old('our_registration', isset($vehicle) ? $vehicle->our_registration : null);
    $availablePurchasingCompanies = collect($purchasingCompanies ?? []);
    if ($selectedPurchasingCompany && !$availablePurchasingCompanies->has($selectedPurchasingCompany)) {
        $availablePurchasingCompanies->put($selectedPurchasingCompany, $selectedPurchasingCompany);
    }
@endphp
<div class="form-group {{ $errors->has('our_registration') ? 'has-error' : '' }}">
    <label for="our_registration">{{ trans('cruds.vehicle.fields.our_registration') }}</label>
    <div class="input-group">
        <select class="form-control" name="our_registration" id="our_registration">
            <option value></option>
            @foreach($availablePurchasingCompanies as $value => $name)
                <option value="{{ $value }}" {{ $selectedPurchasingCompany === $value ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @can('purchasing_company_manage')
            <span class="input-group-btn">
                <button class="btn btn-default" id="new-purchasing-company-button" type="button">Nova</button>
            </span>
        @endcan
    </div>
    @if($errors->has('our_registration'))
        <span class="help-block" role="alert">{{ $errors->first('our_registration') }}</span>
    @endif
    <span class="help-block">{{ trans('cruds.vehicle.fields.our_registration_helper') }}</span>
</div>

@can('purchasing_company_manage')
    <div class="modal fade" id="new-purchasing-company-modal" tabindex="-1" role="dialog" aria-labelledby="new-purchasing-company-title">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="new-purchasing-company-title">Nova empresa compradora</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new-purchasing-company-name">Nome</label>
                        <input class="form-control" id="new-purchasing-company-name" type="text" maxlength="255">
                        <span class="help-block" id="new-purchasing-company-error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="save-purchasing-company-button">Criar e selecionar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const $modal = $('#new-purchasing-company-modal');
            const $name = $('#new-purchasing-company-name');
            const $error = $('#new-purchasing-company-error');

            $('#new-purchasing-company-button').on('click', function () {
                $error.text('');
                $name.val('');
                $modal.modal('show');
                setTimeout(function () { $name.focus(); }, 250);
            });

            $('#save-purchasing-company-button').on('click', function () {
                const $button = $(this);
                $button.prop('disabled', true);
                $error.text('');

                $.ajax({
                    method: 'POST',
                    url: @json(route('admin.purchasing-companies.store')),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { name: $name.val() }
                }).done(function (company) {
                    const option = new Option(company.name, company.name, true, true);
                    $('#our_registration').append(option).trigger('change');
                    $modal.modal('hide');
                }).fail(function (xhr) {
                    const errors = xhr.responseJSON && xhr.responseJSON.errors;
                    $error.text(errors && errors.name ? errors.name[0] : 'Não foi possível criar a empresa.');
                }).always(function () {
                    $button.prop('disabled', false);
                });
            });
        });
    </script>
@endcan

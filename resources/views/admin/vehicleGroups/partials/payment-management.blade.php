@php
    $hasPaymentInput = old('return_to') === $paymentReturnTo;
    $hasPaymentErrors = $errors->any()
        && ($hasPaymentInput || ($paymentReturnTo === 'show' && old('return_to') === null));
    $paymentOld = fn ($key, $default = null) => $hasPaymentInput ? old($key, $default) : $default;
@endphp

@if($canCreateLotPayments)
    <div class="panel panel-default">
        <div class="panel-heading">Submeter pagamento</div>
        <div class="panel-body">
            @if($hasPaymentErrors)
                <div class="alert alert-danger">
                    <strong>Nao foi possivel registar o pagamento.</strong>
                    <ul style="margin-bottom: 0;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.vehicle-groups.payments.store', $vehicleGroup->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="return_to" value="{{ $paymentReturnTo }}">
                <div class="form-group">
                    <label>Tipo de pagamento</label>
                    <div>
                        <label class="radio-inline">
                            <input type="radio" name="payment_type" value="money" {{ $paymentOld('payment_type', 'money') !== 'trade_in' ? 'checked' : '' }}>
                            Dinheiro / transfer&ecirc;ncia
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="payment_type" value="trade_in" {{ $paymentOld('payment_type') === 'trade_in' ? 'checked' : '' }}>
                            Retoma
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <label>Data</label>
                        <input class="form-control date" name="paid_at" value="{{ $paymentOld('paid_at', now()->format(config('panel.date_format'))) }}" required>
                    </div>
                    <div class="col-md-3" id="lot-payment-method-field">
                        <label>Metodo</label>
                        <select class="form-control select2" name="payment_method_id" id="lot_payment_method_id" required>
                            @foreach($paymentMethods as $id => $name)
                                <option value="{{ $id }}" {{ $paymentOld('payment_method_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Recebido</label>
                        <div class="input-group">
                            <input class="form-control" type="number" name="amount" id="lot_payment_amount" value="{{ $paymentOld('amount') }}" step="0.01" min="0.01" required>
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" id="fill-lot-balance" data-balance="{{ number_format($financial['balance'], 2, '.', '') }}">Liquidar saldo</button>
                            </span>
                        </div>
                        <span class="help-block">Pode indicar um valor parcial ou liquidar o saldo em falta.</span>
                    </div>
                    <div class="col-md-3" id="lot-payment-proof-field">
                        <label>Comprovativo</label>
                        <input class="form-control" type="file" name="proof_file">
                        <span class="help-block">Opcional para numer&aacute;rio e retoma.</span>
                    </div>
                </div>
                <div id="lot-payment-classification-fields">
                    <h5 style="margin-top: 15px;">Classifica&ccedil;&atilde;o financeira <small>(opcional)</small></h5>
                    <div class="row">
                        <div class="col-md-3"><label>Faturado</label><input class="form-control" type="number" name="invoiced_amount" value="{{ $paymentOld('invoiced_amount', 0) }}" step="0.01" min="0"></div>
                        <div class="col-md-3"><label>Banco</label><input class="form-control" type="number" name="bank_amount" value="{{ $paymentOld('bank_amount', 0) }}" step="0.01" min="0"></div>
                        <div class="col-md-3"><label>Caixa 1</label><input class="form-control" type="number" name="cash_amount" value="{{ $paymentOld('cash_amount', 0) }}" step="0.01" min="0"></div>
                        <div class="col-md-3"><label>Caixa 2</label><input class="form-control" type="number" name="cash_2_amount" value="{{ $paymentOld('cash_2_amount', 0) }}" step="0.01" min="0"></div>
                    </div>
                    <span class="help-block">Estes valores sao informativos e nao condicionam o montante recebido.</span>
                </div>
                <div id="lot-payment-trade-in-fields" style="display: none;">
                    <hr>
                    <h5>Dados da retoma</h5>
                    <p class="text-muted">Se a matr&iacute;cula j&aacute; existir, a viatura existente ser&aacute; atualizada. Caso contr&aacute;rio, ser&aacute; criada uma viatura em stock.</p>
                    <div class="row">
                        <div class="col-md-3"><label>Matr&iacute;cula</label><input class="form-control" type="text" name="trade_in_license" value="{{ $paymentOld('trade_in_license') }}"></div>
                        <div class="col-md-3">
                            <label>Marca</label>
                            <select class="form-control select2" name="trade_in_brand_id" style="width: 100%;">
                                @foreach($brands as $id => $name)
                                    <option value="{{ $id }}" {{ $paymentOld('trade_in_brand_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label>Modelo</label><input class="form-control" type="text" name="trade_in_model" value="{{ $paymentOld('trade_in_model') }}"></div>
                        <div class="col-md-1"><label>Ano</label><input class="form-control" type="number" name="trade_in_year" value="{{ $paymentOld('trade_in_year') }}" min="1900" max="{{ now()->year + 1 }}"></div>
                        <div class="col-md-2"><label>Km</label><input class="form-control" type="number" name="trade_in_kilometers" value="{{ $paymentOld('trade_in_kilometers') }}" min="0"></div>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Notas</label>
                    <textarea class="form-control" name="notes">{{ $paymentOld('notes') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Submeter para aprovacao</button>
            </form>
        </div>
    </div>
@endif

<div class="panel panel-default">
    <div class="panel-heading">Pagamentos</div>
    <div class="panel-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Metodo</th>
                    <th>Recebido</th>
                    <th>Faturado</th>
                    <th>Banco</th>
                    <th>Caixa 1</th>
                    <th>Caixa 2</th>
                    <th>Estado</th>
                    <th>Criado por</th>
                    <th>Comprovativo</th>
                    <th>Retoma</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicleGroup->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at }}</td>
                        <td>{{ $payment->payment_method->name ?? '-' }}</td>
                        <td>&euro;{{ number_format($payment->amount, 2, ',', '.') }}</td>
                        <td>&euro;{{ number_format($payment->invoiced_amount, 2, ',', '.') }}</td>
                        <td>&euro;{{ number_format($payment->bank_amount, 2, ',', '.') }}</td>
                        <td>&euro;{{ number_format($payment->cash_amount, 2, ',', '.') }}</td>
                        <td>&euro;{{ number_format($payment->cash_2_amount, 2, ',', '.') }}</td>
                        <td>{{ $payment->approval_status }}</td>
                        <td>{{ $payment->creator->name ?? '-' }}</td>
                        <td>
                            @foreach($payment->proof_file as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">{{ trans('global.view_file') }}</a>
                            @endforeach
                        </td>
                        <td>
                            @if($payment->vehicle_trade_in)
                                <a href="{{ route('admin.vehicles.edit', $payment->vehicle_trade_in->created_vehicle_id) }}">
                                    {{ $payment->vehicle_trade_in->license }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($canApproveLots && $payment->approval_status === \App\Models\LotPayment::STATUS_PENDING)
                                <form method="POST" action="{{ route('admin.vehicle-groups.payments.approve', [$vehicleGroup->id, $payment->id]) }}" style="display:inline-block">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $paymentReturnTo }}">
                                    <button class="btn btn-xs btn-success" type="submit">Aprovar</button>
                                </form>
                                <form method="POST" action="{{ route('admin.vehicle-groups.payments.reject', [$vehicleGroup->id, $payment->id]) }}" style="display:inline-block">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $paymentReturnTo }}">
                                    <input type="hidden" name="rejection_reason" value="Rejeitado no detalhe do lote">
                                    <button class="btn btn-xs btn-danger" type="submit">Rejeitar</button>
                                </form>
                            @elseif($payment->approval_status === \App\Models\LotPayment::STATUS_REJECTED)
                                <span class="text-muted">{{ $payment->rejection_reason }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-muted">Sem pagamentos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

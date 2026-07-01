@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Manutencao do sistema</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Comandos</h4>
                            <form method="POST" action="{{ route('admin.system-maintenance.run') }}" style="display:inline-block;margin-right:8px;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="action" value="config-clear">
                                <button type="submit" class="btn btn-warning">php artisan config:clear</button>
                            </form>
                            <form method="POST" action="{{ route('admin.system-maintenance.run') }}" style="display:inline-block;margin-right:8px;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="action" value="cache-clear">
                                <button type="submit" class="btn btn-warning">php artisan cache:clear</button>
                            </form>
                            <form method="POST" action="{{ route('admin.system-maintenance.run') }}" style="display:inline-block;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="action" value="clear-all">
                                <button type="submit" class="btn btn-danger">Limpar configuracao e cache</button>
                            </form>

                            @if(session('command_output'))
                                <pre style="margin-top:15px;white-space:pre-wrap;">{{ session('command_output') }}</pre>
                            @endif

                            <hr>

                            <h4>Leads WhatsApp</h4>
                            <p class="text-muted">
                                Reenfileira notificacoes pendentes para o Node enviar aos stands.
                            </p>
                            <form method="POST"
                                  action="{{ route('admin.system-maintenance.resend-lead-notifications') }}"
                                  onsubmit="return confirm('Confirma o reenfileiramento de notificacoes WhatsApp de leads desde a data indicada?');">
                                @csrf
                                <div class="form-group {{ $errors->has('since') ? 'has-error' : '' }}">
                                    <label for="lead-notifications-since">Desde</label>
                                    <input type="text"
                                           id="lead-notifications-since"
                                           name="since"
                                           class="form-control"
                                           value="{{ old('since', session('lead_resend_since', $defaultLeadNotificationSince)) }}"
                                           placeholder="YYYY-MM-DD HH:MM:SS"
                                           required>
                                    @if($errors->has('since'))
                                        <span class="help-block">{{ $errors->first('since') }}</span>
                                    @else
                                        <span class="help-block">Formato esperado: 2026-06-30 19:11:00</span>
                                    @endif
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    Reenfileirar notificacoes WhatsApp
                                </button>
                            </form>

                            @php($leadResult = session('lead_resend_result'))
                            @if($leadResult)
                                <div class="alert alert-info" style="margin-top:15px;">
                                    <strong>Resultado do reenfileiramento desde {{ session('lead_resend_since') }}:</strong>
                                    <ul style="margin-top:8px;">
                                        <li>Leads colocadas na fila: {{ $leadResult['queued'] }}</li>
                                        <li>Leads ignoradas: {{ $leadResult['skipped'] }}</li>
                                        <li>Erros: {{ count($leadResult['errors']) }}</li>
                                        <li>Notificacoes pendentes para /api/whatsapp/lead-notifications: {{ $leadResult['pending_after'] }}</li>
                                    </ul>
                                </div>

                                @if(! empty($leadResult['queued_ids']))
                                    <p>
                                        <strong>IDs das notificacoes criadas:</strong>
                                        {{ implode(', ', array_slice($leadResult['queued_ids'], 0, 50)) }}
                                        @if(count($leadResult['queued_ids']) > 50)
                                            ... mais {{ count($leadResult['queued_ids']) - 50 }}
                                        @endif
                                    </p>
                                @endif

                                @if(! empty($leadResult['skipped_reasons']))
                                    <details style="margin-top:10px;">
                                        <summary>Leads ignoradas</summary>
                                        <pre style="margin-top:10px;white-space:pre-wrap;">{{ implode("\n", array_slice($leadResult['skipped_reasons'], 0, 50)) }}@if(count($leadResult['skipped_reasons']) > 50)
... mais {{ count($leadResult['skipped_reasons']) - 50 }}@endif</pre>
                                    </details>
                                @endif

                                @if(! empty($leadResult['errors']))
                                    <details open style="margin-top:10px;">
                                        <summary>Erros</summary>
                                        <pre style="margin-top:10px;white-space:pre-wrap;">{{ implode("\n", array_slice($leadResult['errors'], 0, 50)) }}@if(count($leadResult['errors']) > 50)
... mais {{ count($leadResult['errors']) - 50 }}@endif</pre>
                                    </details>
                                @endif
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h4>Configuracao carregada</h4>
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    @foreach($leadConfig as $key => $value)
                                        <tr>
                                            <th style="width:45%;">{{ $key }}</th>
                                            <td>{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach($mailConfig as $key => $value)
                                        <tr>
                                            <th style="width:45%;">{{ $key }}</th>
                                            <td>{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('content')
<div class="content">
    <p>
        <a class="btn btn-default" href="{{ route('admin.oficina-expertise-processes.index') }}">Voltar</a>
        @can('oficina_expertise_process_edit')
            <a class="btn btn-info" href="{{ route('admin.oficina-expertise-processes.edit', $process) }}">Editar / Mudar estado</a>
        @endcan
    </p>

    @if($process->is_alert)
        <div class="alert alert-danger">{{ $process->alertReason() }}</div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">Peritagem de Oficina #{{ $process->id }}</div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr><th style="width:220px;">Matrícula</th><td>{{ $process->license_display }}</td></tr>
                            <tr><th>Viatura</th><td>{{ trim(($process->vehicle->brand->name ?? '') . ' ' . ($process->vehicle->model ?? '')) ?: '-' }}</td></tr>
                            <tr><th>Cliente</th><td>{{ $process->vehicle->client->name ?? '-' }}</td></tr>
                            <tr><th>Seguradora</th><td>{{ $process->insurance_company ?: '-' }}</td></tr>
                            <tr><th>N.º processo / sinistro</th><td>{{ $process->process_number ?: '-' }} / {{ $process->claim_number ?: '-' }}</td></tr>
                            <tr><th>Estado</th><td>{{ $process->status_label }} ({{ $process->days_in_current_status }} dia(s))</td></tr>
                            <tr><th>Próxima ação</th><td>{{ $process->next_action }}</td></tr>
                            <tr><th>Tipo de reparação</th><td>{{ $process->repair_type_label }}</td></tr>
                            <tr><th>Valor aprovado</th><td>{{ $process->approved_amount !== null ? number_format($process->approved_amount, 2, ',', '.') . ' €' : '-' }}</td></tr>
                            <tr><th>Funcionário</th><td>{{ $process->created_by->name ?? '-' }}</td></tr>
                            <tr><th>Observações</th><td>{!! nl2br(e($process->notes ?: '-')) !!}</td></tr>
                            @if($process->rejection_reason)
                                <tr><th>Motivo rejeição/cancelamento</th><td>{!! nl2br(e($process->rejection_reason)) !!}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">Datas principais</div>
                <div class="panel-body">
                    <div class="row">
                        @foreach([
                            'entry_date' => 'Entrada',
                            'scheduled_expertise_date' => 'Peritagem',
                            'approval_date' => 'Aprovação',
                            'repair_start_date' => 'Início reparação',
                            'expected_repair_date' => 'Previsão conclusão',
                            'repair_completed_date' => 'Reparação concluída',
                            'insurance_validation_date' => 'Validação seguradora',
                            'invoice_sent_date' => 'Fatura enviada',
                            'payment_received_date' => 'Pagamento recebido',
                            'closed_at' => 'Fecho',
                        ] as $field => $label)
                            <div class="col-md-3"><strong>{{ $label }}</strong><br>{{ optional($process->{$field})->format(in_array($field, \App\Models\OficinaExpertiseProcess::DATETIME_FIELDS, true) || str_ends_with($field, '_at') ? 'Y-m-d H:i' : 'Y-m-d') ?: '-' }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            @can('oficina_expertise_process_attachment_access')
                <div class="panel panel-default">
                    <div class="panel-heading">Anexos</div>
                    <div class="panel-body">
                        @foreach(\App\Models\OficinaExpertiseProcess::ATTACHMENT_COLLECTIONS as $collection => $label)
                            <h4>{{ $label }}</h4>
                            @forelse($process->getMedia($collection) as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank" style="display:inline-block;margin:0 8px 8px 0;">
                                    @if(str_starts_with((string) $media->mime_type, 'image/'))
                                        <img src="{{ $media->getUrl('thumb') }}" alt="{{ $media->file_name }}">
                                    @else
                                        <i class="fa fa-file"></i> {{ $media->file_name }}
                                    @endif
                                </a>
                            @empty
                                <p class="text-muted">Sem anexos.</p>
                            @endforelse
                        @endforeach
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Histórico de estado</div>
                <div class="panel-body">
                    @forelse($process->histories->sortByDesc('created_at') as $history)
                        <div style="border-bottom:1px solid #eee;padding:8px 0;">
                            <strong>{{ \App\Models\OficinaExpertiseProcess::STATUS_SELECT[$history->new_status] ?? $history->new_status }}</strong>
                            <div class="text-muted small">
                                {{ optional($history->created_at)->format('Y-m-d H:i') }} · {{ $history->changed_by->name ?? '-' }}
                            </div>
                            @if($history->old_status)
                                <div class="small">De {{ \App\Models\OficinaExpertiseProcess::STATUS_SELECT[$history->old_status] ?? $history->old_status }}</div>
                            @endif
                            @if($history->notes)
                                <div>{!! nl2br(e($history->notes)) !!}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">Sem histórico.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

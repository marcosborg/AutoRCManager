@extends('layouts.admin')
@section('content')
<div class="content">
    <p><a href="{{ route('admin.vehicles.edit',$vehicle) }}#vehicle-documents">← Voltar aos documentos da viatura</a></p>
    <div class="panel panel-default">
        <div class="panel-heading"><h1 class="panel-title">Acompanhar documento — {{ \App\Support\VehicleDocuments::applicabilityDocuments()[$document] }}</h1></div>
        <div class="panel-body">
            <h4>{{ $vehicle->license ?: $vehicle->foreign_license }} · {{ $vehicle->model }}</h4>
            <p><strong>{{ $followup ? \App\Models\VehicleDocumentFollowup::LABELS[$followup->status] : 'Novo acompanhamento' }}</strong></p>
            <p>Registe a quem pediu o documento e quando voltar a contactar. O lembrete fica no calendário do responsável. Não são enviadas mensagens automáticas.</p>
            @if(session('message'))<div class="alert alert-success" role="status">{{ session('message') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @if(\App\Support\VehicleDocuments::notApplicable($vehicle,$document))
                <div class="alert alert-info">Documento dispensado. Para voltar a acompanhar, reveja primeiro a opção “Não se aplica” na ficha.</div>
            @else
                @if(($followup->status ?? '') === 'review')<div class="alert alert-warning">É necessário rever este acompanhamento. Confirme os dados e a próxima data antes de voltar a ativar o lembrete.</div>@endif
                @can('vehicle_edit')
                <form method="POST" action="{{ route('admin.vehicles.documents.followup.store',[$vehicle,$document]) }}">
                    @csrf
                    <input type="hidden" name="version" value="{{ old('version',$followup->version ?? 0) }}">
                    <div class="row">
                        <div class="form-group col-md-6"><label for="assigned_to_id">Responsável</label>
                            <select id="assigned_to_id" name="assigned_to_id" class="form-control" required>
                                <option value="">Escolher responsável</option>
                                @foreach($users as $person)<option value="{{ $person->id }}" @selected((string)old('assigned_to_id',$followup->assigned_to_id ?? $defaultResponsible)===(string)$person->id)>{{ $person->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6"><label for="contact">A quem foi pedido</label><input class="form-control" id="contact" name="contact" maxlength="191" value="{{ old('contact',$followup?->contact) }}" required></div>
                        <div class="form-group col-md-6"><label for="requested_on">Data do pedido</label><input class="form-control" type="date" id="requested_on" name="requested_on" value="{{ old('requested_on',$followup->requested_on ?? now()->toDateString()) }}" required></div>
                        <div class="form-group col-md-6"><label for="next_contact_on">Voltar a contactar em</label><input class="form-control" type="date" id="next_contact_on" name="next_contact_on" value="{{ old('next_contact_on',$followup?->next_contact_on) }}" required></div>
                    </div>
                    <div class="form-group"><label for="note">Nota deste contacto ou alteração</label><textarea class="form-control" id="note" name="note" maxlength="1000" required>{{ old('note') }}</textarea></div>
                    <button name="action" value="contact" class="btn btn-primary">{{ !$followup ? 'Guardar acompanhamento' : ($followup->status === 'open' ? 'Registar contacto' : 'Reativar acompanhamento') }}</button>
                    @if($followup)<button name="action" value="reschedule" class="btn btn-default">Reagendar</button>@endif
                    <hr>
                    <h4>Concluir com cópia digital</h4>
                    <p>Recebido em papel não basta. Escolha o ficheiro do tipo correto e confirme o conteúdo. As confirmações da ficha mantêm-se.</p>
                    <div class="form-group"><label for="media_id">Cópia digital identificada</label><select name="media_id" id="media_id" class="form-control">
                        <option value="">{{ $files->isEmpty() ? 'Sem cópia identificada — anexar e identificar na ficha' : 'Escolher ficheiro conferido' }}</option>
                        @foreach($files as $file)<option value="{{ $file->id }}" @selected((string)old('media_id')===(string)$file->id)>{{ $file->file_name }}</option>@endforeach
                    </select></div>
                    @foreach($files as $file)<p><a href="{{ $file->getUrl() }}" target="_blank" rel="noopener">Abrir {{ $file->file_name }}</a></p>@endforeach
                    <div class="checkbox"><label><input type="checkbox" name="confirmed" value="1"> Conferi o conteúdo da cópia digital e corresponde a este documento e viatura.</label></div>
                    <button name="action" value="complete" class="btn btn-success" @disabled($files->isEmpty())>Concluir acompanhamento</button>
                </form>
                @endcan
            @endif
            @if($followup)
                <hr><h4>Histórico</h4>
                <ul>@foreach(array_reverse($followup->history ?? []) as $entry)<li>
                    <strong>{{ \Carbon\Carbon::parse($entry['at'])->format('d/m/Y H:i') }} · {{ $entry['user_name'] }}</strong>
                    — {{ \App\Models\VehicleDocumentFollowup::LABELS[$entry['status']] }}<br>
                    {{ $entry['reason'] }} · Responsável: {{ $entry['responsible_name'] ?? $followup->responsible?->name }} · Pedido a: {{ $entry['contact'] }} · Próximo contacto: {{ \Carbon\Carbon::parse($entry['next_contact_on'])->format('d/m/Y') }}
                </li>@endforeach</ul>
            @endif
        </div>
    </div>
</div>
@endsection

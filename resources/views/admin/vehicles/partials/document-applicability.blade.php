@php
    $decision = $vehicle->document_applicability[$document] ?? null;
    $dispensed = \App\Support\VehicleDocuments::notApplicable($vehicle, $document);
@endphp
<td data-applicability="{{ $document }}" data-dispensed="{{ $dispensed ? '1' : '0' }}">
    <strong>{{ $dispensed ? 'Não se aplica' : 'Por avaliar / conferir' }}</strong>
    @if($decision)
        <div>{{ $decision['reason'] }}</div>
        <small>{{ $decision['user_name'] }} · {{ \Carbon\Carbon::parse($decision['at'])->timezone('Europe/Lisbon')->format('d/m/Y H:i') }}</small>
        <details><summary>Histórico</summary>
            <ul>@foreach(array_reverse($decision['history']) as $event)
                <li>{{ $event['status'] === 'not_applicable' ? 'Não se aplica' : 'Voltar a conferir' }} — {{ $event['reason'] }}
                    ({{ $event['user_name'] }}, {{ \Carbon\Carbon::parse($event['at'])->timezone('Europe/Lisbon')->format('d/m/Y H:i') }})</li>
            @endforeach</ul>
        </details>
    @endif
    @can('vehicle_edit')
        <button type="button" class="btn btn-default btn-xs js-document-applicability"
            data-url="{{ route('admin.vehicles.documents.applicability', [$vehicle, $document]) }}"
            data-revision="{{ \App\Support\VehicleDocuments::applicabilityRevision($vehicle, $document) }}"
            data-status="{{ $dispensed ? 'review' : 'not_applicable' }}"
            data-label="{{ \App\Support\VehicleDocuments::applicabilityDocuments()[$document] }}">
            {{ $dispensed ? 'Voltar a conferir' : 'Não se aplica' }}
        </button>
    @endcan
    @php($followup = $vehicle->documentFollowups->firstWhere('document',$document))
    @if($followup)
        <p style="margin-top:8px"><strong>{{ $followup->statusLabel() }}</strong><br>
        {{ $followup->responsible?->name }}
        @if($followup->status === 'open') · {{ \Carbon\Carbon::parse($followup->next_contact_on)->format('d/m/Y') }}@endif</p>
    @endif
    <a class="btn btn-default btn-xs" style="margin-top:5px" href="{{ route('admin.vehicles.documents.followup',[$vehicle,$document]) }}">{{ $followup ? 'Ver acompanhamento' : 'Acompanhar' }}</a>
</td>

@extends('layouts.admin')
@section('content')
<div class="content"><h3>Histórico da peça #{{ $part->id }} · {{ $part->description }}</h3><p><a href="{{ route('admin.dismantled-parts.index') }}">Voltar ao inventário</a></p>
<p class="text-muted">Cada correção mostra os campos que foram alterados. O registo inicial mantém os dados com que a peça foi criada.</p>
@php($labels=['vehicle_id'=>'Ficha de origem','reference'=>'Referência','description'=>'Descrição','condition'=>'Condição','location'=>'Localização','value'=>'Valor da peça (€)','notes'=>'Notas'])
@forelse($events as $event)<article class="panel panel-default"><div class="panel-heading">{{ $event->description==='audit:created'?'Registo inicial':'Correção' }} · {{ $event->created_at->timezone('Europe/Lisbon')->format('d/m/Y H:i') }} · {{ $event->user?->name ?: 'Utilizador indisponível' }}</div><div class="panel-body">
@foreach($labels as $key=>$label)@if($event->properties?->has($key))<p style="white-space:pre-wrap;overflow-wrap:anywhere"><strong>{{ $label }}:</strong> @if($key==='value'){{ $event->properties[$key] === null ? 'Por definir' : number_format((float)$event->properties[$key],2,',','.') }}@elseif($key==='condition'){{ App\Models\DismantledPart::CONDITIONS[$event->properties[$key]]??$event->properties[$key] }}@else{{ $event->properties[$key]??'Por definir' }}@endif</p>@endif @endforeach
</div></article>@empty<p>Sem alterações registadas.</p>@endforelse
{{ $events->links() }}</div>
@endsection

<section class="panel panel-default" id="repair-preclose-review" aria-labelledby="repair-preclose-review-title">
    <div class="panel-heading"><h4 id="repair-preclose-review-title" style="margin:0">Antes de finalizar</h4></div>
    <div class="panel-body">
        <p>Resumo dos registos guardados para conferência. Não altera custos nem acrescenta condições ao fecho. Ter registos por confirmar não impede finalizar.</p>
        <h5>Desta reparação</h5>
        <ul>
            <li><strong>Tempos:</strong> {{ $precloseReview['time']['open_count'] }} trabalho(s) em curso; {{ $precloseReview['time']['unconfirmed_count'] }} registo(s) por confirmar. <a href="#repair-time-review">Consultar tempos</a>. Ao finalizar, os cronómetros abertos são terminados, como já acontece.</li>
            <li><strong>Tarefas:</strong> {{ count($precloseReview['tasks']['pending']) }} por concluir; {{ count($precloseReview['tasks']['other']) }} concluída(s) ou cancelada(s).</li>
            <li><strong>Peças e serviços lançados manualmente:</strong> {{ count($precloseReview['parts']['rows']) }} de {{ $precloseReview['parts']['total_count'] }} registo(s) com valor ou número de fatura por confirmar.</li>
        </ul>
        <details>
            <summary>Consultar tarefas e lançamentos desta reparação</summary>
            <h5>Tarefas por concluir</h5>
            <ul>
@forelse($precloseReview['tasks']['pending'] as $task)
                <li>{{ $task['label'] }} — {{ $task['status'] }}
@if($task['url'])
 · <a href="{{ $task['url'] }}" target="_blank" rel="noopener">Abrir tarefa</a>
@endif
</li>

@empty
<li>Sem registos.</li>
@endforelse
</ul>
            <h5>Tarefas concluídas ou canceladas</h5>
            <ul>
@forelse($precloseReview['tasks']['other'] as $task)
                <li>{{ $task['label'] }} — {{ $task['status'] }}
@if($task['url'])
 · <a href="{{ $task['url'] }}" target="_blank" rel="noopener">Abrir tarefa</a>
@endif
</li>

@empty
<li>Sem registos.</li>
@endforelse
</ul>
            <h5>Lançamentos manuais por confirmar</h5>
            <ul>
@forelse($precloseReview['parts']['rows'] as $part)
                <li>{{ $part['label'] }} — {{ implode('; ', $part['missing']) }}.</li>

@empty
<li>{{ $precloseReview['parts']['total_count'] ? 'Sem campos por confirmar nos registos consultados.' : 'Sem registos.' }}</li>
@endforelse
</ul>
            <p class="text-muted">As anulações mantêm-se no histórico dos lançamentos. Este resumo não valida as faturas nem os valores.</p>
        </details>

@if($precloseReview['orders']['allowed'])
            @foreach(['repair' => 'Encomendas desta reparação', 'vehicle' => 'Encomendas apenas associadas à viatura'] as $scope => $title)
                <h5>{{ $title }}</h5>
                <p>{{ count($precloseReview['orders'][$scope]) }} registo(s); {{ collect($precloseReview['orders'][$scope])->where('pending', true)->count() }} com receção por confirmar.</p>

@if($scope === 'vehicle')
<p class="text-muted">Estas encomendas não têm uma reparação associada. Não se presume que pertençam a este trabalho.</p>
@endif

                <details><summary>Consultar {{ mb_strtolower($title) }}</summary>

@forelse($precloseReview['orders'][$scope] as $order)
                        <p><strong>{{ $order['label'] }}</strong> — {{ $order['status'] }}
@if($order['url'])
 · <a href="{{ $order['url'] }}" target="_blank" rel="noopener">Abrir encomenda</a>
@endif
</p>
                        <ul>
@forelse($order['items'] as $item)
                            <li>{{ $item['label'] }} — {{ $item['status'] }}. Quantidade recebida: {{ $item['received'] ?? 'Por confirmar' }} / {{ $item['quantity'] ?? 'Por confirmar' }}
@if($item['pending'])
 · Receção por confirmar
@endif
.</li>

@empty
<li>Sem linhas registadas.</li>
@endforelse
</ul>

@empty
<p>Sem registos.</p>
@endforelse

                    <p class="text-muted">Recebido e instalado são estados distintos. Peças devolvidas e encomendas canceladas mantêm a sua identificação.</p>
                </details>
            @endforeach

@endif


@if($precloseReview['services']['allowed'])
            <h5>Serviços externos da viatura</h5>
            <p>{{ count($precloseReview['services']['rows']) }} registo(s); {{ collect($precloseReview['services']['rows'])->where('pending', true)->count() }} com execução ou fatura por confirmar.</p>
            <p class="text-muted">Associados à viatura, não especificamente a esta reparação. A fatura anexada não comprova o pagamento.</p>
            <details><summary>Consultar serviços externos da viatura</summary>
                <ul>
@forelse($precloseReview['services']['rows'] as $service)
                    <li>{{ $service['label'] }} — {{ $service['status'] }}; {{ $service['invoice'] }}
@if($service['url'])
 · <a href="{{ $service['url'] }}" target="_blank" rel="noopener">Abrir serviço</a>
@endif
</li>

@empty
<li>Sem registos.</li>
@endforelse
</ul>
            </details>

@endif

        <p class="text-muted" style="margin-top:12px">Sem registos ou sem avisos não significa que a reparação esteja validada. O botão «Finalizar reparação» mantém o funcionamento atual.</p>
    </div>
</section>

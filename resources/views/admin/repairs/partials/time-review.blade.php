<section id="repair-time-review" aria-labelledby="repair-time-review-title" class="panel panel-default">
    <div class="panel-heading">
        <h4 id="repair-time-review-title" style="margin:0">Conferência dos tempos</h4>
    </div>
    <div class="panel-body">
        <p>O tempo terminado e o tempo em curso são apresentados separadamente. Duas pessoas a trabalhar uma hora representam duas horas de mão de obra.</p>
        <p><strong>Trabalho terminado:</strong> {{ $timeReview['totals']['closed_minutes'] }} min</p>
        <p><strong>Trabalho em curso:</strong> {{ $timeReview['totals']['open_count'] }} registo(s) — {{ $timeReview['totals']['open_minutes'] }} min provisórios</p>
        <p class="text-muted">Tempos em curso calculados ao abrir a página: {{ $timeReview['as_of'] }}. Atualize a página para consultar o tempo mais recente. Esta conferência não calcula custos nem altera os registos.</p>
        @if($timeReview['totals']['unconfirmed_count'] > 0)
            <p class="alert alert-warning" role="status"><strong>Por confirmar:</strong> {{ $timeReview['totals']['unconfirmed_count'] }} registo(s) com datas ou duração incompletas ou inconsistentes. Não entram nos minutos desta conferência; os dados originais foram preservados.</p>
        @endif
        <h5>Por mecânico</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed">
                <thead><tr><th>Mecânico</th><th>Terminado (min)</th><th>Em curso (min provisórios)</th><th>Trabalhos em curso</th><th>Por confirmar</th></tr></thead>
                <tbody>
                    @forelse($timeReview['mechanics'] as $mechanic)
                        <tr>
                            <td>{{ $mechanic['name'] }}</td>
                            <td>{{ $mechanic['totals']['closed_minutes'] }}</td>
                            <td>{{ $mechanic['totals']['open_minutes'] }}</td>
                            <td>{{ $mechanic['totals']['open_count'] }}</td>
                            <td>{{ $mechanic['totals']['unconfirmed_count'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Sem tempos registados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <details>
            <summary>Consultar tempos por tarefa e mecânico</summary>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed">
                <thead><tr><th>Tarefa</th><th>Mecânico</th><th>Terminado (min)</th><th>Em curso (min provisórios)</th><th>Trabalhos em curso</th><th>Por confirmar</th></tr></thead>
                <tbody>
                    @forelse($timeReview['interventions'] as $task)
                        @foreach($task['mechanics'] as $mechanic)
                            <tr>
                                <td>{{ $task['title'] }}</td>
                                <td>{{ $mechanic['name'] }}</td>
                                <td>{{ $mechanic['totals']['closed_minutes'] }}</td>
                                <td>{{ $mechanic['totals']['open_minutes'] }}</td>
                                <td>{{ $mechanic['totals']['open_count'] }}</td>
                                <td>{{ $mechanic['totals']['unconfirmed_count'] }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <th colspan="2">Total: {{ $task['title'] }}</th>
                            <td>{{ $task['totals']['closed_minutes'] }}</td>
                            <td>{{ $task['totals']['open_minutes'] }}</td>
                            <td>{{ $task['totals']['open_count'] }}</td>
                            <td>{{ $task['totals']['unconfirmed_count'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Sem tempos registados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </details>
    </div>
</section>

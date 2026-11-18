@if($canManageSupplierPayments && $costReview)
@php
    $costMoney = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
    $costSourceUrl = function (array $row) {
        $route = $row['source_route'] ?? null;
        $permission = [
            'admin.vehicles.edit' => 'vehicle_edit',
            'admin.repairs.edit' => 'repair_edit',
            'admin.repairs.show' => 'repair_show',
            'admin.part-orders.show' => 'part_order_show',
            'admin.part-orders.edit' => 'part_order_edit',
            'admin.part-payments.edit' => 'part_payment_edit',
            'admin.external-services.edit' => 'external_service_edit',
        ][$route ?? ''] ?? null;
        $allowed = $permission && Gate::allows($permission);
        if ($route === 'admin.cash.index') {
            $allowed = auth()->user()->roles->pluck('title')->intersect(['Admin', 'Adm'])->isNotEmpty();
        }
        return $allowed && Route::has($route) ? route($route, $row['source_parameters'] ?? []) : null;
    };
@endphp
<div class="col-md-12">
    <section class="panel panel-default" id="vehicle-cost-review" aria-labelledby="vehicle-cost-review-title">
        <div class="panel-heading"><h4 id="vehicle-cost-review-title" style="margin:0">Conferência dos custos</h4></div>
        <div class="panel-body">
            <p>Consulte a origem dos valores guardados. Os totais e as margens atuais mantêm-se; esta conferência não corrige nem elimina lançamentos.</p>
            <div class="row">
                <div class="col-sm-6"><p><strong>Custo total atual do Registo central:</strong><br>{{ $costMoney($costReview['current_total']) }}</p></div>
                <div class="col-sm-6"><p><strong>Despesas de aquisição atuais:</strong><br>{{ $costMoney($costReview['acquisition_total']) }}<br><small class="text-muted">Resumo separado, com as regras atuais da aquisição. Não somar ao custo total.</small></p></div>
            </div>
            @if(! $costReview['reconciles'])
                <div class="alert alert-warning" role="status">As parcelas apresentadas não coincidem com o custo total atual. É necessária conferência; nenhum total foi alterado.</div>
            @endif
            @if(count($costReview['warnings']))
                <div class="alert alert-warning" role="status">
                    <strong>Pontos a conferir</strong>
                    <p>Os avisos de possível duplicação não confirmam um erro. Confira os documentos e a origem de cada lançamento.</p>
                    <ul>@foreach($costReview['warnings'] as $warning)<li>{{ $warning['message'] }}</li>@endforeach</ul>
                </div>
            @else
                <p class="text-muted">Sem avisos automáticos nos registos consultados. Isto não substitui a conferência dos documentos.</p>
            @endif
            <h5>Origem dos valores</h5>
            <p class="text-muted">“Entra no total” indica o que a regra atual soma. Um valor registado ou estimado não comprova que esteja pago.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead><tr><th>Origem / registo</th><th>Valor</th><th>Entra no total atual?</th><th>Tipo de valor</th><th>Pagamento</th><th>Observações</th></tr></thead>
                    <tbody>
                    @foreach($costReview['rows'] as $row)
                        @php($sourceUrl = $costSourceUrl($row))
                        <tr>
                            <td><strong>{{ $row['group'] }}</strong><br>{{ $row['label'] }}
                                @if($sourceUrl)<br><a href="{{ $sourceUrl }}" target="_blank" rel="noopener">Abrir origem <span class="sr-only">de {{ $row['label'] }}</span></a>@endif
                            </td>
                            <td class="text-nowrap">{{ $costMoney($row['amount']) }}</td>
                            <td>{{ $row['included'] ? 'Sim' : 'Não' }}</td>
                            <td>{{ $row['value_status'] }}</td>
                            <td>{{ $row['payment_status'] }}</td>
                            <td>@foreach((array) ($row['notes'] ?? []) as $note)<div>{{ $note }}</div>@endforeach</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><th>Soma das parcelas que entram no total</th><td class="text-nowrap">{{ $costMoney($costReview['source_total']) }}</td><td colspan="4">Conferência do cálculo existente, sem alterações.</td></tr></tfoot>
                </table>
            </div>
            @if(count($costReview['payment_rows']))
                <details>
                    <summary>Consultar pagamentos registados</summary>
                    <p>Estes pagamentos são apresentados para conferência e não são novamente somados ao custo. Um pagamento de uma encomenda pode abranger várias peças.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed">
                            <thead><tr><th>Origem / registo</th><th>Valor</th><th>Pagamento</th><th>Observações</th></tr></thead>
                            <tbody>@foreach($costReview['payment_rows'] as $row)
                                @php($sourceUrl = $costSourceUrl($row))
                                <tr><td>{{ $row['group'] }} — {{ $row['label'] }}@if($sourceUrl)<br><a href="{{ $sourceUrl }}" target="_blank" rel="noopener">Abrir origem</a>@endif</td><td class="text-nowrap">{{ $costMoney($row['amount']) }}</td><td>{{ $row['payment_status'] }}</td><td>@foreach((array) ($row['notes'] ?? []) as $note)<div>{{ $note }}</div>@endforeach</td></tr>
                            @endforeach</tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    </section>
</div>
@endif

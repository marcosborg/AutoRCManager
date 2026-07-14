<div class="row">
    <div class="col-md-3">
        <div class="panel panel-default"><div class="panel-heading">Total venda</div><div class="panel-body"><h4>&euro;{{ number_format($financial['target'], 2, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-default"><div class="panel-heading">Recebido aprovado</div><div class="panel-body"><h4>&euro;{{ number_format($financial['paid'], 2, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-3">
        <div class="panel panel-default"><div class="panel-heading">Por receber</div><div class="panel-body"><h4>&euro;{{ number_format($financial['balance'], 2, ',', '.') }}</h4></div></div>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
        <div class="panel panel-default"><div class="panel-heading">Faturado</div><div class="panel-body"><h4>&euro;{{ number_format($financial['invoiced'], 2, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-default"><div class="panel-heading">Banco</div><div class="panel-body"><h4>&euro;{{ number_format($financial['bank'], 2, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-default"><div class="panel-heading">Caixa 1</div><div class="panel-body"><h4>&euro;{{ number_format($financial['cash'], 2, ',', '.') }}</h4></div></div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-default"><div class="panel-heading">Caixa 2</div><div class="panel-body"><h4>&euro;{{ number_format($financial['cash_2'], 2, ',', '.') }}</h4></div></div>
    </div>
</div>

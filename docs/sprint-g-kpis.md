# Sprint G KPIs & Dashboard

## KPIs

- Total de custos
- Total de receitas
- Resultado (receitas - custos)
- Numero de viaturas com consignacao ativa no periodo
- Numero de viaturas movimentadas no periodo

## Origem dos dados

- Totais e resultados por unidade: `OperationalUnitReportService`
- Veiculos ativos/movimentados: consignacoes no periodo

## Limitacoes

- Valores informativos (sem impostos, amortizacoes ou ajustes contabilisticos).
- Viaturas sem consignacao no periodo nao entram nos totais por unidade.

## Checklist manual

1) Abrir dashboard sem dados.
2) Abrir com dados do mes atual.
3) Alterar periodo e validar atualizacao.
4) Comparar valores com Relatorio por Unidade.

# Manual de Lotes, Pagamentos e Aprovações

## Objetivo

Este módulo foi criado para separar claramente:

- venda da viatura;
- pagamento recebido;
- valor faturado;
- valor em caixa;
- estado operacional da viatura;
- aprovação de alterações financeiras.

Na prática, isto permite saber se uma viatura está vendida, parcialmente paga, paga, faturada ou entregue sem depender de folhas em papel.

## Conceitos principais

### Estado operacional da viatura

O estado geral da viatura continua a indicar a situação física/operacional:

- Stand;
- Oficina;
- Vendido;
- Em trânsito;
- outros estados existentes.

Este estado não deve ser usado sozinho para saber se a viatura está paga.

### Estado financeiro da viatura

O sistema calcula automaticamente o estado financeiro com base no lote e nos pagamentos aprovados:

- Disponível;
- Vendida não paga;
- Parcialmente paga;
- Paga;
- Faturada;
- Entregue.

Estes estados aparecem na ficha da viatura, na área "Estado financeiro do lote".

### Lote

Um lote representa uma venda individual ou conjunta de viaturas a um cliente.

Pode ser:

- Lote global: existe um valor total para várias viaturas.
- Lote discriminado: cada viatura tem o seu valor individual.

Os antigos "Grupos" passam a ser usados como "Lotes".

### Pagamento

Um pagamento regista o valor recebido do cliente.

Cada pagamento é dividido em:

- Recebido: total efetivamente recebido.
- Faturado: parte que corresponde a faturação.
- Caixa: parte que entra em caixa operacional.

Regra obrigatória:

```text
Recebido = Faturado + Caixa
```

### Aprovação Rafael

Pagamentos e validações financeiras ficam pendentes até aprovação.

Enquanto um pagamento estiver pendente:

- não conta como recebido;
- não altera o saldo do lote;
- não altera o estado financeiro da viatura.

Só após aprovação passa a contar nos totais.

## Procedimento 1: Criar um lote

1. Abrir o menu **Lotes**.
2. Clicar em **Novo lote**.
3. Preencher:
   - Nome do lote;
   - Cliente principal;
   - Tipo;
   - Valor global, se aplicável;
   - Modo de distribuição;
   - Viaturas incluídas;
   - Observações, se necessário.
4. Gravar.

### Lote global

Usar quando o negócio foi fechado por um valor total.

Exemplo:

```text
Cliente compra 5 viaturas por 50.000€
```

O sistema distribui internamente o valor pelas viaturas para efeitos de reconciliação.

Por defeito, a distribuição é proporcional ao PVP/preço original. Se não houver valores, distribui igualmente.

### Lote discriminado

Usar quando cada viatura tem um preço individual acordado.

Neste caso, preencher o preço ajustado por viatura.

## Procedimento 2: Aprovar um lote

1. Abrir o lote.
2. Confirmar cliente, viaturas e valores.
3. Clicar em **Aprovar lote**.

Também é possível ver lotes sem aprovação no menu **Aprovações Rafael**.

## Procedimento 3: Registar um pagamento

1. Abrir o lote.
2. Ir à secção **Submeter pagamento**.
3. Preencher:
   - Data;
   - Método de pagamento;
   - Valor recebido;
   - Valor faturado;
   - Valor caixa;
   - Comprovativo, quando aplicável;
   - Notas.
4. Clicar em **Submeter para aprovação**.

O pagamento fica com estado:

```text
pending
```

ou seja, pendente de aprovação.

## Procedimento 4: Aprovar ou rejeitar pagamento

1. Abrir o menu **Aprovações Rafael**.
2. Ver pagamentos pendentes.
3. Confirmar:
   - lote;
   - cliente;
   - método;
   - valor recebido;
   - valor faturado;
   - valor caixa;
   - comprovativo.
4. Escolher:
   - **Aprovar**;
   - **Rejeitar**, indicando motivo.

Quando aprovado, o pagamento passa a contar nos totais do lote.

Quando rejeitado, fica registado, mas não entra nos saldos.

## Procedimento 5: Consultar estado financeiro de uma viatura

1. Abrir a ficha da viatura.
2. Ver a área **Estado financeiro do lote**.

Esta área mostra:

- estado financeiro;
- valor de venda;
- valor recebido;
- valor faturado;
- valor em caixa;
- saldo em aberto;
- ligação para o lote.

## Regras importantes

### Não apagar informação financeira

Pagamentos, lotes e aprovações devem ficar registados.

Se houver erro:

- rejeitar o pagamento;
- criar novo pagamento correto;
- ou corrigir o lote, que volta a exigir aprovação.

### Faturado não é o mesmo que recebido

O sistema trata estes valores separadamente.

Exemplo:

```text
Recebido: 10.000€
Faturado: 6.000€
Caixa: 4.000€
```

Isto permite controlar o dinheiro real recebido e o valor declarado/faturado.

### Pagamento pendente não conta

Um pagamento submetido mas ainda não aprovado não altera:

- total recebido;
- total faturado;
- caixa;
- estado financeiro da viatura.

## Permissões

Foram criadas permissões próprias para este módulo:

- acesso a lotes;
- criar lotes;
- editar lotes;
- ver lotes;
- apagar lotes;
- criar pagamentos de lote;
- aprovar pagamentos e lotes.

A aprovação deve ser atribuída apenas ao Rafael ou a utilizadores autorizados a validar operações financeiras.

## O que ficou fora desta fase

Esta fase não inclui ainda:

- caixa mensal Rafael/Rita;
- reconciliação mensal completa;
- integração automática com software de faturação;
- alertas automáticos;
- assinaturas digitais.

Esses pontos ficam para as fases seguintes.

## Resumo operacional

Fluxo recomendado:

```text
1. Criar lote
2. Associar cliente e viaturas
3. Definir valor global ou valores por viatura
4. Rafael aprova lote
5. Registar pagamento
6. Rafael aprova pagamento
7. Sistema atualiza saldos e estado financeiro
8. Consultar viatura/lote para reconciliação
```

# WhatsApp Cloud API

O AutoRCManager suporta três transportes durante a migração:

- `email`: não envia WhatsApp;
- `node`: mantém o bridge antigo e o respetivo polling;
- `cloud`: envia diretamente pela API oficial e processa webhooks em fila.

## Configuração

1. Crie uma app Business e uma WhatsApp Business Account no Meta Business Manager.
2. Crie um System User token permanente com `whatsapp_business_messaging` e associe a app ao WABA.
3. Preencha as variáveis `WHATSAPP_*` descritas em `.env.example`.
4. Configure o callback como `https://SEU-DOMINIO/api/whatsapp/webhook`, subscreva o campo `messages` e use `WHATSAPP_VERIFY_TOKEN` na verificação.
5. Submeta em `pt_PT` os templates abaixo antes de ativar `WHATSAPP_TRANSPORT=cloud`.

## Contrato dos templates

### `autorc_nova_lead_cliente_v1`

Corpo com três parâmetros, pela ordem: nome do cliente, interesse/viatura e nome da empresa.

### `autorc_nova_lead_vendedor_v1`

Corpo com cinco parâmetros, pela ordem: nome do vendedor, nome da lead, telefone, interesse e orçamento. Deve ter um botão URL dinâmico no índice `0`; o Laravel envia o token do link como sufixo.

## Operação

Execute um worker que inclua a fila `whatsapp`, por exemplo:

```bash
php artisan queue:work --queue=whatsapp,default
```

Teste primeiro com o número de teste da Meta. Durante o piloto mantenha `node` ou `email`; só um transporte pode estar ativo. Ative `cloud` depois de confirmar envio, receção, estados e mensagens manuais da WhatsApp Business app em coexistência.

Se a coexistência não estiver disponível para o número atual, não o migre: registe um número novo dedicado à Cloud API.

# Meta Lead Ads

Integração fase 1 para receber leads Meta Lead Ads do formulário configurado em `META_FORM_ID`.

## Variáveis `.env`

```env
META_APP_ID=
META_APP_SECRET=
META_VERIFY_TOKEN=
META_PAGE_ID=
META_FORM_ID=829801293296262
META_GRAPH_VERSION=v25.0
META_PAGE_ACCESS_TOKEN=
```

## Webhook

- Verificação Meta: `GET /api/meta/webhook`
- Receção de leads: `POST /api/meta/webhook`

O `META_VERIFY_TOKEN` tem de ser igual ao token configurado na app Meta.

Teste manual de verificação:

```bash
curl "https://autorcmanager.pt/api/meta/webhook?hub.mode=subscribe&hub.verify_token=TOKEN&hub.challenge=123"
```

Se o token estiver correto, a resposta deve ser `123`.

## Fluxo

1. A Meta envia `leadgen_id` para o webhook.
2. O job `ProcessMetaLeadJob` lê os detalhes na Graph API.
3. O lead é gravado em `leads` com `leadgen_id` único.
4. A atribuição é feita em round-robin pelos utilizadores com role `Stand`.
5. O vendedor atribuído recebe notificação por mail e database.

Leads de outros formulários são ignorados quando `META_FORM_ID` está configurado.

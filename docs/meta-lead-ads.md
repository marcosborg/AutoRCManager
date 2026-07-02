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
META_INBOUND_WEBHOOK_TOKEN=
QUEUE_CONNECTION=database
```

## Webhook

- Verificação Meta: `GET /api/meta/webhook`
- Receção de leads: `POST /api/meta/webhook`
- Receção direta por Make/Apiway: `POST /api/meta/leads/inbound`

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

## Fila e pool de processamento

As entradas de leads respondem rápido ao webhook e ficam na fila `meta-leads`.
Isto evita que picos de leads obriguem o Apache/PHP a processar todas ao mesmo tempo.

Depois de publicar:

```bash
php artisan migrate
php artisan queue:work database --queue=meta-leads --sleep=2 --tries=3 --timeout=120
```

Para criar uma pool controlada, corre 1 ou 2 workers iguais no servidor. Mais workers aumentam concorrência, mas também aumentam carga sobre MySQL, Graph API, mail e integrações de IA. Começa com 1 worker; sobe para 2 se a fila acumular.

Se usares Supervisor/systemd/serviço Windows, aponta o processo para o comando acima e garante restart automático.

## Entrada direta por Make/Apiway

Enquanto a app Meta não tiver `leads_retrieval`, uma integração externa que já recebe os leads pode reenviar os dados completos para:

```text
https://autorcmanager.pt/api/meta/leads/inbound
```

Autenticação:

```http
Authorization: Bearer META_INBOUND_WEBHOOK_TOKEN
```

ou:

```http
X-Lead-Webhook-Token: META_INBOUND_WEBHOOK_TOKEN
```

Campos aceites:

```json
{
  "leadgen_id": "id-unico-da-meta-ou-make",
  "full_name": "Cliente Teste",
  "email": "cliente@example.com",
  "phone": "912345678",
  "vehicle_interest": "BMW Serie 1",
  "budget": "15000",
  "financing": "Sim",
  "trade_in": "Nao"
}
```

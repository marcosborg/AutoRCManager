# AutoRC WhatsApp Node Server

Servidor local para ligar o WhatsApp Web ao AutoRCManager.

## Instalação

```bash
cd node-whatsapp-server
cp .env.example .env
npm install
npm start
```

No primeiro arranque aparece um QR code no terminal. Digitaliza com o WhatsApp que vai responder às leads.

## Variáveis

- `LARAVEL_API_URL`: URL base da API Laravel, por exemplo `https://autorcmanager.pt/api`.
- `LARAVEL_API_TOKEN`: igual ao `NODE_API_TOKEN` configurado no Laravel.
- `PORT`: porta local do servidor Express.
- `POLL_INTERVAL_MS`: intervalo para recolher mensagens pendentes criadas pelo Laravel.

## Teste local sem WhatsApp

```bash
curl -X POST http://127.0.0.1:3099/simulate-incoming \
  -H "Content-Type: application/json" \
  -d '{"phone":"+351910000000","message":"Olá, queria saber mais sobre financiamento","name":"Cliente Teste"}'
```

## Fluxo

1. Mensagens recebidas no WhatsApp são enviadas para `/api/whatsapp/incoming-message`.
2. O Laravel cria/atualiza lead, conversa e mensagens.
3. Se houver resposta automática, o Node envia-a ao cliente e reporta `/sent`.
4. Leads Meta/Make criam saudações `pending`; o Node faz polling em `/api/whatsapp/outgoing-messages` e envia.

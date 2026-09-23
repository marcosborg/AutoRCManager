# API para integrações externas

Esta documentação descreve a API HTTP disponível no código atual. A URL base é `https://SEU-DOMINIO/api`. Use sempre HTTPS em produção e envie `Accept: application/json`. As respostas e os erros são JSON, salvo os uploads e os endpoints de ficheiros. A especificação [OpenAPI 3](openapi.json) pode ser importada no Postman ou num visualizador Swagger. O corpo genérico nela indicado para rotas antigas é um ponto de partida; as regras exatas estão nos Form Requests e controladores correspondentes.

## Acesso e credenciais

1. Um administrador cria um utilizador dedicado para a integração em **Admin > Users**, atribuindo apenas os papéis e permissões necessários.
2. O integrador obtém um token inicial com `POST /api/mobile/auth/login` e a palavra-passe dessa conta.
3. Com esse token, cria um token de integração com `POST /api/v1/integration/tokens` e revoga o token inicial com `POST /api/mobile/auth/logout`. O token de integração aparece **uma única vez** na resposta. Guardá-lo num gestor de segredos. A expiração padrão é 90 dias; o máximo é 365.
4. Os pedidos seguintes usam `Authorization: Bearer TOKEN`. As permissões continuam a ser as do utilizador associado. Revogar o token em `DELETE /api/v1/integration/tokens/{id}` ou desativar a conta.

Para **cobertura total do backoffice**, criar o token a partir de uma conta dedicada com o papel real `Admin`. Apenas tokens com o nome interno `integration:*` dessa conta podem aceder a `/api/v1/backoffice/*`. O token inicial do login móvel não dá acesso a essas rotas; usar o token criado no passo 3.

```bash
curl -X POST 'https://SEU-DOMINIO/api/mobile/auth/login' \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"integracao@example.com","password":"PALAVRA_PASSE","device_name":"integracao-inicial"}'

curl -X POST 'https://SEU-DOMINIO/api/v1/integration/tokens' \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer TOKEN_INICIAL' \
  -d '{"name":"ERP externo","password":"PALAVRA_PASSE","expires_in_days":90}'
```

`GET /api/v1/integration/tokens` lista identificadores, nomes e datas, sem revelar o segredo. `POST /api/mobile/auth/logout` revoga apenas o token usado no pedido. Criar um token por sistema/ambiente permite rodá-los separadamente. O login devolve também o utilizador, os papéis e as permissões; `GET /api/mobile/auth/me` consulta os mesmos dados.

## Convenções

- `GET` consulta, `POST` cria ou executa uma ação, `PUT/PATCH` atualiza e `DELETE` remove.
- `401`: token ausente, inválido ou expirado. `403`: falta uma permissão. `404`: recurso inexistente. `422`: dados inválidos, com detalhes em `errors`. `429`: limite de pedidos excedido.
- A API usa um limitador geral configurado para 600 pedidos/minuto quando o utilizador já está resolvido pelo middleware e 60 por IP nos restantes casos; clientes Bearer devem planear-se para 60/minuto. A emissão de tokens tem ainda limite de 10/minuto.
- As datas aceites dependem do endpoint. Nos pedidos CRUD antigos, verificar as regras em `app/Http/Requests/Store*Request.php` e `Update*Request.php`; várias datas seguem `config('panel.date_format')`. Nos endpoints de catálogo, `updated_since` aceita uma data válida de Laravel, por exemplo ISO 8601.
- Os endpoints CRUD antigos podem devolver coleções completas, sem paginação. Para sincronizações, preferir o catálogo paginado ou as listas paginadas de gestão/oficina.
- Todos os identificadores nas rotas são IDs internos. Não usar números de matrícula como IDs.

## Consulta paginada dos módulos de backoffice

`GET /api/v1/integration/catalog/{resource}` e `GET /api/v1/integration/catalog/{resource}/{id}` disponibilizam consultas de módulos que ainda não tinham endpoints próprios. A lista devolve `data`, `current_page`, `last_page`, `per_page`, `total` e URLs de paginação. Usa `page` (desde 1), `per_page` (1–100, padrão 25), `updated_since` e, nos recursos que o têm, `status`. Os resultados estão ordenados por ID crescente e excluem registos eliminados logicamente.

| `{resource}` | Dados disponíveis | Permissão de lista |
| --- | --- | --- |
| `consignments` | Consignações | `vehicle_consignment_access` |
| `trade-ins` | Retomas | `vehicle_trade_in_access` ou permissão/papel de conversão |
| `vehicle-groups` | Lotes | `vehicle_group_access` ou `vehicle_lot_access` |
| `external-services` | Serviços externos | `external_service_access` |
| `painting-jobs` | Trabalhos de pintura | `painting_job_access` |
| `part-orders` | Encomendas de peças | `part_order_access` |
| `workshop-interventions` | Planeamento da oficina | `workshop_planning_access` |
| `general-states` | Estados gerais | `general_state_access` |
| `workshop-states` | Estados de oficina | `workshop_state_access` |
| `part-payments` | Pagamentos de peças | `part_payment_access` |
| `part-receipts` | Receções de peças | `part_receipt_access` |

A consulta individual exige a permissão `*_show` correspondente quando ela existe no backoffice; a consulta de serviços externos, retomas, intervenções e estados de oficina usa a permissão de lista. Os campos expostos são uma lista explícita no controlador. Por exemplo, os valores financeiros dos lotes e retomas não são expostos neste catálogo; para os totais autorizados, usar os endpoints de gestão que aplicam as suas regras de acesso.

Tal como no backoffice, quem apenas possui `vehicle_trade_in_access` vê somente retomas convertidas; pedidos por outros estados são recusados. Quem tem permissão ou papel para converter pode consultar todos os estados.

Para consignações existem também `POST /api/v1/integration/consignments`, `PUT /api/v1/integration/consignments/{id}` e `DELETE /api/v1/integration/consignments/{id}`. Estas operações usam as mesmas regras de validação e o mesmo serviço de domínio do backoffice, incluindo controlo de sobreposições e efeitos na localização da viatura. Exigem `vehicle_consignment_create`, `vehicle_consignment_edit` e `vehicle_consignment_delete`, respetivamente. O corpo de criação requer `vehicle_id`, `from_unit_id`, `to_unit_id` **ou** `to_unit_name` e `starts_at` no formato de data e hora configurado em `config/panel.php`. A atualização requer também `status` e, ao encerrar, `ends_at`.

```bash
curl 'https://SEU-DOMINIO/api/v1/integration/catalog/part-orders?status=pending&per_page=50&page=1' \
  -H 'Accept: application/json' -H 'Authorization: Bearer TOKEN'
```

## Funcionalidades existentes

### Cobertura completa do backoffice (`/api/v1/backoffice`)

Cada rota `/admin/...` da aplicação tem uma rota equivalente em `/api/v1/backoffice/...`, com o **mesmo método HTTP e o mesmo sufixo**. Por exemplo:

| No backoffice | Na API |
| --- | --- |
| `GET /admin/cash` | `GET /api/v1/backoffice/cash` |
| `POST /admin/cash/movements` | `POST /api/v1/backoffice/cash/movements` |
| `PUT /admin/vehicles/{vehicle}` | `PUT /api/v1/backoffice/vehicles/{vehicle}` |
| `POST /admin/sale-closure-approvals/{approval}/approve` | `POST /api/v1/backoffice/sale-closure-approvals/{approval}/approve` |
| `POST /admin/system-maintenance/run` | `POST /api/v1/backoffice/system-maintenance/run` |

O espelho cobre as 377 rotas do backoffice e chama os próprios controladores. Mantém as suas validações, permissões e efeitos de negócio, incluindo ficheiros `multipart/form-data`, transições, aprovações e exportações. É exclusivo para tokens de integração de administradores. As rotas de manutenção e desligamento continuam a exigir a confirmação e as regras do controlador original.

As respostas seguem estas regras:

- Uma página HTML do backoffice é devolvida como `{ "view": "nome.da.view", "data": { ... } }`. `GET .../create` e `GET .../edit` devolvem os dados do formulário, como opções e registo atual.
- Um redirecionamento de sucesso é devolvido como `{ "message": "...", "redirect_to": "..." }` com HTTP 200. `redirect_to` pode apontar para `/admin/...`; para consultar o resultado via API, substituir o prefixo `/admin` por `/api/v1/backoffice`.
- Um redirecionamento com erros de formulário é convertido em HTTP 422 com `errors`. As validações que já produzem JSON mantêm o formato original.
- Respostas JSON e downloads (PDF, CSV, etc.) são entregues sem conversão. Respeitar `Content-Type` e `Content-Disposition`.

Este espelho oferece acesso a todas as operações existentes, mas **não uniformiza os contratos de dados dos controladores antigos**. Para integrações novas, usar primeiro os endpoints JSON nativos descritos abaixo; recorrer ao espelho para as restantes operações. O [índice de contratos](api-backoffice-contracts.md) identifica o método responsável e a validação de cada rota. Sempre que `routes/web.php` mudar, executar `php artisan route:clear`, `php scripts/generate-backoffice-api-map.php` e `php scripts/generate-backoffice-contracts.php`, por esta ordem. O teste `BackofficeApiMapTest` falha se alguma rota deixar de ter correspondência protegida.

Algumas listas antigas usam DataTables: para obter as linhas em JSON, enviar também `X-Requested-With: XMLHttpRequest` e os parâmetros DataTables usados pela página. Sem esse cabeçalho, o endpoint devolve os dados de configuração da página em `{view, data}`. As listas nativas (`/api/v1/gestao`, `/api/mobile` e `/api/v1/integration/catalog`) têm contratos mais simples para sincronização.

```bash
curl 'https://SEU-DOMINIO/api/v1/backoffice/vehicle-groups' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN_ADMIN_INTEGRACAO' \
  -H 'X-Requested-With: XMLHttpRequest'
```

### CRUD principal (`/api/v1`)

Os recursos `permissions`, `roles`, `users`, `countries`, `clients`, `brands`, `vehicles`, `supliers` (grafia histórica), `payment-statuses`, `carriers`, `pickup-states`, `repairs` e `repair-states` oferecem `GET coleção`, `POST coleção`, `GET /{id}`, `PUT/PATCH /{id}` e `DELETE /{id}`. Cada operação exige a respetiva permissão (`*_access`, `*_create`, `*_show`, `*_edit`, `*_delete`). Os pedidos de escrita são validados pelos Form Requests do projeto. A criação/atualização de veículos e reparações nesta API histórica não cobre necessariamente todos os efeitos de negócio dos formulários web; para transições da oficina usar as ações específicas abaixo.

### Gestão (`/api/v1/gestao`)

- `dashboard`, `alerts`, `vehicles/by-state`, `vehicles`, `vehicles/{id}`, `vehicles/{id}/central-register`, `consignments`, `trade-ins`, `workshop`, `leads`, `leads/{id}`: consultas.
- `POST alerts/{id}/read`, `POST alerts/read-all`: leitura de alertas.
- `approvals-rafael`, `POST approvals-rafael/lots/{id}/approve`, `POST approvals-rafael/payments/{id}/approve` e `POST .../reject`: aprovações com regras próprias de autorização.
- `GET /api/v1/gps/positions/{trackerId?}`: última posição por tracker, exige `vehicle_access`.

### Oficina e aplicação móvel (`/api/mobile`)

- `auth/login`, `auth/me`, `auth/logout`: sessão por token.
- `workshop/repairs`: consulta, detalhe, atualização, início/fim de reparação, início/fim de trabalho, peças, assinaturas e media.
- `workshop/painting-jobs`: consulta, detalhe, atualização e conclusão.
- `workshop/vehicles`, `workshop/garage-vehicles`, criação de intervenções de veículos.
- `workshop/planning`: agenda, tipos, mecânicos e CRUD de intervenções, mais ações `start`, `finish` e `complete`.
- `workshop/part-orders` e `workshop/part-order-suppliers`: encomendas, itens e fornecedores.

### Conversas (`/api/chat`)

Listar, consultar, assumir, libertar e fechar conversas. A consulta exige `chat_conversation_access`/`chat_conversation_show`; as ações exigem `chat_conversation_edit`.

### Conta e recuperação de acesso

- `GET /api/v1/profile`, `PUT /api/v1/profile`: consultar e atualizar nome/email do próprio utilizador. `PUT /api/v1/profile/password` aceita `current_password`, `password` e `password_confirmation`. Exigem `profile_password_edit`.
- `DELETE /api/v1/profile`: elimina a própria conta e revoga os seus tokens; exige `password` e a permissão `profile_password_edit`.
- `POST /api/v1/auth/forgot-password` com `email`: pede o email de recuperação. `POST /api/v1/auth/reset-password` aceita `email`, `token`, `password` e `password_confirmation`. A reposição revoga os tokens anteriores.
- `GET /api/v1/lead-access/{token}` e `GET /api/v1/lead-access/{token}/contact/{call|whatsapp}` reproduzem o acesso por link às leads, com as mesmas regras de prazo e registo de contacto da interface web. Este `token` é o token temporário da lead, diferente do token Bearer da integração; consultar o primeiro endpoint consome a abertura inicial do link.

O inventário completo, com método e caminho exatos, está em [api-endpoints.md](api-endpoints.md). As rotas `meta/*` e `whatsapp/*` são webhooks ou interfaces internas com serviços externos; não são a API de integração geral e usam autenticação própria. Regenerar o OpenAPI com `php scripts/generate-openapi.php` depois de alterar as rotas.

## Disponibilização

O código disponibiliza todas as rotas do backoffice à conta de integração administrativa, além das APIs nativas, perfil, recuperação de acesso e links temporários de leads. Não inclui um token real no repositório. A criação da conta, a aplicação deste código no servidor e a entrega segura do token ao programador são passos de operação no ambiente instalado.

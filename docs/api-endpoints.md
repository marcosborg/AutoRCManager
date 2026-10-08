# Inventário de endpoints HTTP

Gerado a partir das rotas Laravel. Voltar a gerar com `php scripts/generate-api-endpoints.php` após alterar `routes/api.php`.

Base: `/api`. Consultar [api-integracao.md](api-integracao.md) para autenticação, permissões, formatos e limites.

## chat

| Método | Caminho |
| --- | --- |
| `GET` | `/api/chat/conversations` |
| `GET` | `/api/chat/conversations/{conversation}` |
| `POST` | `/api/chat/conversations/{conversation}/close` |
| `POST` | `/api/chat/conversations/{conversation}/release` |
| `POST` | `/api/chat/conversations/{conversation}/takeover` |

## meta

| Método | Caminho |
| --- | --- |
| `POST` | `/api/meta/leads/inbound` |
| `GET` | `/api/meta/webhook` |
| `POST` | `/api/meta/webhook` |

## mobile

| Método | Caminho |
| --- | --- |
| `POST` | `/api/mobile/auth/login` |
| `POST` | `/api/mobile/auth/logout` |
| `GET` | `/api/mobile/auth/me` |
| `GET` | `/api/mobile/workshop/garage-vehicles` |
| `GET` | `/api/mobile/workshop/painting-jobs` |
| `GET` | `/api/mobile/workshop/painting-jobs/{paintingJob}` |
| `PUT` | `/api/mobile/workshop/painting-jobs/{paintingJob}` |
| `POST` | `/api/mobile/workshop/painting-jobs/{paintingJob}/complete` |
| `GET` | `/api/mobile/workshop/part-order-suppliers` |
| `POST` | `/api/mobile/workshop/part-order-suppliers` |
| `GET` | `/api/mobile/workshop/part-orders` |
| `POST` | `/api/mobile/workshop/part-orders` |
| `GET` | `/api/mobile/workshop/part-orders/{partOrder}` |
| `POST` | `/api/mobile/workshop/part-orders/{partOrder}/items` |
| `PATCH` | `/api/mobile/workshop/part-orders/{partOrder}/items/{item}` |
| `DELETE` | `/api/mobile/workshop/part-orders/{partOrder}/items/{item}` |
| `GET` | `/api/mobile/workshop/planning/interventions` |
| `POST` | `/api/mobile/workshop/planning/interventions` |
| `GET` | `/api/mobile/workshop/planning/interventions/{workshopIntervention}` |
| `PUT` | `/api/mobile/workshop/planning/interventions/{workshopIntervention}` |
| `DELETE` | `/api/mobile/workshop/planning/interventions/{workshopIntervention}` |
| `POST` | `/api/mobile/workshop/planning/interventions/{workshopIntervention}/complete` |
| `POST` | `/api/mobile/workshop/planning/interventions/{workshopIntervention}/finish` |
| `POST` | `/api/mobile/workshop/planning/interventions/{workshopIntervention}/start` |
| `GET` | `/api/mobile/workshop/planning/mechanics` |
| `GET` | `/api/mobile/workshop/planning/my-agenda` |
| `GET` | `/api/mobile/workshop/planning/types` |
| `POST` | `/api/mobile/workshop/planning/types` |
| `PUT` | `/api/mobile/workshop/planning/types/{workshopInterventionType}` |
| `DELETE` | `/api/mobile/workshop/planning/types/{workshopInterventionType}` |
| `GET` | `/api/mobile/workshop/repair-states` |
| `GET` | `/api/mobile/workshop/repairs` |
| `GET` | `/api/mobile/workshop/repairs/{repair}` |
| `PUT` | `/api/mobile/workshop/repairs/{repair}` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/finish` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/media` |
| `DELETE` | `/api/mobile/workshop/repairs/{repair}/media/{mediaId}` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/parts` |
| `PATCH` | `/api/mobile/workshop/repairs/{repair}/parts/{part}` |
| `DELETE` | `/api/mobile/workshop/repairs/{repair}/parts/{part}` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/signatures` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/start` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/work/finish` |
| `POST` | `/api/mobile/workshop/repairs/{repair}/work/start` |
| `GET` | `/api/mobile/workshop/vehicles` |
| `POST` | `/api/mobile/workshop/vehicles/{vehicle}/interventions` |

## v1/auth

| Método | Caminho |
| --- | --- |
| `POST` | `/api/v1/auth/forgot-password` |
| `POST` | `/api/v1/auth/reset-password` |

## v1/backoffice

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/backoffice` |
| `GET` | `/api/v1/backoffice/ai-assistants` |
| `POST` | `/api/v1/backoffice/ai-assistants` |
| `GET` | `/api/v1/backoffice/ai-assistants/create` |
| `GET` | `/api/v1/backoffice/ai-assistants/{ai_assistant}` |
| `PUT|PATCH` | `/api/v1/backoffice/ai-assistants/{ai_assistant}` |
| `DELETE` | `/api/v1/backoffice/ai-assistants/{ai_assistant}` |
| `GET` | `/api/v1/backoffice/ai-assistants/{ai_assistant}/edit` |
| `GET` | `/api/v1/backoffice/ai-training-contents` |
| `POST` | `/api/v1/backoffice/ai-training-contents` |
| `GET` | `/api/v1/backoffice/ai-training-contents/create` |
| `GET` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}` |
| `PUT|PATCH` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}` |
| `DELETE` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}` |
| `GET` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}/edit` |
| `GET` | `/api/v1/backoffice/appreciations` |
| `POST` | `/api/v1/backoffice/appreciations` |
| `GET` | `/api/v1/backoffice/appreciations/create` |
| `DELETE` | `/api/v1/backoffice/appreciations/destroy` |
| `POST` | `/api/v1/backoffice/appreciations/parse-csv-import` |
| `POST` | `/api/v1/backoffice/appreciations/process-csv-import` |
| `GET` | `/api/v1/backoffice/appreciations/{appreciation}` |
| `PUT|PATCH` | `/api/v1/backoffice/appreciations/{appreciation}` |
| `DELETE` | `/api/v1/backoffice/appreciations/{appreciation}` |
| `GET` | `/api/v1/backoffice/appreciations/{appreciation}/edit` |
| `GET` | `/api/v1/backoffice/approvals` |
| `GET` | `/api/v1/backoffice/audit-logs` |
| `GET` | `/api/v1/backoffice/audit-logs/{audit_log}` |
| `GET` | `/api/v1/backoffice/brands` |
| `POST` | `/api/v1/backoffice/brands` |
| `GET` | `/api/v1/backoffice/brands/create` |
| `DELETE` | `/api/v1/backoffice/brands/destroy` |
| `POST` | `/api/v1/backoffice/brands/parse-csv-import` |
| `POST` | `/api/v1/backoffice/brands/process-csv-import` |
| `GET` | `/api/v1/backoffice/brands/{brand}` |
| `PUT|PATCH` | `/api/v1/backoffice/brands/{brand}` |
| `DELETE` | `/api/v1/backoffice/brands/{brand}` |
| `GET` | `/api/v1/backoffice/brands/{brand}/edit` |
| `GET` | `/api/v1/backoffice/carriers` |
| `POST` | `/api/v1/backoffice/carriers` |
| `GET` | `/api/v1/backoffice/carriers/create` |
| `DELETE` | `/api/v1/backoffice/carriers/destroy` |
| `POST` | `/api/v1/backoffice/carriers/parse-csv-import` |
| `POST` | `/api/v1/backoffice/carriers/process-csv-import` |
| `GET` | `/api/v1/backoffice/carriers/{carrier}` |
| `PUT|PATCH` | `/api/v1/backoffice/carriers/{carrier}` |
| `DELETE` | `/api/v1/backoffice/carriers/{carrier}` |
| `GET` | `/api/v1/backoffice/carriers/{carrier}/edit` |
| `GET` | `/api/v1/backoffice/cash` |
| `POST` | `/api/v1/backoffice/cash/boxes` |
| `POST` | `/api/v1/backoffice/cash/categories` |
| `POST` | `/api/v1/backoffice/cash/departments` |
| `POST` | `/api/v1/backoffice/cash/movements` |
| `POST` | `/api/v1/backoffice/cash/movements/{operation}/accounted` |
| `POST` | `/api/v1/backoffice/cash/transfers` |
| `GET` | `/api/v1/backoffice/chat-conversations` |
| `POST` | `/api/v1/backoffice/chat-conversations/{chatConversation}/close` |
| `POST` | `/api/v1/backoffice/chat-conversations/{chatConversation}/release` |
| `POST` | `/api/v1/backoffice/chat-conversations/{chatConversation}/takeover` |
| `GET` | `/api/v1/backoffice/chat-conversations/{chat_conversation}` |
| `GET` | `/api/v1/backoffice/chat-leads` |
| `POST` | `/api/v1/backoffice/chat-leads` |
| `GET` | `/api/v1/backoffice/chat-leads/create` |
| `GET` | `/api/v1/backoffice/chat-leads/{chat_lead}` |
| `PUT|PATCH` | `/api/v1/backoffice/chat-leads/{chat_lead}` |
| `DELETE` | `/api/v1/backoffice/chat-leads/{chat_lead}` |
| `GET` | `/api/v1/backoffice/chat-leads/{chat_lead}/edit` |
| `GET` | `/api/v1/backoffice/clients` |
| `POST` | `/api/v1/backoffice/clients` |
| `GET` | `/api/v1/backoffice/clients/create` |
| `DELETE` | `/api/v1/backoffice/clients/destroy` |
| `GET` | `/api/v1/backoffice/clients/{client}` |
| `PUT|PATCH` | `/api/v1/backoffice/clients/{client}` |
| `DELETE` | `/api/v1/backoffice/clients/{client}` |
| `POST` | `/api/v1/backoffice/clients/{client}/charges` |
| `GET` | `/api/v1/backoffice/clients/{client}/edit` |
| `POST` | `/api/v1/backoffice/clients/{client}/payments` |
| `GET` | `/api/v1/backoffice/clients/{client}/payments/{payment}` |
| `GET` | `/api/v1/backoffice/clients/{client}/reconciliation` |
| `GET` | `/api/v1/backoffice/countries` |
| `POST` | `/api/v1/backoffice/countries` |
| `GET` | `/api/v1/backoffice/countries/create` |
| `DELETE` | `/api/v1/backoffice/countries/destroy` |
| `POST` | `/api/v1/backoffice/countries/parse-csv-import` |
| `POST` | `/api/v1/backoffice/countries/process-csv-import` |
| `GET` | `/api/v1/backoffice/countries/{country}` |
| `PUT|PATCH` | `/api/v1/backoffice/countries/{country}` |
| `DELETE` | `/api/v1/backoffice/countries/{country}` |
| `GET` | `/api/v1/backoffice/countries/{country}/edit` |
| `GET` | `/api/v1/backoffice/create-car-for-repairs` |
| `POST` | `/api/v1/backoffice/create-car-for-repairs` |
| `GET` | `/api/v1/backoffice/dashboard` |
| `GET` | `/api/v1/backoffice/depreciations` |
| `POST` | `/api/v1/backoffice/depreciations` |
| `GET` | `/api/v1/backoffice/depreciations/create` |
| `DELETE` | `/api/v1/backoffice/depreciations/destroy` |
| `POST` | `/api/v1/backoffice/depreciations/parse-csv-import` |
| `POST` | `/api/v1/backoffice/depreciations/process-csv-import` |
| `GET` | `/api/v1/backoffice/depreciations/{depreciation}` |
| `PUT|PATCH` | `/api/v1/backoffice/depreciations/{depreciation}` |
| `DELETE` | `/api/v1/backoffice/depreciations/{depreciation}` |
| `GET` | `/api/v1/backoffice/depreciations/{depreciation}/edit` |
| `GET` | `/api/v1/backoffice/external-services` |
| `POST` | `/api/v1/backoffice/external-services` |
| `GET` | `/api/v1/backoffice/external-services/create` |
| `PUT|PATCH` | `/api/v1/backoffice/external-services/{external_service}` |
| `DELETE` | `/api/v1/backoffice/external-services/{external_service}` |
| `GET` | `/api/v1/backoffice/external-services/{external_service}/edit` |
| `POST` | `/api/v1/backoffice/financial-institutions` |
| `GET` | `/api/v1/backoffice/general-states` |
| `POST` | `/api/v1/backoffice/general-states` |
| `POST` | `/api/v1/backoffice/general-states/ckmedia` |
| `GET` | `/api/v1/backoffice/general-states/create` |
| `DELETE` | `/api/v1/backoffice/general-states/destroy` |
| `POST` | `/api/v1/backoffice/general-states/media` |
| `POST` | `/api/v1/backoffice/general-states/reorder` |
| `GET` | `/api/v1/backoffice/general-states/{general_state}` |
| `PUT|PATCH` | `/api/v1/backoffice/general-states/{general_state}` |
| `DELETE` | `/api/v1/backoffice/general-states/{general_state}` |
| `GET` | `/api/v1/backoffice/general-states/{general_state}/edit` |
| `GET` | `/api/v1/backoffice/global-search` |
| `GET` | `/api/v1/backoffice/gps-positions` |
| `GET` | `/api/v1/backoffice/import-configuration` |
| `PUT` | `/api/v1/backoffice/import-configuration/tolls-recipient` |
| `GET` | `/api/v1/backoffice/iuc-due/export` |
| `GET` | `/api/v1/backoffice/leads` |
| `GET` | `/api/v1/backoffice/leads-performance` |
| `GET` | `/api/v1/backoffice/leads-performance/pdf` |
| `GET` | `/api/v1/backoffice/leads/export/pdf` |
| `GET` | `/api/v1/backoffice/leads/{lead}` |
| `PUT|PATCH` | `/api/v1/backoffice/leads/{lead}` |
| `DELETE` | `/api/v1/backoffice/leads/{lead}` |
| `GET` | `/api/v1/backoffice/leads/{lead}/edit` |
| `POST` | `/api/v1/backoffice/leads/{lead}/notes` |
| `DELETE` | `/api/v1/backoffice/leads/{lead}/notes/{note}` |
| `GET` | `/api/v1/backoffice/messenger` |
| `POST` | `/api/v1/backoffice/messenger` |
| `GET` | `/api/v1/backoffice/messenger/create` |
| `GET` | `/api/v1/backoffice/messenger/inbox` |
| `GET` | `/api/v1/backoffice/messenger/outbox` |
| `GET` | `/api/v1/backoffice/messenger/{topic}` |
| `DELETE` | `/api/v1/backoffice/messenger/{topic}` |
| `POST` | `/api/v1/backoffice/messenger/{topic}/reply` |
| `GET` | `/api/v1/backoffice/messenger/{topic}/reply` |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes` |
| `POST` | `/api/v1/backoffice/oficina-expertise-processes` |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes/create` |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}` |
| `PUT|PATCH` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}` |
| `DELETE` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}` |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}/edit` |
| `PATCH` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}/status` |
| `GET` | `/api/v1/backoffice/painting-jobs` |
| `POST` | `/api/v1/backoffice/painting-jobs` |
| `GET` | `/api/v1/backoffice/painting-jobs/create` |
| `GET` | `/api/v1/backoffice/painting-jobs/{paintingJob}` |
| `PUT|PATCH` | `/api/v1/backoffice/painting-jobs/{paintingJob}` |
| `POST` | `/api/v1/backoffice/painting-jobs/{paintingJob}/complete` |
| `GET` | `/api/v1/backoffice/painting-jobs/{paintingJob}/edit` |
| `POST` | `/api/v1/backoffice/painting-jobs/{paintingJob}/reopen` |
| `GET` | `/api/v1/backoffice/part-orders` |
| `POST` | `/api/v1/backoffice/part-orders` |
| `GET` | `/api/v1/backoffice/part-orders/create` |
| `POST` | `/api/v1/backoffice/part-orders/{partOrder}/items/{item}/quotes` |
| `POST` | `/api/v1/backoffice/part-orders/{partOrder}/items/{item}/quotes/{quote}/select` |
| `GET` | `/api/v1/backoffice/part-orders/{part_order}` |
| `PUT|PATCH` | `/api/v1/backoffice/part-orders/{part_order}` |
| `DELETE` | `/api/v1/backoffice/part-orders/{part_order}` |
| `GET` | `/api/v1/backoffice/part-orders/{part_order}/edit` |
| `GET` | `/api/v1/backoffice/part-payments` |
| `POST` | `/api/v1/backoffice/part-payments` |
| `GET` | `/api/v1/backoffice/part-payments/create` |
| `GET` | `/api/v1/backoffice/part-payments/{part_payment}` |
| `PUT|PATCH` | `/api/v1/backoffice/part-payments/{part_payment}` |
| `DELETE` | `/api/v1/backoffice/part-payments/{part_payment}` |
| `GET` | `/api/v1/backoffice/part-payments/{part_payment}/edit` |
| `GET` | `/api/v1/backoffice/part-receipts` |
| `POST` | `/api/v1/backoffice/part-receipts` |
| `GET` | `/api/v1/backoffice/part-receipts/create` |
| `GET` | `/api/v1/backoffice/part-receipts/{part_receipt}` |
| `PUT|PATCH` | `/api/v1/backoffice/part-receipts/{part_receipt}` |
| `DELETE` | `/api/v1/backoffice/part-receipts/{part_receipt}` |
| `GET` | `/api/v1/backoffice/part-receipts/{part_receipt}/edit` |
| `GET` | `/api/v1/backoffice/payment-methods` |
| `POST` | `/api/v1/backoffice/payment-methods` |
| `GET` | `/api/v1/backoffice/payment-methods/create` |
| `DELETE` | `/api/v1/backoffice/payment-methods/destroy` |
| `GET` | `/api/v1/backoffice/payment-methods/{payment_method}` |
| `PUT|PATCH` | `/api/v1/backoffice/payment-methods/{payment_method}` |
| `DELETE` | `/api/v1/backoffice/payment-methods/{payment_method}` |
| `GET` | `/api/v1/backoffice/payment-methods/{payment_method}/edit` |
| `GET` | `/api/v1/backoffice/payment-statuses` |
| `POST` | `/api/v1/backoffice/payment-statuses` |
| `GET` | `/api/v1/backoffice/payment-statuses/create` |
| `DELETE` | `/api/v1/backoffice/payment-statuses/destroy` |
| `POST` | `/api/v1/backoffice/payment-statuses/parse-csv-import` |
| `POST` | `/api/v1/backoffice/payment-statuses/process-csv-import` |
| `GET` | `/api/v1/backoffice/payment-statuses/{payment_status}` |
| `PUT|PATCH` | `/api/v1/backoffice/payment-statuses/{payment_status}` |
| `DELETE` | `/api/v1/backoffice/payment-statuses/{payment_status}` |
| `GET` | `/api/v1/backoffice/payment-statuses/{payment_status}/edit` |
| `GET` | `/api/v1/backoffice/permissions` |
| `POST` | `/api/v1/backoffice/permissions` |
| `GET` | `/api/v1/backoffice/permissions/create` |
| `DELETE` | `/api/v1/backoffice/permissions/destroy` |
| `POST` | `/api/v1/backoffice/permissions/parse-csv-import` |
| `POST` | `/api/v1/backoffice/permissions/process-csv-import` |
| `GET` | `/api/v1/backoffice/permissions/{permission}` |
| `PUT|PATCH` | `/api/v1/backoffice/permissions/{permission}` |
| `DELETE` | `/api/v1/backoffice/permissions/{permission}` |
| `GET` | `/api/v1/backoffice/permissions/{permission}/edit` |
| `GET` | `/api/v1/backoffice/pickup-states` |
| `POST` | `/api/v1/backoffice/pickup-states` |
| `GET` | `/api/v1/backoffice/pickup-states/create` |
| `DELETE` | `/api/v1/backoffice/pickup-states/destroy` |
| `POST` | `/api/v1/backoffice/pickup-states/parse-csv-import` |
| `POST` | `/api/v1/backoffice/pickup-states/process-csv-import` |
| `GET` | `/api/v1/backoffice/pickup-states/{pickup_state}` |
| `PUT|PATCH` | `/api/v1/backoffice/pickup-states/{pickup_state}` |
| `DELETE` | `/api/v1/backoffice/pickup-states/{pickup_state}` |
| `GET` | `/api/v1/backoffice/pickup-states/{pickup_state}/edit` |
| `POST` | `/api/v1/backoffice/proveniences` |
| `GET` | `/api/v1/backoffice/repair-parts-report` |
| `GET` | `/api/v1/backoffice/repair-states` |
| `POST` | `/api/v1/backoffice/repair-states` |
| `GET` | `/api/v1/backoffice/repair-states/create` |
| `DELETE` | `/api/v1/backoffice/repair-states/destroy` |
| `POST` | `/api/v1/backoffice/repair-states/parse-csv-import` |
| `POST` | `/api/v1/backoffice/repair-states/process-csv-import` |
| `GET` | `/api/v1/backoffice/repair-states/{repair_state}` |
| `PUT|PATCH` | `/api/v1/backoffice/repair-states/{repair_state}` |
| `DELETE` | `/api/v1/backoffice/repair-states/{repair_state}` |
| `GET` | `/api/v1/backoffice/repair-states/{repair_state}/edit` |
| `GET` | `/api/v1/backoffice/repairs` |
| `POST` | `/api/v1/backoffice/repairs` |
| `POST` | `/api/v1/backoffice/repairs/ckmedia` |
| `GET` | `/api/v1/backoffice/repairs/create` |
| `DELETE` | `/api/v1/backoffice/repairs/destroy` |
| `POST` | `/api/v1/backoffice/repairs/media` |
| `POST` | `/api/v1/backoffice/repairs/parse-csv-import` |
| `POST` | `/api/v1/backoffice/repairs/process-csv-import` |
| `GET` | `/api/v1/backoffice/repairs/{repair}` |
| `PUT|PATCH` | `/api/v1/backoffice/repairs/{repair}` |
| `DELETE` | `/api/v1/backoffice/repairs/{repair}` |
| `GET` | `/api/v1/backoffice/repairs/{repair}/edit` |
| `POST` | `/api/v1/backoffice/repairs/{repair}/finish` |
| `POST` | `/api/v1/backoffice/repairs/{repair}/new-intervention` |
| `POST` | `/api/v1/backoffice/repairs/{repair}/reopen` |
| `POST` | `/api/v1/backoffice/repairs/{repair}/start` |
| `POST` | `/api/v1/backoffice/repairs/{repair}/work/finish` |
| `POST` | `/api/v1/backoffice/repairs/{repair}/work/start` |
| `POST` | `/api/v1/backoffice/role-preview` |
| `DELETE` | `/api/v1/backoffice/role-preview` |
| `GET` | `/api/v1/backoffice/roles` |
| `POST` | `/api/v1/backoffice/roles` |
| `GET` | `/api/v1/backoffice/roles/create` |
| `DELETE` | `/api/v1/backoffice/roles/destroy` |
| `POST` | `/api/v1/backoffice/roles/parse-csv-import` |
| `POST` | `/api/v1/backoffice/roles/process-csv-import` |
| `GET` | `/api/v1/backoffice/roles/{role}` |
| `PUT|PATCH` | `/api/v1/backoffice/roles/{role}` |
| `DELETE` | `/api/v1/backoffice/roles/{role}` |
| `GET` | `/api/v1/backoffice/roles/{role}/edit` |
| `GET` | `/api/v1/backoffice/sale-closure-approvals` |
| `GET` | `/api/v1/backoffice/sale-closure-approvals/export` |
| `POST` | `/api/v1/backoffice/sale-closure-approvals/{approval}/approve` |
| `POST` | `/api/v1/backoffice/sale-closure-approvals/{approval}/reject` |
| `GET` | `/api/v1/backoffice/sales/create` |
| `GET` | `/api/v1/backoffice/sales/{general_state_id?}` |
| `GET` | `/api/v1/backoffice/stand-cash-payment-approvals` |
| `POST` | `/api/v1/backoffice/stand-cash-payment-approvals/{approval}/approve` |
| `POST` | `/api/v1/backoffice/stand-cash-payment-approvals/{approval}/reject` |
| `GET` | `/api/v1/backoffice/supliers` |
| `POST` | `/api/v1/backoffice/supliers` |
| `GET` | `/api/v1/backoffice/supliers/create` |
| `DELETE` | `/api/v1/backoffice/supliers/destroy` |
| `POST` | `/api/v1/backoffice/supliers/parse-csv-import` |
| `POST` | `/api/v1/backoffice/supliers/process-csv-import` |
| `GET` | `/api/v1/backoffice/supliers/{suplier}` |
| `PUT|PATCH` | `/api/v1/backoffice/supliers/{suplier}` |
| `DELETE` | `/api/v1/backoffice/supliers/{suplier}` |
| `GET` | `/api/v1/backoffice/supliers/{suplier}/edit` |
| `GET` | `/api/v1/backoffice/system-calendar` |
| `POST` | `/api/v1/backoffice/system-calendar/tasks` |
| `DELETE` | `/api/v1/backoffice/system-calendar/tasks/{task}` |
| `POST` | `/api/v1/backoffice/system-calendar/tasks/{task}/complete` |
| `GET` | `/api/v1/backoffice/system-maintenance` |
| `POST` | `/api/v1/backoffice/system-maintenance/resend-lead-notifications` |
| `POST` | `/api/v1/backoffice/system-maintenance/run` |
| `POST` | `/api/v1/backoffice/system-shutdown` |
| `GET` | `/api/v1/backoffice/users` |
| `POST` | `/api/v1/backoffice/users` |
| `GET` | `/api/v1/backoffice/users/create` |
| `DELETE` | `/api/v1/backoffice/users/destroy` |
| `POST` | `/api/v1/backoffice/users/parse-csv-import` |
| `POST` | `/api/v1/backoffice/users/process-csv-import` |
| `GET` | `/api/v1/backoffice/users/{user}` |
| `PUT|PATCH` | `/api/v1/backoffice/users/{user}` |
| `DELETE` | `/api/v1/backoffice/users/{user}` |
| `GET` | `/api/v1/backoffice/users/{user}/edit` |
| `GET` | `/api/v1/backoffice/vehicle-consignments` |
| `POST` | `/api/v1/backoffice/vehicle-consignments` |
| `GET` | `/api/v1/backoffice/vehicle-consignments-history` |
| `GET` | `/api/v1/backoffice/vehicle-consignments/create` |
| `GET` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}` |
| `PUT|PATCH` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}` |
| `DELETE` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}` |
| `GET` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}/edit` |
| `GET` | `/api/v1/backoffice/vehicle-groups` |
| `POST` | `/api/v1/backoffice/vehicle-groups` |
| `GET` | `/api/v1/backoffice/vehicle-groups/create` |
| `DELETE` | `/api/v1/backoffice/vehicle-groups/destroy` |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/approve` |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/payments` |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/payments/{payment}/approve` |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/payments/{payment}/reject` |
| `GET` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}` |
| `PUT|PATCH` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}` |
| `DELETE` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}` |
| `GET` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}/edit` |
| `GET` | `/api/v1/backoffice/vehicle-state-transfers` |
| `POST` | `/api/v1/backoffice/vehicle-state-transfers/{transfer}/check` |
| `GET` | `/api/v1/backoffice/vehicle-trade-ins` |
| `POST` | `/api/v1/backoffice/vehicle-trade-ins` |
| `GET` | `/api/v1/backoffice/vehicle-trade-ins/create` |
| `GET` | `/api/v1/backoffice/vehicle-trade-ins/pending` |
| `POST` | `/api/v1/backoffice/vehicle-trade-ins/{tradeIn}/convert` |
| `POST` | `/api/v1/backoffice/vehicle-trade-ins/{tradeIn}/reject` |
| `GET` | `/api/v1/backoffice/vehicles` |
| `POST` | `/api/v1/backoffice/vehicles` |
| `GET` | `/api/v1/backoffice/vehicles-deleted` |
| `GET` | `/api/v1/backoffice/vehicles-deleted/{vehicle}` |
| `PUT` | `/api/v1/backoffice/vehicles-deleted/{vehicle}` |
| `GET` | `/api/v1/backoffice/vehicles-deleted/{vehicle}/edit` |
| `POST` | `/api/v1/backoffice/vehicles-deleted/{vehicle}/restore` |
| `POST` | `/api/v1/backoffice/vehicles/ckmedia` |
| `GET` | `/api/v1/backoffice/vehicles/create` |
| `DELETE` | `/api/v1/backoffice/vehicles/destroy` |
| `POST` | `/api/v1/backoffice/vehicles/media` |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}` |
| `PUT|PATCH` | `/api/v1/backoffice/vehicles/{vehicle}` |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}` |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/client-payments/{payment}` |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/edit` |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/generic-payments/{payment}` |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/notes` |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/notes` |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/notes/{note}` |
| `PATCH` | `/api/v1/backoffice/vehicles/{vehicle}/notes/{note}` |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/send-to-workshop` |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/start-intervention` |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/supplier-payments/{payment}` |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/suspended-sale` |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/suspended-sale` |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/timeline` |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/timeline/export/pdf` |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/trade-ins` |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/workshop` |
| `PATCH` | `/api/v1/backoffice/vehicles/{vehicle}/workshop-state` |
| `GET` | `/api/v1/backoffice/workshop-cash` |
| `POST` | `/api/v1/backoffice/workshop-cash/categories` |
| `PUT` | `/api/v1/backoffice/workshop-cash/categories/{cashCategory}` |
| `POST` | `/api/v1/backoffice/workshop-cash/expenses` |
| `POST` | `/api/v1/backoffice/workshop-cash/transfers` |
| `GET` | `/api/v1/backoffice/workshop-intervention-types` |
| `POST` | `/api/v1/backoffice/workshop-intervention-types` |
| `PUT|PATCH` | `/api/v1/backoffice/workshop-intervention-types/{workshopInterventionType}` |
| `DELETE` | `/api/v1/backoffice/workshop-intervention-types/{workshopInterventionType}` |
| `GET` | `/api/v1/backoffice/workshop-interventions` |
| `POST` | `/api/v1/backoffice/workshop-interventions` |
| `GET` | `/api/v1/backoffice/workshop-interventions/create` |
| `PUT|PATCH` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}` |
| `DELETE` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}` |
| `POST` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/complete` |
| `GET` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/edit` |
| `POST` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/finish` |
| `POST` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/start` |
| `GET` | `/api/v1/backoffice/workshop-states` |
| `POST` | `/api/v1/backoffice/workshop-states` |
| `PUT|PATCH` | `/api/v1/backoffice/workshop-states/{workshop_state}` |
| `DELETE` | `/api/v1/backoffice/workshop-states/{workshop_state}` |

## v1/brands

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/brands` |
| `POST` | `/api/v1/brands` |
| `GET` | `/api/v1/brands/{brand}` |
| `PUT|PATCH` | `/api/v1/brands/{brand}` |
| `DELETE` | `/api/v1/brands/{brand}` |

## v1/carriers

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/carriers` |
| `POST` | `/api/v1/carriers` |
| `GET` | `/api/v1/carriers/{carrier}` |
| `PUT|PATCH` | `/api/v1/carriers/{carrier}` |
| `DELETE` | `/api/v1/carriers/{carrier}` |

## v1/clients

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/clients` |
| `POST` | `/api/v1/clients` |
| `GET` | `/api/v1/clients/{client}` |
| `PUT|PATCH` | `/api/v1/clients/{client}` |
| `DELETE` | `/api/v1/clients/{client}` |

## v1/countries

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/countries` |
| `POST` | `/api/v1/countries` |
| `GET` | `/api/v1/countries/{country}` |
| `PUT|PATCH` | `/api/v1/countries/{country}` |
| `DELETE` | `/api/v1/countries/{country}` |

## v1/gestao

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/gestao/alerts` |
| `POST` | `/api/v1/gestao/alerts/read-all` |
| `POST` | `/api/v1/gestao/alerts/{alert}/read` |
| `GET` | `/api/v1/gestao/approvals-rafael` |
| `POST` | `/api/v1/gestao/approvals-rafael/lots/{vehicleGroup}/approve` |
| `POST` | `/api/v1/gestao/approvals-rafael/payments/{lotPayment}/approve` |
| `POST` | `/api/v1/gestao/approvals-rafael/payments/{lotPayment}/reject` |
| `GET` | `/api/v1/gestao/consignments` |
| `GET` | `/api/v1/gestao/dashboard` |
| `GET` | `/api/v1/gestao/leads` |
| `GET` | `/api/v1/gestao/leads/{lead}` |
| `GET` | `/api/v1/gestao/trade-ins` |
| `GET` | `/api/v1/gestao/vehicles` |
| `GET` | `/api/v1/gestao/vehicles/by-state` |
| `GET` | `/api/v1/gestao/vehicles/{vehicle}` |
| `GET` | `/api/v1/gestao/vehicles/{vehicle}/central-register` |
| `GET` | `/api/v1/gestao/workshop` |

## v1/gps

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/gps/positions/{trackerId?}` |

## v1/integration

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/integration/catalog/{resource}` |
| `GET` | `/api/v1/integration/catalog/{resource}/{id}` |
| `POST` | `/api/v1/integration/consignments` |
| `PUT` | `/api/v1/integration/consignments/{consignment}` |
| `DELETE` | `/api/v1/integration/consignments/{consignment}` |
| `GET` | `/api/v1/integration/tokens` |
| `POST` | `/api/v1/integration/tokens` |
| `DELETE` | `/api/v1/integration/tokens/{token}` |

## v1/lead-access

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/lead-access/{token}` |
| `GET` | `/api/v1/lead-access/{token}/contact/{channel}` |

## v1/payment-statuses

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/payment-statuses` |
| `POST` | `/api/v1/payment-statuses` |
| `GET` | `/api/v1/payment-statuses/{payment_status}` |
| `PUT|PATCH` | `/api/v1/payment-statuses/{payment_status}` |
| `DELETE` | `/api/v1/payment-statuses/{payment_status}` |

## v1/permissions

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/permissions` |
| `POST` | `/api/v1/permissions` |
| `GET` | `/api/v1/permissions/{permission}` |
| `PUT|PATCH` | `/api/v1/permissions/{permission}` |
| `DELETE` | `/api/v1/permissions/{permission}` |

## v1/pickup-states

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/pickup-states` |
| `POST` | `/api/v1/pickup-states` |
| `GET` | `/api/v1/pickup-states/{pickup_state}` |
| `PUT|PATCH` | `/api/v1/pickup-states/{pickup_state}` |
| `DELETE` | `/api/v1/pickup-states/{pickup_state}` |

## v1/profile

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/profile` |
| `PUT` | `/api/v1/profile` |
| `DELETE` | `/api/v1/profile` |
| `PUT` | `/api/v1/profile/password` |

## v1/repair-states

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/repair-states` |
| `POST` | `/api/v1/repair-states` |
| `GET` | `/api/v1/repair-states/{repair_state}` |
| `PUT|PATCH` | `/api/v1/repair-states/{repair_state}` |
| `DELETE` | `/api/v1/repair-states/{repair_state}` |

## v1/repairs

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/repairs` |
| `POST` | `/api/v1/repairs` |
| `GET` | `/api/v1/repairs/{repair}` |
| `PUT|PATCH` | `/api/v1/repairs/{repair}` |
| `DELETE` | `/api/v1/repairs/{repair}` |

## v1/roles

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/roles` |
| `POST` | `/api/v1/roles` |
| `GET` | `/api/v1/roles/{role}` |
| `PUT|PATCH` | `/api/v1/roles/{role}` |
| `DELETE` | `/api/v1/roles/{role}` |

## v1/supliers

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/supliers` |
| `POST` | `/api/v1/supliers` |
| `GET` | `/api/v1/supliers/{suplier}` |
| `PUT|PATCH` | `/api/v1/supliers/{suplier}` |
| `DELETE` | `/api/v1/supliers/{suplier}` |

## v1/users

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/users` |
| `POST` | `/api/v1/users` |
| `GET` | `/api/v1/users/{user}` |
| `PUT|PATCH` | `/api/v1/users/{user}` |
| `DELETE` | `/api/v1/users/{user}` |

## v1/vehicles

| Método | Caminho |
| --- | --- |
| `GET` | `/api/v1/vehicles` |
| `POST` | `/api/v1/vehicles` |
| `POST` | `/api/v1/vehicles/media` |
| `GET` | `/api/v1/vehicles/{vehicle}` |
| `PUT|PATCH` | `/api/v1/vehicles/{vehicle}` |
| `DELETE` | `/api/v1/vehicles/{vehicle}` |

## whatsapp

| Método | Caminho |
| --- | --- |
| `POST` | `/api/whatsapp/human-outgoing-message` |
| `POST` | `/api/whatsapp/incoming-message` |
| `GET` | `/api/whatsapp/lead-notifications` |
| `POST` | `/api/whatsapp/lead-notifications/{notification}/failed` |
| `POST` | `/api/whatsapp/lead-notifications/{notification}/sent` |
| `POST` | `/api/whatsapp/message-status` |
| `GET` | `/api/whatsapp/outgoing-messages` |
| `POST` | `/api/whatsapp/outgoing-messages/{message}/failed` |
| `POST` | `/api/whatsapp/outgoing-messages/{message}/sent` |
| `GET` | `/api/whatsapp/webhook` |
| `POST` | `/api/whatsapp/webhook` |

# Contratos do espelho do backoffice

Este índice aponta para a implementação e a validação efetivas de cada operação. Para os métodos com `Request` genérico, consultar as regras `validate(...)` no método indicado. Atualizar com `php scripts/generate-backoffice-contracts.php`.

| Método | Endpoint API | Implementação | Validação |
| --- | --- | --- | --- |
| `GET` | `/api/v1/backoffice` | [HomeController::index](../app/Http/Controllers/Admin/HomeController.php#L20) | — |
| `GET` | `/api/v1/backoffice/ai-assistants` | [AiAssistantController::index](../app/Http/Controllers/Admin/AiAssistantController.php#L14) | — |
| `POST` | `/api/v1/backoffice/ai-assistants` | [AiAssistantController::store](../app/Http/Controllers/Admin/AiAssistantController.php#L30) | [StoreAiAssistantRequest](../app/Http/Requests/StoreAiAssistantRequest.php) |
| `GET` | `/api/v1/backoffice/ai-assistants/create` | [AiAssistantController::create](../app/Http/Controllers/Admin/AiAssistantController.php#L23) | — |
| `GET` | `/api/v1/backoffice/ai-assistants/{ai_assistant}` | [AiAssistantController::show](../app/Http/Controllers/Admin/AiAssistantController.php#L37) | — |
| `PUT/PATCH` | `/api/v1/backoffice/ai-assistants/{ai_assistant}` | [AiAssistantController::update](../app/Http/Controllers/Admin/AiAssistantController.php#L53) | [UpdateAiAssistantRequest](../app/Http/Requests/UpdateAiAssistantRequest.php) |
| `DELETE` | `/api/v1/backoffice/ai-assistants/{ai_assistant}` | [AiAssistantController::destroy](../app/Http/Controllers/Admin/AiAssistantController.php#L60) | — |
| `GET` | `/api/v1/backoffice/ai-assistants/{ai_assistant}/edit` | [AiAssistantController::edit](../app/Http/Controllers/Admin/AiAssistantController.php#L46) | — |
| `GET` | `/api/v1/backoffice/ai-training-contents` | [AiTrainingContentController::index](../app/Http/Controllers/Admin/AiTrainingContentController.php#L15) | — |
| `POST` | `/api/v1/backoffice/ai-training-contents` | [AiTrainingContentController::store](../app/Http/Controllers/Admin/AiTrainingContentController.php#L34) | [StoreAiTrainingContentRequest](../app/Http/Requests/StoreAiTrainingContentRequest.php) |
| `GET` | `/api/v1/backoffice/ai-training-contents/create` | [AiTrainingContentController::create](../app/Http/Controllers/Admin/AiTrainingContentController.php#L24) | — |
| `GET` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}` | [AiTrainingContentController::show](../app/Http/Controllers/Admin/AiTrainingContentController.php#L41) | — |
| `PUT/PATCH` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}` | [AiTrainingContentController::update](../app/Http/Controllers/Admin/AiTrainingContentController.php#L58) | [UpdateAiTrainingContentRequest](../app/Http/Requests/UpdateAiTrainingContentRequest.php) |
| `DELETE` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}` | [AiTrainingContentController::destroy](../app/Http/Controllers/Admin/AiTrainingContentController.php#L65) | — |
| `GET` | `/api/v1/backoffice/ai-training-contents/{ai_training_content}/edit` | [AiTrainingContentController::edit](../app/Http/Controllers/Admin/AiTrainingContentController.php#L48) | — |
| `GET` | `/api/v1/backoffice/appreciations` | [AppreciationController::index](../app/Http/Controllers/Admin/AppreciationController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/appreciations` | [AppreciationController::store](../app/Http/Controllers/Admin/AppreciationController.php#L71) | [StoreAppreciationRequest](../app/Http/Requests/StoreAppreciationRequest.php) |
| `GET` | `/api/v1/backoffice/appreciations/create` | [AppreciationController::create](../app/Http/Controllers/Admin/AppreciationController.php#L64) | — |
| `DELETE` | `/api/v1/backoffice/appreciations/destroy` | [AppreciationController::massDestroy](../app/Http/Controllers/Admin/AppreciationController.php#L108) | [MassDestroyAppreciationRequest](../app/Http/Requests/MassDestroyAppreciationRequest.php) |
| `POST` | `/api/v1/backoffice/appreciations/parse-csv-import` | [AppreciationController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/appreciations/process-csv-import` | [AppreciationController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/appreciations/{appreciation}` | [AppreciationController::show](../app/Http/Controllers/Admin/AppreciationController.php#L92) | — |
| `PUT/PATCH` | `/api/v1/backoffice/appreciations/{appreciation}` | [AppreciationController::update](../app/Http/Controllers/Admin/AppreciationController.php#L85) | [UpdateAppreciationRequest](../app/Http/Requests/UpdateAppreciationRequest.php) |
| `DELETE` | `/api/v1/backoffice/appreciations/{appreciation}` | [AppreciationController::destroy](../app/Http/Controllers/Admin/AppreciationController.php#L99) | — |
| `GET` | `/api/v1/backoffice/appreciations/{appreciation}/edit` | [AppreciationController::edit](../app/Http/Controllers/Admin/AppreciationController.php#L78) | — |
| `GET` | `/api/v1/backoffice/approvals` | [ApprovalController::index](../app/Http/Controllers/Admin/ApprovalController.php#L13) | — |
| `GET` | `/api/v1/backoffice/audit-logs` | [AuditLogsController::index](../app/Http/Controllers/Admin/AuditLogsController.php#L14) | No controlador |
| `GET` | `/api/v1/backoffice/audit-logs/{audit_log}` | [AuditLogsController::show](../app/Http/Controllers/Admin/AuditLogsController.php#L67) | — |
| `GET` | `/api/v1/backoffice/brands` | [BrandController::index](../app/Http/Controllers/Admin/BrandController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/brands` | [BrandController::store](../app/Http/Controllers/Admin/BrandController.php#L68) | [StoreBrandRequest](../app/Http/Requests/StoreBrandRequest.php) |
| `GET` | `/api/v1/backoffice/brands/create` | [BrandController::create](../app/Http/Controllers/Admin/BrandController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/brands/destroy` | [BrandController::massDestroy](../app/Http/Controllers/Admin/BrandController.php#L105) | [MassDestroyBrandRequest](../app/Http/Requests/MassDestroyBrandRequest.php) |
| `POST` | `/api/v1/backoffice/brands/parse-csv-import` | [BrandController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/brands/process-csv-import` | [BrandController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/brands/{brand}` | [BrandController::show](../app/Http/Controllers/Admin/BrandController.php#L89) | — |
| `PUT/PATCH` | `/api/v1/backoffice/brands/{brand}` | [BrandController::update](../app/Http/Controllers/Admin/BrandController.php#L82) | [UpdateBrandRequest](../app/Http/Requests/UpdateBrandRequest.php) |
| `DELETE` | `/api/v1/backoffice/brands/{brand}` | [BrandController::destroy](../app/Http/Controllers/Admin/BrandController.php#L96) | — |
| `GET` | `/api/v1/backoffice/brands/{brand}/edit` | [BrandController::edit](../app/Http/Controllers/Admin/BrandController.php#L75) | — |
| `GET` | `/api/v1/backoffice/carriers` | [CarrierController::index](../app/Http/Controllers/Admin/CarrierController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/carriers` | [CarrierController::store](../app/Http/Controllers/Admin/CarrierController.php#L68) | [StoreCarrierRequest](../app/Http/Requests/StoreCarrierRequest.php) |
| `GET` | `/api/v1/backoffice/carriers/create` | [CarrierController::create](../app/Http/Controllers/Admin/CarrierController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/carriers/destroy` | [CarrierController::massDestroy](../app/Http/Controllers/Admin/CarrierController.php#L105) | [MassDestroyCarrierRequest](../app/Http/Requests/MassDestroyCarrierRequest.php) |
| `POST` | `/api/v1/backoffice/carriers/parse-csv-import` | [CarrierController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/carriers/process-csv-import` | [CarrierController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/carriers/{carrier}` | [CarrierController::show](../app/Http/Controllers/Admin/CarrierController.php#L89) | — |
| `PUT/PATCH` | `/api/v1/backoffice/carriers/{carrier}` | [CarrierController::update](../app/Http/Controllers/Admin/CarrierController.php#L82) | [UpdateCarrierRequest](../app/Http/Requests/UpdateCarrierRequest.php) |
| `DELETE` | `/api/v1/backoffice/carriers/{carrier}` | [CarrierController::destroy](../app/Http/Controllers/Admin/CarrierController.php#L96) | — |
| `GET` | `/api/v1/backoffice/carriers/{carrier}/edit` | [CarrierController::edit](../app/Http/Controllers/Admin/CarrierController.php#L75) | — |
| `GET` | `/api/v1/backoffice/cash` | [CashController::index](../app/Http/Controllers/Admin/CashController.php#L24) | No controlador |
| `POST` | `/api/v1/backoffice/cash/boxes` | [CashController::storeCashBox](../app/Http/Controllers/Admin/CashController.php#L199) | No controlador |
| `POST` | `/api/v1/backoffice/cash/categories` | [CashController::storeCategory](../app/Http/Controllers/Admin/CashController.php#L181) | No controlador |
| `POST` | `/api/v1/backoffice/cash/departments` | [CashController::storeDepartment](../app/Http/Controllers/Admin/CashController.php#L163) | No controlador |
| `POST` | `/api/v1/backoffice/cash/movements` | [CashController::store](../app/Http/Controllers/Admin/CashController.php#L46) | No controlador |
| `POST` | `/api/v1/backoffice/cash/movements/{operation}/accounted` | [CashController::toggleAccounted](../app/Http/Controllers/Admin/CashController.php#L146) | No controlador |
| `POST` | `/api/v1/backoffice/cash/transfers` | [CashController::transfer](../app/Http/Controllers/Admin/CashController.php#L109) | No controlador |
| `GET` | `/api/v1/backoffice/chat-conversations` | [ChatConversationController::index](../app/Http/Controllers/Admin/ChatConversationController.php#L13) | — |
| `POST` | `/api/v1/backoffice/chat-conversations/{chatConversation}/close` | [ChatConversationController::close](../app/Http/Controllers/Admin/ChatConversationController.php#L51) | — |
| `POST` | `/api/v1/backoffice/chat-conversations/{chatConversation}/release` | [ChatConversationController::release](../app/Http/Controllers/Admin/ChatConversationController.php#L42) | — |
| `POST` | `/api/v1/backoffice/chat-conversations/{chatConversation}/takeover` | [ChatConversationController::takeover](../app/Http/Controllers/Admin/ChatConversationController.php#L33) | — |
| `GET` | `/api/v1/backoffice/chat-conversations/{chat_conversation}` | [ChatConversationController::show](../app/Http/Controllers/Admin/ChatConversationController.php#L24) | — |
| `GET` | `/api/v1/backoffice/chat-leads` | [ChatLeadController::index](../app/Http/Controllers/Admin/ChatLeadController.php#L16) | — |
| `POST` | `/api/v1/backoffice/chat-leads` | [ChatLeadController::store](../app/Http/Controllers/Admin/ChatLeadController.php#L38) | [StoreChatLeadRequest](../app/Http/Requests/StoreChatLeadRequest.php) |
| `GET` | `/api/v1/backoffice/chat-leads/create` | [ChatLeadController::create](../app/Http/Controllers/Admin/ChatLeadController.php#L25) | — |
| `GET` | `/api/v1/backoffice/chat-leads/{chat_lead}` | [ChatLeadController::show](../app/Http/Controllers/Admin/ChatLeadController.php#L48) | — |
| `PUT/PATCH` | `/api/v1/backoffice/chat-leads/{chat_lead}` | [ChatLeadController::update](../app/Http/Controllers/Admin/ChatLeadController.php#L69) | [UpdateChatLeadRequest](../app/Http/Requests/UpdateChatLeadRequest.php) |
| `DELETE` | `/api/v1/backoffice/chat-leads/{chat_lead}` | [ChatLeadController::destroy](../app/Http/Controllers/Admin/ChatLeadController.php#L79) | — |
| `GET` | `/api/v1/backoffice/chat-leads/{chat_lead}/edit` | [ChatLeadController::edit](../app/Http/Controllers/Admin/ChatLeadController.php#L57) | — |
| `GET` | `/api/v1/backoffice/clients` | [ClientController::index](../app/Http/Controllers/Admin/ClientController.php#L28) | No controlador |
| `POST` | `/api/v1/backoffice/clients` | [ClientController::store](../app/Http/Controllers/Admin/ClientController.php#L134) | [StoreClientRequest](../app/Http/Requests/StoreClientRequest.php) |
| `GET` | `/api/v1/backoffice/clients/create` | [ClientController::create](../app/Http/Controllers/Admin/ClientController.php#L121) | — |
| `DELETE` | `/api/v1/backoffice/clients/destroy` | [ClientController::massDestroy](../app/Http/Controllers/Admin/ClientController.php#L525) | [MassDestroyClientRequest](../app/Http/Requests/MassDestroyClientRequest.php) |
| `GET` | `/api/v1/backoffice/clients/{client}` | [ClientController::show](../app/Http/Controllers/Admin/ClientController.php#L292) | — |
| `PUT/PATCH` | `/api/v1/backoffice/clients/{client}` | [ClientController::update](../app/Http/Controllers/Admin/ClientController.php#L196) | [UpdateClientRequest](../app/Http/Requests/UpdateClientRequest.php) |
| `DELETE` | `/api/v1/backoffice/clients/{client}` | [ClientController::destroy](../app/Http/Controllers/Admin/ClientController.php#L516) | — |
| `POST` | `/api/v1/backoffice/clients/{client}/charges` | [ClientController::storeCharge](../app/Http/Controllers/Admin/ClientController.php#L269) | No controlador |
| `GET` | `/api/v1/backoffice/clients/{client}/edit` | [ClientController::edit](../app/Http/Controllers/Admin/ClientController.php#L148) | — |
| `POST` | `/api/v1/backoffice/clients/{client}/payments` | [ClientController::storePayment](../app/Http/Controllers/Admin/ClientController.php#L203) | No controlador |
| `GET` | `/api/v1/backoffice/clients/{client}/payments/{payment}` | [ClientController::showPayment](../app/Http/Controllers/Admin/ClientController.php#L259) | — |
| `GET` | `/api/v1/backoffice/clients/{client}/reconciliation` | [ClientController::reconciliation](../app/Http/Controllers/Admin/ClientController.php#L314) | — |
| `GET` | `/api/v1/backoffice/countries` | [CountriesController::index](../app/Http/Controllers/Admin/CountriesController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/countries` | [CountriesController::store](../app/Http/Controllers/Admin/CountriesController.php#L71) | [StoreCountryRequest](../app/Http/Requests/StoreCountryRequest.php) |
| `GET` | `/api/v1/backoffice/countries/create` | [CountriesController::create](../app/Http/Controllers/Admin/CountriesController.php#L64) | — |
| `DELETE` | `/api/v1/backoffice/countries/destroy` | [CountriesController::massDestroy](../app/Http/Controllers/Admin/CountriesController.php#L108) | [MassDestroyCountryRequest](../app/Http/Requests/MassDestroyCountryRequest.php) |
| `POST` | `/api/v1/backoffice/countries/parse-csv-import` | [CountriesController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/countries/process-csv-import` | [CountriesController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/countries/{country}` | [CountriesController::show](../app/Http/Controllers/Admin/CountriesController.php#L92) | — |
| `PUT/PATCH` | `/api/v1/backoffice/countries/{country}` | [CountriesController::update](../app/Http/Controllers/Admin/CountriesController.php#L85) | [UpdateCountryRequest](../app/Http/Requests/UpdateCountryRequest.php) |
| `DELETE` | `/api/v1/backoffice/countries/{country}` | [CountriesController::destroy](../app/Http/Controllers/Admin/CountriesController.php#L99) | — |
| `GET` | `/api/v1/backoffice/countries/{country}/edit` | [CountriesController::edit](../app/Http/Controllers/Admin/CountriesController.php#L78) | — |
| `GET` | `/api/v1/backoffice/create-car-for-repairs` | [CreateCarForRepairController::index](../app/Http/Controllers/Admin/CreateCarForRepairController.php#L21) | — |
| `POST` | `/api/v1/backoffice/create-car-for-repairs` | [CreateCarForRepairController::store](../app/Http/Controllers/Admin/CreateCarForRepairController.php#L44) | [StoreVehicleRequest](../app/Http/Requests/StoreVehicleRequest.php) |
| `GET` | `/api/v1/backoffice/dashboard` | [DashboardController::index](../app/Http/Controllers/Admin/DashboardController.php#L10) | — |
| `GET` | `/api/v1/backoffice/depreciations` | [DepreciationController::index](../app/Http/Controllers/Admin/DepreciationController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/depreciations` | [DepreciationController::store](../app/Http/Controllers/Admin/DepreciationController.php#L71) | [StoreDepreciationRequest](../app/Http/Requests/StoreDepreciationRequest.php) |
| `GET` | `/api/v1/backoffice/depreciations/create` | [DepreciationController::create](../app/Http/Controllers/Admin/DepreciationController.php#L64) | — |
| `DELETE` | `/api/v1/backoffice/depreciations/destroy` | [DepreciationController::massDestroy](../app/Http/Controllers/Admin/DepreciationController.php#L108) | [MassDestroyDepreciationRequest](../app/Http/Requests/MassDestroyDepreciationRequest.php) |
| `POST` | `/api/v1/backoffice/depreciations/parse-csv-import` | [DepreciationController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/depreciations/process-csv-import` | [DepreciationController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/depreciations/{depreciation}` | [DepreciationController::show](../app/Http/Controllers/Admin/DepreciationController.php#L92) | — |
| `PUT/PATCH` | `/api/v1/backoffice/depreciations/{depreciation}` | [DepreciationController::update](../app/Http/Controllers/Admin/DepreciationController.php#L85) | [UpdateDepreciationRequest](../app/Http/Requests/UpdateDepreciationRequest.php) |
| `DELETE` | `/api/v1/backoffice/depreciations/{depreciation}` | [DepreciationController::destroy](../app/Http/Controllers/Admin/DepreciationController.php#L99) | — |
| `GET` | `/api/v1/backoffice/depreciations/{depreciation}/edit` | [DepreciationController::edit](../app/Http/Controllers/Admin/DepreciationController.php#L78) | — |
| `GET` | `/api/v1/backoffice/external-services` | [ExternalServiceController::index](../app/Http/Controllers/Admin/ExternalServiceController.php#L18) | No controlador |
| `POST` | `/api/v1/backoffice/external-services` | [ExternalServiceController::store](../app/Http/Controllers/Admin/ExternalServiceController.php#L43) | [StoreExternalServiceRequest](../app/Http/Requests/StoreExternalServiceRequest.php) |
| `GET` | `/api/v1/backoffice/external-services/create` | [ExternalServiceController::create](../app/Http/Controllers/Admin/ExternalServiceController.php#L36) | No controlador |
| `PUT/PATCH` | `/api/v1/backoffice/external-services/{external_service}` | [ExternalServiceController::update](../app/Http/Controllers/Admin/ExternalServiceController.php#L58) | [UpdateExternalServiceRequest](../app/Http/Requests/UpdateExternalServiceRequest.php) |
| `DELETE` | `/api/v1/backoffice/external-services/{external_service}` | [ExternalServiceController::destroy](../app/Http/Controllers/Admin/ExternalServiceController.php#L66) | — |
| `GET` | `/api/v1/backoffice/external-services/{external_service}/edit` | [ExternalServiceController::edit](../app/Http/Controllers/Admin/ExternalServiceController.php#L51) | — |
| `POST` | `/api/v1/backoffice/financial-institutions` | [FinancialInstitutionController::store](../app/Http/Controllers/Admin/FinancialInstitutionController.php#L14) | No controlador |
| `GET` | `/api/v1/backoffice/general-states` | [GeneralStateController::index](../app/Http/Controllers/Admin/GeneralStateController.php#L20) | — |
| `POST` | `/api/v1/backoffice/general-states` | [GeneralStateController::store](../app/Http/Controllers/Admin/GeneralStateController.php#L38) | [StoreGeneralStateRequest](../app/Http/Requests/StoreGeneralStateRequest.php) |
| `POST` | `/api/v1/backoffice/general-states/ckmedia` | [GeneralStateController::storeCKEditorImages](../app/Http/Controllers/Admin/GeneralStateController.php#L106) | No controlador |
| `GET` | `/api/v1/backoffice/general-states/create` | [GeneralStateController::create](../app/Http/Controllers/Admin/GeneralStateController.php#L31) | — |
| `DELETE` | `/api/v1/backoffice/general-states/destroy` | [GeneralStateController::massDestroy](../app/Http/Controllers/Admin/GeneralStateController.php#L79) | [MassDestroyGeneralStateRequest](../app/Http/Requests/MassDestroyGeneralStateRequest.php) |
| `POST` | `/api/v1/backoffice/general-states/media` | [GeneralStateController::storeMedia](../app/Http/Controllers/Traits/MediaUploadingTrait.php#L9) | No controlador |
| `POST` | `/api/v1/backoffice/general-states/reorder` | [GeneralStateController::reorder](../app/Http/Controllers/Admin/GeneralStateController.php#L90) | No controlador |
| `GET` | `/api/v1/backoffice/general-states/{general_state}` | [GeneralStateController::show](../app/Http/Controllers/Admin/GeneralStateController.php#L63) | — |
| `PUT/PATCH` | `/api/v1/backoffice/general-states/{general_state}` | [GeneralStateController::update](../app/Http/Controllers/Admin/GeneralStateController.php#L56) | [UpdateGeneralStateRequest](../app/Http/Requests/UpdateGeneralStateRequest.php) |
| `DELETE` | `/api/v1/backoffice/general-states/{general_state}` | [GeneralStateController::destroy](../app/Http/Controllers/Admin/GeneralStateController.php#L70) | — |
| `GET` | `/api/v1/backoffice/general-states/{general_state}/edit` | [GeneralStateController::edit](../app/Http/Controllers/Admin/GeneralStateController.php#L49) | — |
| `GET` | `/api/v1/backoffice/global-search` | [GlobalSearchController::search](../app/Http/Controllers/Admin/GlobalSearchController.php#L15) | No controlador |
| `GET` | `/api/v1/backoffice/gps-positions` | [GpsController::latest](../app/Http/Controllers/Admin/GpsController.php#L16) | No controlador |
| `GET` | `/api/v1/backoffice/import-configuration` | [ImportConfigurationController::index](../app/Http/Controllers/Admin/ImportConfigurationController.php#L15) | — |
| `PUT` | `/api/v1/backoffice/import-configuration/tolls-recipient` | [ImportConfigurationController::updateTollsRecipient](../app/Http/Controllers/Admin/ImportConfigurationController.php#L33) | [UpdateOperationalAlertRecipientsRequest](../app/Http/Requests/UpdateOperationalAlertRecipientsRequest.php) |
| `GET` | `/api/v1/backoffice/iuc-due/export` | [HomeController::exportIucDue](../app/Http/Controllers/Admin/HomeController.php#L168) | — |
| `GET` | `/api/v1/backoffice/leads` | [LeadController::index](../app/Http/Controllers/Admin/LeadController.php#L19) | No controlador |
| `GET` | `/api/v1/backoffice/leads-performance` | [LeadPerformanceController::index](../app/Http/Controllers/Admin/LeadPerformanceController.php#L25) | No controlador |
| `GET` | `/api/v1/backoffice/leads-performance/pdf` | [LeadPerformanceController::export](../app/Http/Controllers/Admin/LeadPerformanceController.php#L18) | No controlador |
| `GET` | `/api/v1/backoffice/leads/export/pdf` | [LeadController::exportPdf](../app/Http/Controllers/Admin/LeadController.php#L109) | No controlador |
| `GET` | `/api/v1/backoffice/leads/{lead}` | [LeadController::show](../app/Http/Controllers/Admin/LeadController.php#L97) | — |
| `PUT/PATCH` | `/api/v1/backoffice/leads/{lead}` | [LeadController::update](../app/Http/Controllers/Admin/LeadController.php#L173) | No controlador |
| `DELETE` | `/api/v1/backoffice/leads/{lead}` | [LeadController::destroy](../app/Http/Controllers/Admin/LeadController.php#L220) | — |
| `GET` | `/api/v1/backoffice/leads/{lead}/edit` | [LeadController::edit](../app/Http/Controllers/Admin/LeadController.php#L162) | — |
| `POST` | `/api/v1/backoffice/leads/{lead}/notes` | [LeadController::storeNote](../app/Http/Controllers/Admin/LeadController.php#L230) | No controlador |
| `DELETE` | `/api/v1/backoffice/leads/{lead}/notes/{note}` | [LeadController::destroyNote](../app/Http/Controllers/Admin/LeadController.php#L247) | — |
| `GET` | `/api/v1/backoffice/messenger` | [MessengerController::index](../app/Http/Controllers/Admin/MessengerController.php#L16) | — |
| `POST` | `/api/v1/backoffice/messenger` | [MessengerController::storeTopic](../app/Http/Controllers/Admin/MessengerController.php#L42) | [QaTopicCreateRequest](../app/Http/Requests/QaTopicCreateRequest.php) |
| `GET` | `/api/v1/backoffice/messenger/create` | [MessengerController::createTopic](../app/Http/Controllers/Admin/MessengerController.php#L32) | — |
| `GET` | `/api/v1/backoffice/messenger/inbox` | [MessengerController::showInbox](../app/Http/Controllers/Admin/MessengerController.php#L83) | — |
| `GET` | `/api/v1/backoffice/messenger/outbox` | [MessengerController::showOutbox](../app/Http/Controllers/Admin/MessengerController.php#L96) | — |
| `GET` | `/api/v1/backoffice/messenger/{topic}` | [MessengerController::showMessages](../app/Http/Controllers/Admin/MessengerController.php#L58) | — |
| `DELETE` | `/api/v1/backoffice/messenger/{topic}` | [MessengerController::destroyTopic](../app/Http/Controllers/Admin/MessengerController.php#L74) | — |
| `POST` | `/api/v1/backoffice/messenger/{topic}/reply` | [MessengerController::replyToTopic](../app/Http/Controllers/Admin/MessengerController.php#L109) | [QaTopicReplyRequest](../app/Http/Requests/QaTopicReplyRequest.php) |
| `GET` | `/api/v1/backoffice/messenger/{topic}/reply` | [MessengerController::showReply](../app/Http/Controllers/Admin/MessengerController.php#L121) | — |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes` | [OficinaExpertiseProcessController::index](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L21) | No controlador |
| `POST` | `/api/v1/backoffice/oficina-expertise-processes` | [OficinaExpertiseProcessController::store](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L66) | [StoreOficinaExpertiseProcessRequest](../app/Http/Requests/StoreOficinaExpertiseProcessRequest.php) |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes/create` | [OficinaExpertiseProcessController::create](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L59) | No controlador |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}` | [OficinaExpertiseProcessController::show](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L83) | — |
| `PUT/PATCH` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}` | [OficinaExpertiseProcessController::update](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L99) | [UpdateOficinaExpertiseProcessRequest](../app/Http/Requests/UpdateOficinaExpertiseProcessRequest.php) |
| `DELETE` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}` | [OficinaExpertiseProcessController::destroy](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L184) | — |
| `GET` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}/edit` | [OficinaExpertiseProcessController::edit](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L92) | No controlador |
| `PATCH` | `/api/v1/backoffice/oficina-expertise-processes/{oficina_expertise_process}/status` | [OficinaExpertiseProcessController::updateStatus](../app/Http/Controllers/Admin/OficinaExpertiseProcessController.php#L131) | No controlador |
| `GET` | `/api/v1/backoffice/painting-jobs` | [PaintingJobController::index](../app/Http/Controllers/Admin/PaintingJobController.php#L17) | No controlador |
| `POST` | `/api/v1/backoffice/painting-jobs` | [PaintingJobController::store](../app/Http/Controllers/Admin/PaintingJobController.php#L42) | No controlador |
| `GET` | `/api/v1/backoffice/painting-jobs/create` | [PaintingJobController::create](../app/Http/Controllers/Admin/PaintingJobController.php#L35) | — |
| `GET` | `/api/v1/backoffice/painting-jobs/{paintingJob}` | [PaintingJobController::show](../app/Http/Controllers/Admin/PaintingJobController.php#L70) | — |
| `PUT/PATCH` | `/api/v1/backoffice/painting-jobs/{paintingJob}` | [PaintingJobController::update](../app/Http/Controllers/Admin/PaintingJobController.php#L84) | No controlador |
| `POST` | `/api/v1/backoffice/painting-jobs/{paintingJob}/complete` | [PaintingJobController::complete](../app/Http/Controllers/Admin/PaintingJobController.php#L101) | No controlador |
| `GET` | `/api/v1/backoffice/painting-jobs/{paintingJob}/edit` | [PaintingJobController::edit](../app/Http/Controllers/Admin/PaintingJobController.php#L77) | — |
| `POST` | `/api/v1/backoffice/painting-jobs/{paintingJob}/reopen` | [PaintingJobController::reopen](../app/Http/Controllers/Admin/PaintingJobController.php#L112) | No controlador |
| `GET` | `/api/v1/backoffice/part-orders` | [PartOrderController::index](../app/Http/Controllers/Admin/PartOrderController.php#L23) | No controlador |
| `POST` | `/api/v1/backoffice/part-orders` | [PartOrderController::store](../app/Http/Controllers/Admin/PartOrderController.php#L77) | [StorePartOrderRequest](../app/Http/Requests/StorePartOrderRequest.php) |
| `GET` | `/api/v1/backoffice/part-orders/create` | [PartOrderController::create](../app/Http/Controllers/Admin/PartOrderController.php#L70) | No controlador |
| `POST` | `/api/v1/backoffice/part-orders/{partOrder}/items/{item}/quotes` | [PartOrderController::storeQuote](../app/Http/Controllers/Admin/PartOrderController.php#L126) | No controlador |
| `POST` | `/api/v1/backoffice/part-orders/{partOrder}/items/{item}/quotes/{quote}/select` | [PartOrderController::selectQuote](../app/Http/Controllers/Admin/PartOrderController.php#L145) | — |
| `GET` | `/api/v1/backoffice/part-orders/{part_order}` | [PartOrderController::show](../app/Http/Controllers/Admin/PartOrderController.php#L108) | — |
| `PUT/PATCH` | `/api/v1/backoffice/part-orders/{part_order}` | [PartOrderController::update](../app/Http/Controllers/Admin/PartOrderController.php#L96) | [UpdatePartOrderRequest](../app/Http/Requests/UpdatePartOrderRequest.php) |
| `DELETE` | `/api/v1/backoffice/part-orders/{part_order}` | [PartOrderController::destroy](../app/Http/Controllers/Admin/PartOrderController.php#L117) | — |
| `GET` | `/api/v1/backoffice/part-orders/{part_order}/edit` | [PartOrderController::edit](../app/Http/Controllers/Admin/PartOrderController.php#L87) | — |
| `GET` | `/api/v1/backoffice/part-payments` | [PartPaymentController::index](../app/Http/Controllers/Admin/PartPaymentController.php#L18) | No controlador |
| `POST` | `/api/v1/backoffice/part-payments` | [PartPaymentController::store](../app/Http/Controllers/Admin/PartPaymentController.php#L49) | [StorePartPaymentRequest](../app/Http/Requests/StorePartPaymentRequest.php) |
| `GET` | `/api/v1/backoffice/part-payments/create` | [PartPaymentController::create](../app/Http/Controllers/Admin/PartPaymentController.php#L42) | No controlador |
| `GET` | `/api/v1/backoffice/part-payments/{part_payment}` | [PartPaymentController::show](../app/Http/Controllers/Admin/PartPaymentController.php#L70) | — |
| `PUT/PATCH` | `/api/v1/backoffice/part-payments/{part_payment}` | [PartPaymentController::update](../app/Http/Controllers/Admin/PartPaymentController.php#L63) | [UpdatePartPaymentRequest](../app/Http/Requests/UpdatePartPaymentRequest.php) |
| `DELETE` | `/api/v1/backoffice/part-payments/{part_payment}` | [PartPaymentController::destroy](../app/Http/Controllers/Admin/PartPaymentController.php#L79) | — |
| `GET` | `/api/v1/backoffice/part-payments/{part_payment}/edit` | [PartPaymentController::edit](../app/Http/Controllers/Admin/PartPaymentController.php#L56) | — |
| `GET` | `/api/v1/backoffice/part-receipts` | [PartReceiptController::index](../app/Http/Controllers/Admin/PartReceiptController.php#L21) | No controlador |
| `POST` | `/api/v1/backoffice/part-receipts` | [PartReceiptController::store](../app/Http/Controllers/Admin/PartReceiptController.php#L40) | [StorePartReceiptRequest](../app/Http/Requests/StorePartReceiptRequest.php) |
| `GET` | `/api/v1/backoffice/part-receipts/create` | [PartReceiptController::create](../app/Http/Controllers/Admin/PartReceiptController.php#L33) | No controlador |
| `GET` | `/api/v1/backoffice/part-receipts/{part_receipt}` | [PartReceiptController::show](../app/Http/Controllers/Admin/PartReceiptController.php#L65) | — |
| `PUT/PATCH` | `/api/v1/backoffice/part-receipts/{part_receipt}` | [PartReceiptController::update](../app/Http/Controllers/Admin/PartReceiptController.php#L57) | [UpdatePartReceiptRequest](../app/Http/Requests/UpdatePartReceiptRequest.php) |
| `DELETE` | `/api/v1/backoffice/part-receipts/{part_receipt}` | [PartReceiptController::destroy](../app/Http/Controllers/Admin/PartReceiptController.php#L74) | — |
| `GET` | `/api/v1/backoffice/part-receipts/{part_receipt}/edit` | [PartReceiptController::edit](../app/Http/Controllers/Admin/PartReceiptController.php#L48) | — |
| `GET` | `/api/v1/backoffice/payment-methods` | [PaymentMethodController::index](../app/Http/Controllers/Admin/PaymentMethodController.php#L16) | — |
| `POST` | `/api/v1/backoffice/payment-methods` | [PaymentMethodController::store](../app/Http/Controllers/Admin/PaymentMethodController.php#L32) | [StorePaymentMethodRequest](../app/Http/Requests/StorePaymentMethodRequest.php) |
| `GET` | `/api/v1/backoffice/payment-methods/create` | [PaymentMethodController::create](../app/Http/Controllers/Admin/PaymentMethodController.php#L25) | — |
| `DELETE` | `/api/v1/backoffice/payment-methods/destroy` | [PaymentMethodController::massDestroy](../app/Http/Controllers/Admin/PaymentMethodController.php#L69) | [MassDestroyPaymentMethodRequest](../app/Http/Requests/MassDestroyPaymentMethodRequest.php) |
| `GET` | `/api/v1/backoffice/payment-methods/{payment_method}` | [PaymentMethodController::show](../app/Http/Controllers/Admin/PaymentMethodController.php#L53) | — |
| `PUT/PATCH` | `/api/v1/backoffice/payment-methods/{payment_method}` | [PaymentMethodController::update](../app/Http/Controllers/Admin/PaymentMethodController.php#L46) | [UpdatePaymentMethodRequest](../app/Http/Requests/UpdatePaymentMethodRequest.php) |
| `DELETE` | `/api/v1/backoffice/payment-methods/{payment_method}` | [PaymentMethodController::destroy](../app/Http/Controllers/Admin/PaymentMethodController.php#L60) | — |
| `GET` | `/api/v1/backoffice/payment-methods/{payment_method}/edit` | [PaymentMethodController::edit](../app/Http/Controllers/Admin/PaymentMethodController.php#L39) | — |
| `GET` | `/api/v1/backoffice/payment-statuses` | [PaymentStatusController::index](../app/Http/Controllers/Admin/PaymentStatusController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/payment-statuses` | [PaymentStatusController::store](../app/Http/Controllers/Admin/PaymentStatusController.php#L68) | [StorePaymentStatusRequest](../app/Http/Requests/StorePaymentStatusRequest.php) |
| `GET` | `/api/v1/backoffice/payment-statuses/create` | [PaymentStatusController::create](../app/Http/Controllers/Admin/PaymentStatusController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/payment-statuses/destroy` | [PaymentStatusController::massDestroy](../app/Http/Controllers/Admin/PaymentStatusController.php#L105) | [MassDestroyPaymentStatusRequest](../app/Http/Requests/MassDestroyPaymentStatusRequest.php) |
| `POST` | `/api/v1/backoffice/payment-statuses/parse-csv-import` | [PaymentStatusController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/payment-statuses/process-csv-import` | [PaymentStatusController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/payment-statuses/{payment_status}` | [PaymentStatusController::show](../app/Http/Controllers/Admin/PaymentStatusController.php#L89) | — |
| `PUT/PATCH` | `/api/v1/backoffice/payment-statuses/{payment_status}` | [PaymentStatusController::update](../app/Http/Controllers/Admin/PaymentStatusController.php#L82) | [UpdatePaymentStatusRequest](../app/Http/Requests/UpdatePaymentStatusRequest.php) |
| `DELETE` | `/api/v1/backoffice/payment-statuses/{payment_status}` | [PaymentStatusController::destroy](../app/Http/Controllers/Admin/PaymentStatusController.php#L96) | — |
| `GET` | `/api/v1/backoffice/payment-statuses/{payment_status}/edit` | [PaymentStatusController::edit](../app/Http/Controllers/Admin/PaymentStatusController.php#L75) | — |
| `GET` | `/api/v1/backoffice/permissions` | [PermissionsController::index](../app/Http/Controllers/Admin/PermissionsController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/permissions` | [PermissionsController::store](../app/Http/Controllers/Admin/PermissionsController.php#L68) | [StorePermissionRequest](../app/Http/Requests/StorePermissionRequest.php) |
| `GET` | `/api/v1/backoffice/permissions/create` | [PermissionsController::create](../app/Http/Controllers/Admin/PermissionsController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/permissions/destroy` | [PermissionsController::massDestroy](../app/Http/Controllers/Admin/PermissionsController.php#L105) | [MassDestroyPermissionRequest](../app/Http/Requests/MassDestroyPermissionRequest.php) |
| `POST` | `/api/v1/backoffice/permissions/parse-csv-import` | [PermissionsController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/permissions/process-csv-import` | [PermissionsController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/permissions/{permission}` | [PermissionsController::show](../app/Http/Controllers/Admin/PermissionsController.php#L89) | — |
| `PUT/PATCH` | `/api/v1/backoffice/permissions/{permission}` | [PermissionsController::update](../app/Http/Controllers/Admin/PermissionsController.php#L82) | [UpdatePermissionRequest](../app/Http/Requests/UpdatePermissionRequest.php) |
| `DELETE` | `/api/v1/backoffice/permissions/{permission}` | [PermissionsController::destroy](../app/Http/Controllers/Admin/PermissionsController.php#L96) | — |
| `GET` | `/api/v1/backoffice/permissions/{permission}/edit` | [PermissionsController::edit](../app/Http/Controllers/Admin/PermissionsController.php#L75) | — |
| `GET` | `/api/v1/backoffice/pickup-states` | [PickupStateController::index](../app/Http/Controllers/Admin/PickupStateController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/pickup-states` | [PickupStateController::store](../app/Http/Controllers/Admin/PickupStateController.php#L68) | [StorePickupStateRequest](../app/Http/Requests/StorePickupStateRequest.php) |
| `GET` | `/api/v1/backoffice/pickup-states/create` | [PickupStateController::create](../app/Http/Controllers/Admin/PickupStateController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/pickup-states/destroy` | [PickupStateController::massDestroy](../app/Http/Controllers/Admin/PickupStateController.php#L105) | [MassDestroyPickupStateRequest](../app/Http/Requests/MassDestroyPickupStateRequest.php) |
| `POST` | `/api/v1/backoffice/pickup-states/parse-csv-import` | [PickupStateController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/pickup-states/process-csv-import` | [PickupStateController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/pickup-states/{pickup_state}` | [PickupStateController::show](../app/Http/Controllers/Admin/PickupStateController.php#L89) | — |
| `PUT/PATCH` | `/api/v1/backoffice/pickup-states/{pickup_state}` | [PickupStateController::update](../app/Http/Controllers/Admin/PickupStateController.php#L82) | [UpdatePickupStateRequest](../app/Http/Requests/UpdatePickupStateRequest.php) |
| `DELETE` | `/api/v1/backoffice/pickup-states/{pickup_state}` | [PickupStateController::destroy](../app/Http/Controllers/Admin/PickupStateController.php#L96) | — |
| `GET` | `/api/v1/backoffice/pickup-states/{pickup_state}/edit` | [PickupStateController::edit](../app/Http/Controllers/Admin/PickupStateController.php#L75) | — |
| `POST` | `/api/v1/backoffice/proveniences` | [ProvenienceController::store](../app/Http/Controllers/Admin/ProvenienceController.php#L14) | No controlador |
| `GET` | `/api/v1/backoffice/repair-parts-report` | [RepairPartsReportController::index](../app/Http/Controllers/Admin/RepairPartsReportController.php#L13) | No controlador |
| `GET` | `/api/v1/backoffice/repair-states` | [RepairStatesController::index](../app/Http/Controllers/Admin/RepairStatesController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/repair-states` | [RepairStatesController::store](../app/Http/Controllers/Admin/RepairStatesController.php#L68) | [StoreRepairStateRequest](../app/Http/Requests/StoreRepairStateRequest.php) |
| `GET` | `/api/v1/backoffice/repair-states/create` | [RepairStatesController::create](../app/Http/Controllers/Admin/RepairStatesController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/repair-states/destroy` | [RepairStatesController::massDestroy](../app/Http/Controllers/Admin/RepairStatesController.php#L105) | [MassDestroyRepairStateRequest](../app/Http/Requests/MassDestroyRepairStateRequest.php) |
| `POST` | `/api/v1/backoffice/repair-states/parse-csv-import` | [RepairStatesController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/repair-states/process-csv-import` | [RepairStatesController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/repair-states/{repair_state}` | [RepairStatesController::show](../app/Http/Controllers/Admin/RepairStatesController.php#L89) | — |
| `PUT/PATCH` | `/api/v1/backoffice/repair-states/{repair_state}` | [RepairStatesController::update](../app/Http/Controllers/Admin/RepairStatesController.php#L82) | [UpdateRepairStateRequest](../app/Http/Requests/UpdateRepairStateRequest.php) |
| `DELETE` | `/api/v1/backoffice/repair-states/{repair_state}` | [RepairStatesController::destroy](../app/Http/Controllers/Admin/RepairStatesController.php#L96) | — |
| `GET` | `/api/v1/backoffice/repair-states/{repair_state}/edit` | [RepairStatesController::edit](../app/Http/Controllers/Admin/RepairStatesController.php#L75) | — |
| `GET` | `/api/v1/backoffice/repairs` | [RepairController::index](../app/Http/Controllers/Admin/RepairController.php#L39) | No controlador |
| `POST` | `/api/v1/backoffice/repairs` | [RepairController::store](../app/Http/Controllers/Admin/RepairController.php#L524) | [StoreRepairRequest](../app/Http/Requests/StoreRepairRequest.php) |
| `POST` | `/api/v1/backoffice/repairs/ckmedia` | [RepairController::storeCKEditorImages](../app/Http/Controllers/Admin/RepairController.php#L859) | No controlador |
| `GET` | `/api/v1/backoffice/repairs/create` | [RepairController::create](../app/Http/Controllers/Admin/RepairController.php#L513) | — |
| `DELETE` | `/api/v1/backoffice/repairs/destroy` | [RepairController::massDestroy](../app/Http/Controllers/Admin/RepairController.php#L848) | [MassDestroyRepairRequest](../app/Http/Requests/MassDestroyRepairRequest.php) |
| `POST` | `/api/v1/backoffice/repairs/media` | [RepairController::storeMedia](../app/Http/Controllers/Traits/MediaUploadingTrait.php#L9) | No controlador |
| `POST` | `/api/v1/backoffice/repairs/parse-csv-import` | [RepairController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/repairs/process-csv-import` | [RepairController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/repairs/{repair}` | [RepairController::show](../app/Http/Controllers/Admin/RepairController.php#L830) | — |
| `PUT/PATCH` | `/api/v1/backoffice/repairs/{repair}` | [RepairController::update](../app/Http/Controllers/Admin/RepairController.php#L667) | [UpdateRepairRequest](../app/Http/Requests/UpdateRepairRequest.php) |
| `DELETE` | `/api/v1/backoffice/repairs/{repair}` | [RepairController::destroy](../app/Http/Controllers/Admin/RepairController.php#L839) | — |
| `GET` | `/api/v1/backoffice/repairs/{repair}/edit` | [RepairController::edit](../app/Http/Controllers/Admin/RepairController.php#L543) | — |
| `POST` | `/api/v1/backoffice/repairs/{repair}/finish` | [RepairController::finishRepair](../app/Http/Controllers/Admin/RepairController.php#L722) | — |
| `POST` | `/api/v1/backoffice/repairs/{repair}/new-intervention` | [RepairController::newIntervention](../app/Http/Controllers/Admin/RepairController.php#L787) | — |
| `POST` | `/api/v1/backoffice/repairs/{repair}/reopen` | [RepairController::reopenRepair](../app/Http/Controllers/Admin/RepairController.php#L747) | — |
| `POST` | `/api/v1/backoffice/repairs/{repair}/start` | [RepairController::startRepair](../app/Http/Controllers/Admin/RepairController.php#L707) | — |
| `POST` | `/api/v1/backoffice/repairs/{repair}/work/finish` | [RepairController::finishWork](../app/Http/Controllers/Admin/RepairController.php#L776) | — |
| `POST` | `/api/v1/backoffice/repairs/{repair}/work/start` | [RepairController::startWork](../app/Http/Controllers/Admin/RepairController.php#L765) | — |
| `POST` | `/api/v1/backoffice/role-preview` | [RolePreviewController::store](../app/Http/Controllers/Admin/RolePreviewController.php#L13) | No controlador |
| `DELETE` | `/api/v1/backoffice/role-preview` | [RolePreviewController::destroy](../app/Http/Controllers/Admin/RolePreviewController.php#L27) | No controlador |
| `GET` | `/api/v1/backoffice/roles` | [RolesController::index](../app/Http/Controllers/Admin/RolesController.php#L22) | No controlador |
| `POST` | `/api/v1/backoffice/roles` | [RolesController::store](../app/Http/Controllers/Admin/RolesController.php#L83) | [StoreRoleRequest](../app/Http/Requests/StoreRoleRequest.php) |
| `GET` | `/api/v1/backoffice/roles/create` | [RolesController::create](../app/Http/Controllers/Admin/RolesController.php#L73) | — |
| `DELETE` | `/api/v1/backoffice/roles/destroy` | [RolesController::massDestroy](../app/Http/Controllers/Admin/RolesController.php#L131) | [MassDestroyRoleRequest](../app/Http/Requests/MassDestroyRoleRequest.php) |
| `POST` | `/api/v1/backoffice/roles/parse-csv-import` | [RolesController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/roles/process-csv-import` | [RolesController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/roles/{role}` | [RolesController::show](../app/Http/Controllers/Admin/RolesController.php#L113) | — |
| `PUT/PATCH` | `/api/v1/backoffice/roles/{role}` | [RolesController::update](../app/Http/Controllers/Admin/RolesController.php#L104) | [UpdateRoleRequest](../app/Http/Requests/UpdateRoleRequest.php) |
| `DELETE` | `/api/v1/backoffice/roles/{role}` | [RolesController::destroy](../app/Http/Controllers/Admin/RolesController.php#L122) | — |
| `GET` | `/api/v1/backoffice/roles/{role}/edit` | [RolesController::edit](../app/Http/Controllers/Admin/RolesController.php#L92) | — |
| `GET` | `/api/v1/backoffice/sale-closure-approvals` | [SaleClosureApprovalController::index](../app/Http/Controllers/Admin/SaleClosureApprovalController.php#L14) | No controlador |
| `GET` | `/api/v1/backoffice/sale-closure-approvals/export` | [SaleClosureApprovalController::export](../app/Http/Controllers/Admin/SaleClosureApprovalController.php#L30) | No controlador |
| `POST` | `/api/v1/backoffice/sale-closure-approvals/{approval}/approve` | [SaleClosureApprovalController::approve](../app/Http/Controllers/Admin/SaleClosureApprovalController.php#L82) | — |
| `POST` | `/api/v1/backoffice/sale-closure-approvals/{approval}/reject` | [SaleClosureApprovalController::reject](../app/Http/Controllers/Admin/SaleClosureApprovalController.php#L96) | No controlador |
| `GET` | `/api/v1/backoffice/sales/create` | [SalesController::create](../app/Http/Controllers/Admin/SalesController.php#L26) | — |
| `GET` | `/api/v1/backoffice/sales/{general_state_id?}` | [SalesController::index](../app/Http/Controllers/Admin/SalesController.php#L33) | No controlador |
| `GET` | `/api/v1/backoffice/stand-cash-payment-approvals` | [StandCashPaymentApprovalController::index](../app/Http/Controllers/Admin/StandCashPaymentApprovalController.php#L19) | No controlador |
| `POST` | `/api/v1/backoffice/stand-cash-payment-approvals/{approval}/approve` | [StandCashPaymentApprovalController::approve](../app/Http/Controllers/Admin/StandCashPaymentApprovalController.php#L40) | — |
| `POST` | `/api/v1/backoffice/stand-cash-payment-approvals/{approval}/reject` | [StandCashPaymentApprovalController::reject](../app/Http/Controllers/Admin/StandCashPaymentApprovalController.php#L75) | No controlador |
| `GET` | `/api/v1/backoffice/supliers` | [SuplierController::index](../app/Http/Controllers/Admin/SuplierController.php#L20) | No controlador |
| `POST` | `/api/v1/backoffice/supliers` | [SuplierController::store](../app/Http/Controllers/Admin/SuplierController.php#L68) | [StoreSuplierRequest](../app/Http/Requests/StoreSuplierRequest.php) |
| `GET` | `/api/v1/backoffice/supliers/create` | [SuplierController::create](../app/Http/Controllers/Admin/SuplierController.php#L61) | — |
| `DELETE` | `/api/v1/backoffice/supliers/destroy` | [SuplierController::massDestroy](../app/Http/Controllers/Admin/SuplierController.php#L114) | [MassDestroySuplierRequest](../app/Http/Requests/MassDestroySuplierRequest.php) |
| `POST` | `/api/v1/backoffice/supliers/parse-csv-import` | [SuplierController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/supliers/process-csv-import` | [SuplierController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/supliers/{suplier}` | [SuplierController::show](../app/Http/Controllers/Admin/SuplierController.php#L93) | — |
| `PUT/PATCH` | `/api/v1/backoffice/supliers/{suplier}` | [SuplierController::update](../app/Http/Controllers/Admin/SuplierController.php#L84) | [UpdateSuplierRequest](../app/Http/Requests/UpdateSuplierRequest.php) |
| `DELETE` | `/api/v1/backoffice/supliers/{suplier}` | [SuplierController::destroy](../app/Http/Controllers/Admin/SuplierController.php#L105) | — |
| `GET` | `/api/v1/backoffice/supliers/{suplier}/edit` | [SuplierController::edit](../app/Http/Controllers/Admin/SuplierController.php#L77) | — |
| `GET` | `/api/v1/backoffice/system-calendar` | [SystemCalendarController::index](../app/Http/Controllers/Admin/SystemCalendarController.php#L16) | — |
| `POST` | `/api/v1/backoffice/system-calendar/tasks` | [SystemCalendarController::storeTask](../app/Http/Controllers/Admin/SystemCalendarController.php#L54) | No controlador |
| `DELETE` | `/api/v1/backoffice/system-calendar/tasks/{task}` | [SystemCalendarController::destroyTask](../app/Http/Controllers/Admin/SystemCalendarController.php#L78) | — |
| `POST` | `/api/v1/backoffice/system-calendar/tasks/{task}/complete` | [SystemCalendarController::completeTask](../app/Http/Controllers/Admin/SystemCalendarController.php#L69) | — |
| `GET` | `/api/v1/backoffice/system-maintenance` | [SystemMaintenanceController::index](../app/Http/Controllers/Admin/SystemMaintenanceController.php#L37) | — |
| `POST` | `/api/v1/backoffice/system-maintenance/resend-lead-notifications` | [SystemMaintenanceController::resendLeadNotifications](../app/Http/Controllers/Admin/SystemMaintenanceController.php#L77) | No controlador |
| `POST` | `/api/v1/backoffice/system-maintenance/run` | [SystemMaintenanceController::run](../app/Http/Controllers/Admin/SystemMaintenanceController.php#L53) | No controlador |
| `POST` | `/api/v1/backoffice/system-shutdown` | [SystemShutdownController::store](../app/Http/Controllers/Admin/SystemShutdownController.php#L13) | No controlador |
| `GET` | `/api/v1/backoffice/users` | [UsersController::index](../app/Http/Controllers/Admin/UsersController.php#L21) | No controlador |
| `POST` | `/api/v1/backoffice/users` | [UsersController::store](../app/Http/Controllers/Admin/UsersController.php#L88) | [StoreUserRequest](../app/Http/Requests/StoreUserRequest.php) |
| `GET` | `/api/v1/backoffice/users/create` | [UsersController::create](../app/Http/Controllers/Admin/UsersController.php#L79) | — |
| `DELETE` | `/api/v1/backoffice/users/destroy` | [UsersController::massDestroy](../app/Http/Controllers/Admin/UsersController.php#L133) | [MassDestroyUserRequest](../app/Http/Requests/MassDestroyUserRequest.php) |
| `POST` | `/api/v1/backoffice/users/parse-csv-import` | [UsersController::parseCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L65) | No controlador |
| `POST` | `/api/v1/backoffice/users/process-csv-import` | [UsersController::processCsvImport](../app/Http/Controllers/Traits/CsvImportTrait.php#L12) | No controlador |
| `GET` | `/api/v1/backoffice/users/{user}` | [UsersController::show](../app/Http/Controllers/Admin/UsersController.php#L115) | — |
| `PUT/PATCH` | `/api/v1/backoffice/users/{user}` | [UsersController::update](../app/Http/Controllers/Admin/UsersController.php#L107) | [UpdateUserRequest](../app/Http/Requests/UpdateUserRequest.php) |
| `DELETE` | `/api/v1/backoffice/users/{user}` | [UsersController::destroy](../app/Http/Controllers/Admin/UsersController.php#L124) | — |
| `GET` | `/api/v1/backoffice/users/{user}/edit` | [UsersController::edit](../app/Http/Controllers/Admin/UsersController.php#L96) | — |
| `GET` | `/api/v1/backoffice/vehicle-consignments` | [VehicleConsignmentController::index](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L19) | No controlador |
| `POST` | `/api/v1/backoffice/vehicle-consignments` | [VehicleConsignmentController::store](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L84) | [StoreVehicleConsignmentRequest](../app/Http/Requests/StoreVehicleConsignmentRequest.php) |
| `GET` | `/api/v1/backoffice/vehicle-consignments-history` | [VehicleConsignmentAuditController::index](../app/Http/Controllers/Admin/VehicleConsignmentAuditController.php#L15) | No controlador |
| `GET` | `/api/v1/backoffice/vehicle-consignments/create` | [VehicleConsignmentController::create](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L74) | — |
| `GET` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}` | [VehicleConsignmentController::show](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L124) | — |
| `PUT/PATCH` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}` | [VehicleConsignmentController::update](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L105) | [UpdateVehicleConsignmentRequest](../app/Http/Requests/UpdateVehicleConsignmentRequest.php) |
| `DELETE` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}` | [VehicleConsignmentController::destroy](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L114) | — |
| `GET` | `/api/v1/backoffice/vehicle-consignments/{vehicle_consignment}/edit` | [VehicleConsignmentController::edit](../app/Http/Controllers/Admin/VehicleConsignmentController.php#L94) | — |
| `GET` | `/api/v1/backoffice/vehicle-groups` | [VehicleGroupController::index](../app/Http/Controllers/Admin/VehicleGroupController.php#L32) | — |
| `POST` | `/api/v1/backoffice/vehicle-groups` | [VehicleGroupController::store](../app/Http/Controllers/Admin/VehicleGroupController.php#L54) | [StoreVehicleGroupRequest](../app/Http/Requests/StoreVehicleGroupRequest.php) |
| `GET` | `/api/v1/backoffice/vehicle-groups/create` | [VehicleGroupController::create](../app/Http/Controllers/Admin/VehicleGroupController.php#L44) | — |
| `DELETE` | `/api/v1/backoffice/vehicle-groups/destroy` | [VehicleGroupController::massDestroy](../app/Http/Controllers/Admin/VehicleGroupController.php#L114) | [MassDestroyVehicleGroupRequest](../app/Http/Requests/MassDestroyVehicleGroupRequest.php) |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/approve` | [VehicleGroupController::approveLot](../app/Http/Controllers/Admin/VehicleGroupController.php#L202) | — |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/payments` | [VehicleGroupController::storePayment](../app/Http/Controllers/Admin/VehicleGroupController.php#L125) | [StoreVehicleGroupPaymentRequest](../app/Http/Requests/StoreVehicleGroupPaymentRequest.php) |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/payments/{payment}/approve` | [VehicleGroupController::approvePayment](../app/Http/Controllers/Admin/VehicleGroupController.php#L214) | No controlador |
| `POST` | `/api/v1/backoffice/vehicle-groups/{vehicleGroup}/payments/{payment}/reject` | [VehicleGroupController::rejectPayment](../app/Http/Controllers/Admin/VehicleGroupController.php#L225) | No controlador |
| `GET` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}` | [VehicleGroupController::show](../app/Http/Controllers/Admin/VehicleGroupController.php#L95) | — |
| `PUT/PATCH` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}` | [VehicleGroupController::update](../app/Http/Controllers/Admin/VehicleGroupController.php#L82) | [UpdateVehicleGroupRequest](../app/Http/Requests/UpdateVehicleGroupRequest.php) |
| `DELETE` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}` | [VehicleGroupController::destroy](../app/Http/Controllers/Admin/VehicleGroupController.php#L105) | — |
| `GET` | `/api/v1/backoffice/vehicle-groups/{vehicle_group}/edit` | [VehicleGroupController::edit](../app/Http/Controllers/Admin/VehicleGroupController.php#L65) | — |
| `GET` | `/api/v1/backoffice/vehicle-state-transfers` | [VehicleStateTransferController::index](../app/Http/Controllers/Admin/VehicleStateTransferController.php#L13) | No controlador |
| `POST` | `/api/v1/backoffice/vehicle-state-transfers/{transfer}/check` | [VehicleStateTransferController::check](../app/Http/Controllers/Admin/VehicleStateTransferController.php#L31) | No controlador |
| `GET` | `/api/v1/backoffice/vehicle-trade-ins` | [VehicleTradeInController::index](../app/Http/Controllers/Admin/VehicleTradeInController.php#L22) | No controlador |
| `POST` | `/api/v1/backoffice/vehicle-trade-ins` | [VehicleTradeInController::storeStandalone](../app/Http/Controllers/Admin/VehicleTradeInController.php#L82) | No controlador |
| `GET` | `/api/v1/backoffice/vehicle-trade-ins/create` | [VehicleTradeInController::create](../app/Http/Controllers/Admin/VehicleTradeInController.php#L73) | — |
| `GET` | `/api/v1/backoffice/vehicle-trade-ins/pending` | [VehicleTradeInController::pending](../app/Http/Controllers/Admin/VehicleTradeInController.php#L433) | — |
| `POST` | `/api/v1/backoffice/vehicle-trade-ins/{tradeIn}/convert` | [VehicleTradeInController::convert](../app/Http/Controllers/Admin/VehicleTradeInController.php#L149) | No controlador |
| `POST` | `/api/v1/backoffice/vehicle-trade-ins/{tradeIn}/reject` | [VehicleTradeInController::reject](../app/Http/Controllers/Admin/VehicleTradeInController.php#L177) | No controlador |
| `GET` | `/api/v1/backoffice/vehicles` | [VehicleController::index](../app/Http/Controllers/Admin/VehicleController.php#L51) | No controlador |
| `POST` | `/api/v1/backoffice/vehicles` | [VehicleController::store](../app/Http/Controllers/Admin/VehicleController.php#L260) | [StoreVehicleRequest](../app/Http/Requests/StoreVehicleRequest.php) |
| `GET` | `/api/v1/backoffice/vehicles-deleted` | [VehicleController::deleted](../app/Http/Controllers/Admin/VehicleController.php#L827) | — |
| `GET` | `/api/v1/backoffice/vehicles-deleted/{vehicle}` | [VehicleController::showDeleted](../app/Http/Controllers/Admin/VehicleController.php#L839) | — |
| `PUT` | `/api/v1/backoffice/vehicles-deleted/{vehicle}` | [VehicleController::updateDeleted](../app/Http/Controllers/Admin/VehicleController.php#L856) | [UpdateVehicleRequest](../app/Http/Requests/UpdateVehicleRequest.php) |
| `GET` | `/api/v1/backoffice/vehicles-deleted/{vehicle}/edit` | [VehicleController::editDeleted](../app/Http/Controllers/Admin/VehicleController.php#L846) | — |
| `POST` | `/api/v1/backoffice/vehicles-deleted/{vehicle}/restore` | [VehicleController::restore](../app/Http/Controllers/Admin/VehicleController.php#L866) | — |
| `POST` | `/api/v1/backoffice/vehicles/ckmedia` | [VehicleController::storeCKEditorImages](../app/Http/Controllers/Admin/VehicleController.php#L949) | No controlador |
| `GET` | `/api/v1/backoffice/vehicles/create` | [VehicleController::create](../app/Http/Controllers/Admin/VehicleController.php#L235) | — |
| `DELETE` | `/api/v1/backoffice/vehicles/destroy` | [VehicleController::massDestroy](../app/Http/Controllers/Admin/VehicleController.php#L878) | [MassDestroyVehicleRequest](../app/Http/Requests/MassDestroyVehicleRequest.php) |
| `POST` | `/api/v1/backoffice/vehicles/media` | [VehicleController::storeMedia](../app/Http/Controllers/Traits/MediaUploadingTrait.php#L9) | No controlador |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}` | [VehicleController::show](../app/Http/Controllers/Admin/VehicleController.php#L791) | — |
| `PUT/PATCH` | `/api/v1/backoffice/vehicles/{vehicle}` | [VehicleController::update](../app/Http/Controllers/Admin/VehicleController.php#L527) | [UpdateVehicleRequest](../app/Http/Requests/UpdateVehicleRequest.php) |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}` | [VehicleController::destroy](../app/Http/Controllers/Admin/VehicleController.php#L818) | — |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/client-payments/{payment}` | [VehicleController::destroyClientPayment](../app/Http/Controllers/Admin/VehicleController.php#L923) | No controlador |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/edit` | [VehicleController::edit](../app/Http/Controllers/Admin/VehicleController.php#L284) | — |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/generic-payments/{payment}` | [VehicleController::destroyGenericPayment](../app/Http/Controllers/Admin/VehicleController.php#L906) | No controlador |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/notes` | [VehicleNoteController::index](../app/Http/Controllers/Admin/VehicleNoteController.php#L15) | — |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/notes` | [VehicleNoteController::store](../app/Http/Controllers/Admin/VehicleNoteController.php#L24) | No controlador |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/send-to-workshop` | [VehicleController::sendToWorkshop](../app/Http/Controllers/Admin/VehicleController.php#L425) | — |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/start-intervention` | [RepairController::startIntervention](../app/Http/Controllers/Admin/RepairController.php#L808) | — |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/supplier-payments/{payment}` | [VehicleController::destroySupplierPayment](../app/Http/Controllers/Admin/VehicleController.php#L889) | No controlador |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/suspended-sale` | [VehicleController::suspendSale](../app/Http/Controllers/Admin/VehicleController.php#L752) | No controlador |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/suspended-sale` | [VehicleController::cancelSuspendedSale](../app/Http/Controllers/Admin/VehicleController.php#L775) | No controlador |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/timeline` | [VehicleTimelineController::show](../app/Http/Controllers/Admin/VehicleTimelineController.php#L13) | — |
| `GET` | `/api/v1/backoffice/vehicles/{vehicle}/timeline/export/pdf` | [VehicleTimelineExportController::exportPdf](../app/Http/Controllers/Admin/VehicleTimelineExportController.php#L15) | — |
| `POST` | `/api/v1/backoffice/vehicles/{vehicle}/trade-ins` | [VehicleTradeInController::store](../app/Http/Controllers/Admin/VehicleTradeInController.php#L112) | No controlador |
| `DELETE` | `/api/v1/backoffice/vehicles/{vehicle}/workshop` | [VehicleController::removeFromWorkshop](../app/Http/Controllers/Admin/VehicleController.php#L452) | — |
| `PATCH` | `/api/v1/backoffice/vehicles/{vehicle}/workshop-state` | [VehicleController::updateWorkshopState](../app/Http/Controllers/Admin/VehicleController.php#L488) | [UpdateVehicleWorkshopStateRequest](../app/Http/Requests/UpdateVehicleWorkshopStateRequest.php) |
| `GET` | `/api/v1/backoffice/workshop-cash` | [WorkshopCashController::index](../app/Http/Controllers/Admin/WorkshopCashController.php#L24) | No controlador |
| `POST` | `/api/v1/backoffice/workshop-cash/categories` | [WorkshopCashController::storeCategory](../app/Http/Controllers/Admin/WorkshopCashController.php#L110) | [StoreWorkshopCashCategoryRequest](../app/Http/Requests/StoreWorkshopCashCategoryRequest.php) |
| `PUT` | `/api/v1/backoffice/workshop-cash/categories/{cashCategory}` | [WorkshopCashController::updateCategory](../app/Http/Controllers/Admin/WorkshopCashController.php#L120) | No controlador |
| `POST` | `/api/v1/backoffice/workshop-cash/expenses` | [WorkshopCashController::storeExpense](../app/Http/Controllers/Admin/WorkshopCashController.php#L61) | [StoreWorkshopCashExpenseRequest](../app/Http/Requests/StoreWorkshopCashExpenseRequest.php) |
| `POST` | `/api/v1/backoffice/workshop-cash/transfers` | [WorkshopCashController::storeTransfer](../app/Http/Controllers/Admin/WorkshopCashController.php#L86) | [StoreCashTransferRequest](../app/Http/Requests/StoreCashTransferRequest.php) |
| `GET` | `/api/v1/backoffice/workshop-intervention-types` | [WorkshopInterventionTypeController::index](../app/Http/Controllers/Admin/WorkshopInterventionTypeController.php#L14) | — |
| `POST` | `/api/v1/backoffice/workshop-intervention-types` | [WorkshopInterventionTypeController::store](../app/Http/Controllers/Admin/WorkshopInterventionTypeController.php#L21) | No controlador |
| `PUT/PATCH` | `/api/v1/backoffice/workshop-intervention-types/{workshopInterventionType}` | [WorkshopInterventionTypeController::update](../app/Http/Controllers/Admin/WorkshopInterventionTypeController.php#L29) | No controlador |
| `DELETE` | `/api/v1/backoffice/workshop-intervention-types/{workshopInterventionType}` | [WorkshopInterventionTypeController::destroy](../app/Http/Controllers/Admin/WorkshopInterventionTypeController.php#L42) | — |
| `GET` | `/api/v1/backoffice/workshop-interventions` | [WorkshopInterventionController::index](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L19) | No controlador |
| `POST` | `/api/v1/backoffice/workshop-interventions` | [WorkshopInterventionController::store](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L52) | [StoreWorkshopInterventionRequest](../app/Http/Requests/StoreWorkshopInterventionRequest.php) |
| `GET` | `/api/v1/backoffice/workshop-interventions/create` | [WorkshopInterventionController::create](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L45) | No controlador |
| `PUT/PATCH` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}` | [WorkshopInterventionController::update](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L70) | [UpdateWorkshopInterventionRequest](../app/Http/Requests/UpdateWorkshopInterventionRequest.php) |
| `DELETE` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}` | [WorkshopInterventionController::destroy](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L94) | — |
| `POST` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/complete` | [WorkshopInterventionController::complete](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L119) | — |
| `GET` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/edit` | [WorkshopInterventionController::edit](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L63) | — |
| `POST` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/finish` | [WorkshopInterventionController::finish](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L111) | — |
| `POST` | `/api/v1/backoffice/workshop-interventions/{workshopIntervention}/start` | [WorkshopInterventionController::start](../app/Http/Controllers/Admin/WorkshopInterventionController.php#L103) | — |
| `GET` | `/api/v1/backoffice/workshop-states` | [WorkshopStateController::index](../app/Http/Controllers/Admin/WorkshopStateController.php#L18) | — |
| `POST` | `/api/v1/backoffice/workshop-states` | [WorkshopStateController::store](../app/Http/Controllers/Admin/WorkshopStateController.php#L42) | [StoreWorkshopStateRequest](../app/Http/Requests/StoreWorkshopStateRequest.php) |
| `PUT/PATCH` | `/api/v1/backoffice/workshop-states/{workshop_state}` | [WorkshopStateController::update](../app/Http/Controllers/Admin/WorkshopStateController.php#L79) | [UpdateWorkshopStateRequest](../app/Http/Requests/UpdateWorkshopStateRequest.php) |
| `DELETE` | `/api/v1/backoffice/workshop-states/{workshop_state}` | [WorkshopStateController::destroy](../app/Http/Controllers/Admin/WorkshopStateController.php#L104) | — |

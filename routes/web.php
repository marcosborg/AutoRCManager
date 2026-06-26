<?php

Route::redirect('/', '/login');
Route::get('/home', function () {
    if (session('status')) {
        return redirect()->route('admin.home')->with('status', session('status'));
    }

    return redirect()->route('admin.home');
});

Auth::routes(['register' => false]);

Route::get('lead-access/{token}', [\App\Http\Controllers\LeadAccessController::class, 'show'])->name('lead-access.show');

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('iuc-due/export', 'HomeController@exportIucDue')->name('iuc-due.export');
    Route::get('dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('cash', 'CashController@index')->name('cash.index');
    Route::post('cash/movements', 'CashController@store')->name('cash.movements.store');
    Route::post('cash/movements/{operation}/accounted', 'CashController@toggleAccounted')->name('cash.movements.accounted');
    Route::post('cash/transfers', 'CashController@transfer')->name('cash.transfers.store');
    Route::post('cash/departments', 'CashController@storeDepartment')->name('cash.departments.store');
    Route::post('cash/categories', 'CashController@storeCategory')->name('cash.categories.store');
    Route::post('cash/boxes', 'CashController@storeCashBox')->name('cash.boxes.store');
    Route::post('system-shutdown', 'SystemShutdownController@store')->name('system-shutdown.store');
    Route::post('role-preview', 'RolePreviewController@store')->name('role-preview.store');
    Route::delete('role-preview', 'RolePreviewController@destroy')->name('role-preview.destroy');
    Route::get('approvals', 'ApprovalController@index')->name('approvals.index');
    Route::get('gps-positions', 'GpsController@latest')->name('gps.positions');
    Route::post('leads/send-pending-smtp', 'LeadController@sendPendingSmtp')->name('leads.send-pending-smtp');
    Route::post('leads/{lead}/notes', 'LeadController@storeNote')->name('leads.notes.store');
    Route::delete('leads/{lead}/notes/{note}', 'LeadController@destroyNote')->name('leads.notes.destroy');
    Route::resource('leads', 'LeadController')->except(['create', 'store']);

    Route::resource('ai-assistants', 'AiAssistantController');
    Route::resource('ai-training-contents', 'AiTrainingContentController');
    Route::resource('chat-leads', 'ChatLeadController');
    Route::post('chat-conversations/{chatConversation}/takeover', 'ChatConversationController@takeover')->name('chat-conversations.takeover');
    Route::post('chat-conversations/{chatConversation}/release', 'ChatConversationController@release')->name('chat-conversations.release');
    Route::post('chat-conversations/{chatConversation}/close', 'ChatConversationController@close')->name('chat-conversations.close');
    Route::resource('chat-conversations', 'ChatConversationController')->only(['index', 'show']);

    // Permissions
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::post('permissions/parse-csv-import', 'PermissionsController@parseCsvImport')->name('permissions.parseCsvImport');
    Route::post('permissions/process-csv-import', 'PermissionsController@processCsvImport')->name('permissions.processCsvImport');
    Route::resource('permissions', 'PermissionsController');

    // Roles
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::post('roles/parse-csv-import', 'RolesController@parseCsvImport')->name('roles.parseCsvImport');
    Route::post('roles/process-csv-import', 'RolesController@processCsvImport')->name('roles.processCsvImport');
    Route::resource('roles', 'RolesController');

    // Users
    Route::delete('users/destroy', 'UsersController@massDestroy')->name('users.massDestroy');
    Route::post('users/parse-csv-import', 'UsersController@parseCsvImport')->name('users.parseCsvImport');
    Route::post('users/process-csv-import', 'UsersController@processCsvImport')->name('users.processCsvImport');
    Route::resource('users', 'UsersController');

    // Countries
    Route::delete('countries/destroy', 'CountriesController@massDestroy')->name('countries.massDestroy');
    Route::post('countries/parse-csv-import', 'CountriesController@parseCsvImport')->name('countries.parseCsvImport');
    Route::post('countries/process-csv-import', 'CountriesController@processCsvImport')->name('countries.processCsvImport');
    Route::resource('countries', 'CountriesController');

    // Client
    Route::delete('clients/destroy', 'ClientController@massDestroy')->name('clients.massDestroy');
    Route::post('clients/parse-csv-import', 'ClientController@parseCsvImport')->name('clients.parseCsvImport');
    Route::post('clients/process-csv-import', 'ClientController@processCsvImport')->name('clients.processCsvImport');
    Route::get('clients/{client}/reconciliation', [App\Http\Controllers\Admin\ClientController::class, 'reconciliation'])->name('clients.reconciliation');
    Route::get('clients/{client}/payments/{payment}', 'ClientController@showPayment')->name('clients.payments.show');
    Route::post('clients/{client}/payments', 'ClientController@storePayment')->name('clients.payments.store');
    Route::post('clients/{client}/charges', 'ClientController@storeCharge')->name('clients.charges.store');
    Route::post('proveniences', 'ProvenienceController@store')->name('proveniences.store');
    Route::post('financial-institutions', 'FinancialInstitutionController@store')->name('financial-institutions.store');
    Route::resource('clients', 'ClientController');

    // Brand
    Route::delete('brands/destroy', 'BrandController@massDestroy')->name('brands.massDestroy');
    Route::post('brands/parse-csv-import', 'BrandController@parseCsvImport')->name('brands.parseCsvImport');
    Route::post('brands/process-csv-import', 'BrandController@processCsvImport')->name('brands.processCsvImport');
    Route::resource('brands', 'BrandController');

    // Vehicle
    Route::delete('vehicles/destroy', 'VehicleController@massDestroy')->name('vehicles.massDestroy');
    Route::post('vehicles/media', 'VehicleController@storeMedia')->name('vehicles.storeMedia');
    Route::post('vehicles/ckmedia', 'VehicleController@storeCKEditorImages')->name('vehicles.storeCKEditorImages');
    Route::delete('vehicles/{vehicle}/supplier-payments/{payment}', 'VehicleController@destroySupplierPayment')->name('vehicles.supplier-payments.destroy');
    Route::delete('vehicles/{vehicle}/generic-payments/{payment}', 'VehicleController@destroyGenericPayment')->name('vehicles.generic-payments.destroy');
    Route::delete('vehicles/{vehicle}/client-payments/{payment}', 'VehicleController@destroyClientPayment')->name('vehicles.client-payments.destroy');
    Route::post('vehicles/{vehicle}/send-to-workshop', 'VehicleController@sendToWorkshop')->name('vehicles.send-to-workshop');
    Route::post('vehicles/{vehicle}/suspended-sale', 'VehicleController@suspendSale')->name('vehicles.suspended-sale.store');
    Route::delete('vehicles/{vehicle}/suspended-sale', 'VehicleController@cancelSuspendedSale')->name('vehicles.suspended-sale.destroy');
    Route::get('vehicle-trade-ins', 'VehicleTradeInController@index')->name('vehicle-trade-ins.index');
    Route::get('vehicle-trade-ins/create', 'VehicleTradeInController@create')->name('vehicle-trade-ins.create');
    Route::post('vehicle-trade-ins', 'VehicleTradeInController@storeStandalone')->name('vehicle-trade-ins.store');
    Route::get('vehicle-trade-ins/pending', 'VehicleTradeInController@pending')->name('vehicle-trade-ins.pending');
    Route::post('vehicles/{vehicle}/trade-ins', 'VehicleTradeInController@store')->name('vehicles.trade-ins.store');
    Route::post('vehicle-trade-ins/{tradeIn}/convert', 'VehicleTradeInController@convert')->name('vehicle-trade-ins.convert');
    Route::post('vehicle-trade-ins/{tradeIn}/reject', 'VehicleTradeInController@reject')->name('vehicle-trade-ins.reject');
    Route::get('stand-cash-payment-approvals', 'StandCashPaymentApprovalController@index')->name('stand-cash-payment-approvals.index');
    Route::post('stand-cash-payment-approvals/{approval}/approve', 'StandCashPaymentApprovalController@approve')->name('stand-cash-payment-approvals.approve');
    Route::post('stand-cash-payment-approvals/{approval}/reject', 'StandCashPaymentApprovalController@reject')->name('stand-cash-payment-approvals.reject');
    Route::get('sale-closure-approvals', 'SaleClosureApprovalController@index')->name('sale-closure-approvals.index');
    Route::get('sale-closure-approvals/export', 'SaleClosureApprovalController@export')->name('sale-closure-approvals.export');
    Route::post('sale-closure-approvals/{approval}/approve', 'SaleClosureApprovalController@approve')->name('sale-closure-approvals.approve');
    Route::post('sale-closure-approvals/{approval}/reject', 'SaleClosureApprovalController@reject')->name('sale-closure-approvals.reject');
    Route::resource('vehicles', 'VehicleController');
    Route::get('vehicles/{vehicle}/timeline', 'VehicleTimelineController@show')->name('vehicles.timeline');
    Route::get('vehicles/{vehicle}/timeline/export/pdf', 'VehicleTimelineExportController@exportPdf')->name('vehicles.timeline.export.pdf');

    // Vehicle Consignments
    Route::resource('vehicle-consignments', 'VehicleConsignmentController')->except(['destroy']);

    // Vehicle Groups
    Route::delete('vehicle-groups/destroy', 'VehicleGroupController@massDestroy')->name('vehicle-groups.massDestroy');
    Route::post('vehicle-groups/{vehicleGroup}/approve', 'VehicleGroupController@approveLot')->name('vehicle-groups.approve');
    Route::post('vehicle-groups/{vehicleGroup}/payments', 'VehicleGroupController@storePayment')->name('vehicle-groups.payments.store');
    Route::post('vehicle-groups/{vehicleGroup}/payments/{payment}/approve', 'VehicleGroupController@approvePayment')->name('vehicle-groups.payments.approve');
    Route::post('vehicle-groups/{vehicleGroup}/payments/{payment}/reject', 'VehicleGroupController@rejectPayment')->name('vehicle-groups.payments.reject');
    Route::resource('vehicle-groups', 'VehicleGroupController');

    // Suplier
    Route::delete('supliers/destroy', 'SuplierController@massDestroy')->name('supliers.massDestroy');
    Route::post('supliers/parse-csv-import', 'SuplierController@parseCsvImport')->name('supliers.parseCsvImport');
    Route::post('supliers/process-csv-import', 'SuplierController@processCsvImport')->name('supliers.processCsvImport');
    Route::resource('supliers', 'SuplierController');

    // Payment Status
    Route::delete('payment-statuses/destroy', 'PaymentStatusController@massDestroy')->name('payment-statuses.massDestroy');
    Route::post('payment-statuses/parse-csv-import', 'PaymentStatusController@parseCsvImport')->name('payment-statuses.parseCsvImport');
    Route::post('payment-statuses/process-csv-import', 'PaymentStatusController@processCsvImport')->name('payment-statuses.processCsvImport');
    Route::resource('payment-statuses', 'PaymentStatusController');

    // Carrier
    Route::delete('carriers/destroy', 'CarrierController@massDestroy')->name('carriers.massDestroy');
    Route::post('carriers/parse-csv-import', 'CarrierController@parseCsvImport')->name('carriers.parseCsvImport');
    Route::post('carriers/process-csv-import', 'CarrierController@processCsvImport')->name('carriers.processCsvImport');
    Route::resource('carriers', 'CarrierController');

    // Pickup State
    Route::delete('pickup-states/destroy', 'PickupStateController@massDestroy')->name('pickup-states.massDestroy');
    Route::post('pickup-states/parse-csv-import', 'PickupStateController@parseCsvImport')->name('pickup-states.parseCsvImport');
    Route::post('pickup-states/process-csv-import', 'PickupStateController@processCsvImport')->name('pickup-states.processCsvImport');
    Route::resource('pickup-states', 'PickupStateController');
    
    // Sales
    Route::prefix('sales')->group(function() {
        Route::get('/{general_state_id?}', 'SalesController@index')->name('sales.index');
        Route::get('create', 'SalesController@create')->name('sales.create');
    });

    // Repair
    Route::delete('repairs/destroy', 'RepairController@massDestroy')->name('repairs.massDestroy');
    Route::post('repairs/parse-csv-import', 'RepairController@parseCsvImport')->name('repairs.parseCsvImport');
    Route::post('repairs/process-csv-import', 'RepairController@processCsvImport')->name('repairs.processCsvImport');
    Route::post('repairs/media', 'RepairController@storeMedia')->name('repairs.storeMedia');
    Route::post('repairs/ckmedia', 'RepairController@storeCKEditorImages')->name('repairs.storeCKEditorImages');
    Route::post('repairs/{repair}/new-intervention', 'RepairController@newIntervention')->name('repairs.newIntervention');
    Route::post('repairs/{repair}/start', 'RepairController@startRepair')->name('repairs.start');
    Route::post('repairs/{repair}/finish', 'RepairController@finishRepair')->name('repairs.finish');
    Route::post('repairs/{repair}/reopen', 'RepairController@reopenRepair')->name('repairs.reopen');
    Route::post('repairs/{repair}/work/start', 'RepairController@startWork')->name('repairs.work.start');
    Route::post('repairs/{repair}/work/finish', 'RepairController@finishWork')->name('repairs.work.finish');
    Route::resource('repairs', 'RepairController');
    Route::get('repair-parts-report', 'RepairPartsReportController@index')->name('repair-parts-report.index');
    Route::post('part-orders/{partOrder}/items/{item}/quotes', 'PartOrderController@storeQuote')->name('part-orders.items.quotes.store');
    Route::post('part-orders/{partOrder}/items/{item}/quotes/{quote}/select', 'PartOrderController@selectQuote')->name('part-orders.items.quotes.select');
    Route::resource('part-orders', 'PartOrderController');
    Route::resource('external-services', 'ExternalServiceController')->except(['show']);
    Route::patch('oficina-expertise-processes/{oficina_expertise_process}/status', 'OficinaExpertiseProcessController@updateStatus')->name('oficina-expertise-processes.update-status');
    Route::resource('oficina-expertise-processes', 'OficinaExpertiseProcessController');
    Route::post('workshop-interventions/{workshopIntervention}/start', 'WorkshopInterventionController@start')->name('workshop-interventions.start');
    Route::post('workshop-interventions/{workshopIntervention}/finish', 'WorkshopInterventionController@finish')->name('workshop-interventions.finish');
    Route::post('workshop-interventions/{workshopIntervention}/complete', 'WorkshopInterventionController@complete')->name('workshop-interventions.complete');
    Route::resource('workshop-interventions', 'WorkshopInterventionController')
        ->parameters(['workshop-interventions' => 'workshopIntervention'])
        ->except(['show']);
    Route::resource('workshop-intervention-types', 'WorkshopInterventionTypeController')
        ->parameters(['workshop-intervention-types' => 'workshopInterventionType'])
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('part-payments', 'PartPaymentController');
    Route::resource('part-receipts', 'PartReceiptController');

    // Create Car For Repair
    Route::get('create-car-for-repairs', 'CreateCarForRepairController@index')->name('create-car-for-repairs.index');
    Route::post('create-car-for-repairs', 'CreateCarForRepairController@store')->name('create-car-for-repairs.store');

    // Repair States
    Route::delete('repair-states/destroy', 'RepairStatesController@massDestroy')->name('repair-states.massDestroy');
    Route::post('repair-states/parse-csv-import', 'RepairStatesController@parseCsvImport')->name('repair-states.parseCsvImport');
    Route::post('repair-states/process-csv-import', 'RepairStatesController@processCsvImport')->name('repair-states.processCsvImport');
    Route::resource('repair-states', 'RepairStatesController');

    // General State
    Route::delete('general-states/destroy', 'GeneralStateController@massDestroy')->name('general-states.massDestroy');
    Route::post('general-states/media', 'GeneralStateController@storeMedia')->name('general-states.storeMedia');
    Route::post('general-states/ckmedia', 'GeneralStateController@storeCKEditorImages')->name('general-states.storeCKEditorImages');
    Route::post('general-states/reorder', 'GeneralStateController@reorder')->name('general-states.reorder');
    Route::resource('general-states', 'GeneralStateController');
    Route::get('vehicle-state-transfers', 'VehicleStateTransferController@index')->name('vehicle-state-transfers.index');
    Route::post('vehicle-state-transfers/{transfer}/check', 'VehicleStateTransferController@check')->name('vehicle-state-transfers.check');
    // Audit Logs
    Route::resource('audit-logs', 'AuditLogsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

    // Payment Method
    Route::delete('payment-methods/destroy', 'PaymentMethodController@massDestroy')->name('payment-methods.massDestroy');
    Route::resource('payment-methods', 'PaymentMethodController');

    // Depreciation
    Route::delete('depreciations/destroy', 'DepreciationController@massDestroy')->name('depreciations.massDestroy');
    Route::post('depreciations/parse-csv-import', 'DepreciationController@parseCsvImport')->name('depreciations.parseCsvImport');
    Route::post('depreciations/process-csv-import', 'DepreciationController@processCsvImport')->name('depreciations.processCsvImport');
    Route::resource('depreciations', 'DepreciationController');

    // Appreciation
    Route::delete('appreciations/destroy', 'AppreciationController@massDestroy')->name('appreciations.massDestroy');
    Route::post('appreciations/parse-csv-import', 'AppreciationController@parseCsvImport')->name('appreciations.parseCsvImport');
    Route::post('appreciations/process-csv-import', 'AppreciationController@processCsvImport')->name('appreciations.processCsvImport');
    Route::resource('appreciations', 'AppreciationController');
    
    Route::get('system-calendar', 'SystemCalendarController@index')->name('systemCalendar');
    Route::post('system-calendar/tasks', 'SystemCalendarController@storeTask')->name('systemCalendar.tasks.store');
    Route::post('system-calendar/tasks/{task}/complete', 'SystemCalendarController@completeTask')->name('systemCalendar.tasks.complete');
    Route::delete('system-calendar/tasks/{task}', 'SystemCalendarController@destroyTask')->name('systemCalendar.tasks.destroy');
    Route::get('global-search', 'GlobalSearchController@search')->name('globalSearch');
    Route::get('messenger', 'MessengerController@index')->name('messenger.index');
    Route::get('messenger/create', 'MessengerController@createTopic')->name('messenger.createTopic');
    Route::post('messenger', 'MessengerController@storeTopic')->name('messenger.storeTopic');
    Route::get('messenger/inbox', 'MessengerController@showInbox')->name('messenger.showInbox');
    Route::get('messenger/outbox', 'MessengerController@showOutbox')->name('messenger.showOutbox');
    Route::get('messenger/{topic}', 'MessengerController@showMessages')->name('messenger.showMessages');
    Route::delete('messenger/{topic}', 'MessengerController@destroyTopic')->name('messenger.destroyTopic');
    Route::post('messenger/{topic}/reply', 'MessengerController@replyToTopic')->name('messenger.reply');
    Route::get('messenger/{topic}/reply', 'MessengerController@showReply')->name('messenger.showReply');
});
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', 'ChangePasswordController@edit')->name('password.edit');
        Route::post('password', 'ChangePasswordController@update')->name('password.update');
        Route::post('profile', 'ChangePasswordController@updateProfile')->name('password.updateProfile');
        Route::post('profile/destroy', 'ChangePasswordController@destroy')->name('password.destroyProfile');
    }
});

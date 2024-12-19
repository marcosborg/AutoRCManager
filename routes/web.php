<?php

Route::redirect('/', '/login');
Route::get('/home', function () {
    if (session('status')) {
        return redirect()->route('admin.home')->with('status', session('status'));
    }

    return redirect()->route('admin.home');
});

Auth::routes(['register' => false]);

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
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
    Route::post('vehicles/parse-csv-import', 'VehicleController@parseCsvImport')->name('vehicles.parseCsvImport');
    Route::post('vehicles/process-csv-import', 'VehicleController@processCsvImport')->name('vehicles.processCsvImport');
    Route::resource('vehicles', 'VehicleController');

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

    // Acquisition
    Route::delete('acquisitions/destroy', 'AcquisitionController@massDestroy')->name('acquisitions.massDestroy');
    Route::resource('acquisitions', 'AcquisitionController');

    // Expedition
    Route::delete('expeditions/destroy', 'ExpeditionController@massDestroy')->name('expeditions.massDestroy');
    Route::resource('expeditions', 'ExpeditionController');

    // Sales
    Route::delete('sales/destroy', 'SalesController@massDestroy')->name('sales.massDestroy');
    Route::resource('sales', 'SalesController');

    Route::get('system-calendar', 'SystemCalendarController@index')->name('systemCalendar');
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

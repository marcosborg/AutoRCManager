<?php

use App\Models\VehiclePosition;

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    // Permissions
    Route::apiResource('permissions', 'PermissionsApiController');

    // Roles
    Route::apiResource('roles', 'RolesApiController');

    // Users
    Route::apiResource('users', 'UsersApiController');

    // Countries
    Route::apiResource('countries', 'CountriesApiController');

    // Client
    Route::apiResource('clients', 'ClientApiController');

    // Brand
    Route::apiResource('brands', 'BrandApiController');

    // Vehicle
    Route::post('vehicles/media', 'VehicleApiController@storeMedia')->name('vehicles.storeMedia');
    Route::apiResource('vehicles', 'VehicleApiController');

    // Suplier
    Route::apiResource('supliers', 'SuplierApiController');

    // Payment Status
    Route::apiResource('payment-statuses', 'PaymentStatusApiController');

    // Carrier
    Route::apiResource('carriers', 'CarrierApiController');

    // Pickup State
    Route::apiResource('pickup-states', 'PickupStateApiController');

    // Repair
    Route::apiResource('repairs', 'RepairApiController');

    // Repair States
    Route::apiResource('repair-states', 'RepairStatesApiController');

    // Ultimas posicoes por tracker (opcional simples).
    Route::get('gps/positions/{trackerId?}', function (?string $trackerId = null) {
        $latestIds = VehiclePosition::query()
            ->when($trackerId, fn ($query) => $query->where('tracker_id', $trackerId))
            ->selectRaw('MAX(id) as id')
            ->groupBy('tracker_id')
            ->pluck('id');

        $positions = VehiclePosition::whereIn('id', $latestIds)
            ->orderBy('tracker_id')
            ->get([
                'id',
                'tracker_id',
                'latitude',
                'longitude',
                'speed_kph',
                'fix_valid',
                'voltage',
                'reported_at',
                'created_at',
            ]);

        return response()->json($positions);
    })->name('gps.positions');
});

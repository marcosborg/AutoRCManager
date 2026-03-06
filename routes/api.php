<?php

use App\Models\VehiclePosition;
use App\Http\Controllers\Api\V1\Mobile\AuthApiController;
use App\Http\Controllers\Api\V1\Mobile\WorkshopApiController;

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

Route::prefix('mobile')->group(function () {
    Route::post('auth/login', [AuthApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthApiController::class, 'me']);
        Route::post('auth/logout', [AuthApiController::class, 'logout']);

        Route::get('workshop/repair-states', [WorkshopApiController::class, 'repairStates']);
        Route::get('workshop/repairs', [WorkshopApiController::class, 'repairs']);
        Route::get('workshop/repairs/{repair}', [WorkshopApiController::class, 'repair']);
        Route::put('workshop/repairs/{repair}', [WorkshopApiController::class, 'updateRepair']);
        Route::post('workshop/repairs/{repair}/start', [WorkshopApiController::class, 'startRepair']);
        Route::post('workshop/repairs/{repair}/finish', [WorkshopApiController::class, 'finishRepair']);
        Route::post('workshop/repairs/{repair}/work/start', [WorkshopApiController::class, 'startWork']);
        Route::post('workshop/repairs/{repair}/work/finish', [WorkshopApiController::class, 'finishWork']);
        Route::post('workshop/repairs/{repair}/parts', [WorkshopApiController::class, 'addPart']);
        Route::patch('workshop/repairs/{repair}/parts/{part}', [WorkshopApiController::class, 'updatePart']);
        Route::delete('workshop/repairs/{repair}/parts/{part}', [WorkshopApiController::class, 'deletePart']);
        Route::post('workshop/repairs/{repair}/media', [WorkshopApiController::class, 'uploadMedia']);
        Route::delete('workshop/repairs/{repair}/media/{mediaId}', [WorkshopApiController::class, 'deleteMedia']);

        Route::get('workshop/vehicles', [WorkshopApiController::class, 'vehicles']);
        Route::post('workshop/vehicles/{vehicle}/interventions', [WorkshopApiController::class, 'newIntervention']);
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\Auth\DeviceTokenController;
use App\Http\Controllers\Api\v1\Admin\AdminBookingController;
use App\Http\Controllers\Api\v1\Admin\NotificationController;
use App\Http\Controllers\Api\v1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\v1\Admin\AdminClientController;
use App\Http\Controllers\Api\v1\Admin\AdminWorkerController;
use App\Http\Controllers\Api\v1\Client\ClientBookingController;
use App\Http\Controllers\Api\v1\Client\ClientController;
use App\Http\Controllers\Api\v1\Worker\WorkerBookingController;
use App\Http\Controllers\Api\v1\Worker\WorkerController;
use App\Http\Controllers\Api\v1\CategoryController;


Route::get('/health', function (\Illuminate\Http\Request $request) {
    abort_unless($request->query('key') === 'warm123', 403);
    DB::select('SELECT 1');
    return response()->json(['status' => 'ok']);
});

/*

|--------------------------------------------------------------------------
| API V1 ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // PUBLIC ROUTES
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    

    // AUTHENTICATED ROUTES
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth / Profile
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password/change', [AuthController::class, 'changePassword']);
        
         Route::post('/save-token', [DeviceTokenController::class, 'store']);
        
        // 👑 ADMIN
        Route::prefix('admin')->middleware('admin')->group(function () {

        
         Route::get('notifications', [NotificationController::class, 'index']);
         Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

            Route::apiResource('workers', AdminWorkerController::class);
            Route::apiResource('clients', AdminClientController::class);
            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('bookings', AdminBookingController::class);
            
        });

        // 🧑 WORKER
        Route::prefix('worker')->middleware('role:worker')->group(function () {
            Route::get('/bookings', [WorkerBookingController::class, 'index']);
            Route::put('/bookings/{id}', [WorkerBookingController::class, 'update']);
            Route::get('/profile', [WorkerController::class, 'show']); 
        });

        // 👤 CLIENT
        Route::prefix('client')->middleware('role:client')->group(function () {
            Route::get('/bookings', [ClientBookingController::class, 'index']);
            Route::post('/bookings', [ClientBookingController::class, 'store']);
            Route::get('/bookings/{id}', [ClientBookingController::class, 'show']);
            Route::delete('/bookings/{id}', [ClientBookingController::class, 'destroy']);
            Route::get('/profile', [ClientController::class, 'show']);
        });
    });
});

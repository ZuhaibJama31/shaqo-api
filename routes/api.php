<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController; 
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\WorkerController as AdminWorkerController;
use App\Http\Controllers\Client\ClientBookingController;
use App\Http\Controllers\Client\ClientController as ClientProfileController;
use App\Http\Controllers\Worker\WorkerController as WorkerProfileController;
use App\Http\Controllers\Worker\BookingController as WorkerBookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\BookingController;

Route::get('/health', function (\Illuminate\Http\Request $request) {
    abort_unless($request->query('key') === 'warm123', 403);
    DB::select('SELECT 1');

    return response()->json(['status' => 'ok']);
});


/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);


// 🔒 AUTHENTICATED ROUTES (SANCTUM)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/passsword-reset', [AuthController::class, 'resetPassword']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);

    Route::get('/workers', [WorkerController::class, 'index']);
    
    Route::post('/workers/profile', [WorkerController::class, 'createOrUpdate']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
});


// 👑 ADMIN
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        Route::apiResource('workers', AdminWorkerController::class);
        Route::apiResource('clients', AdminClientController::class);
        Route::apiResource('categories', AdminCategoryController::class);
        Route::apiResource('bookings', AdminBookingController::class);
    });


// 🧑 WORKER
Route::prefix('worker')
    ->middleware(['auth:sanctum', 'role:worker'])
    ->group(function () {
        Route::get('/bookings', [WorkerBookingController::class, 'index']);
        Route::put('/bookings/{id}', [WorkerBookingController::class, 'update']);
        Route::get('/working-hours', [WorkerProfileController::class, 'index']);
    });


// 👤 CLIENT
Route::prefix('client')
    ->middleware(['auth:sanctum', 'role:client'])
    ->group(function () {

        Route::get('/bookings', [ClientBookingController::class, 'index']);
        Route::post('/bookings', [ClientBookingController::class, 'store']);
        Route::get('/bookings/{id}', [ClientBookingController::class, 'show']);
        Route::delete('/bookings/{id}', [ClientBookingController::class, 'destroy']);

        Route::get('/profile', [ClientProfileController::class, 'profile']);
    });
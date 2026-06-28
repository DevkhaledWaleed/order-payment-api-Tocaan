<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Authentication (Public) ───────────────────────────────────────────────────

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login',    [AuthController::class, 'login'])->name('login');
});

// ── Protected Routes ──────────────────────────────────────────────────────────

Route::middleware('auth:api')->group(function () {

    // Auth management
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('me',      [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('refresh',[AuthController::class, 'refresh'])->name('refresh');
    });
    Route::apiResource('orders', OrderController::class);

    Route::get('payments',         [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments',        [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}',[PaymentController::class, 'show'])->name('payments.show');

});

<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Plan\PlanController;
use App\Http\Controllers\Api\Subscription\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/logout', 'logout')->name('logout');
});

Route::prefix('plans')->name('plans.')->controller(PlanController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('/update/{id}', 'update')->name('update');
    Route::delete('/delete/{id}', 'delete')->name('delete');
    Route::get('/show/{id}', 'show')->name('show');
    Route::get('/deleted', 'showDeleted')->name('deleted');
    Route::post('/restore/{id}', 'restore')->name('restore');
    Route::get('/force-delete/{id}', 'forceDelete')->name('force-delete');
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('subscriptions')->name('subscriptions.')->controller(SubscriptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::delete('/delete/{id}', 'delete')->name('delete');
        Route::get('/deleted', 'showDeleted')->name('deleted');
        Route::post('/restore/{id}', 'restore')->name('restore');
        Route::get('/force-delete/{id}', 'forceDelete')->name('force-delete');
        Route::post('/cancel/{id}', 'cancel')->name('cancel');
    });
});

<?php

use App\Http\Controllers\Api\Plan\PlanController;
use Illuminate\Support\Facades\Route;

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

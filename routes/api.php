<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentTypeController;
use App\Http\Controllers\EvaluationMetricController;
use App\Http\Controllers\SedeController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

//    EQUIPMENTS
    Route::get('/equipment/useStateGraph', [EquipmentController::class, 'useStateGraph']);
    Route::get('/equipment/sedeGraph', [EquipmentController::class, 'sedeGraph']);
    Route::resource('equipment', EquipmentController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

//    TYPE EQUIPMENTS
    Route::resource('equipmentType', EquipmentTypeController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

//    PERFORMANCE EVALUATION
    Route::group(['prefix' => 'performanceEvaluation'], function () {
//        METRICS
        Route::resource('metric', EvaluationMetricController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
    });


//    SEDE
    Route::resource('sede', SedeController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

    // Add other routes that require authentication here
});

<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentTypeController;
use App\Http\Controllers\EvaluationCompetenceController;
use App\Http\Controllers\EvaluationMetricController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\ViewController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

//    GENERAL
//    SEDE
    Route::resource('company', CompanyController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

    Route::resource('sede', SedeController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

//    SYSTEM
    Route::group(['prefix' => 'configuration'], function () {
//        ROLES
        Route::resource('role', RoleController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        VIEWS
        Route::resource('view', ViewController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        ACCESS
        Route::resource('access', AccessController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
    });


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

//        COMPETENCES
        Route::resource('competence', EvaluationCompetenceController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
    });


    // Add other routes that require authentication here
});

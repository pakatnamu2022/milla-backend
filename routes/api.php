<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCompetenceController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationMetricController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationObjectiveController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationParameterController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPeriodController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\HierarchicalCategoryController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\HierarchicalCategoryDetailController;
use App\Http\Controllers\gp\gestionsistema\AccessController;
use App\Http\Controllers\gp\gestionsistema\CompanyController;
use App\Http\Controllers\gp\gestionsistema\PositionController;
use App\Http\Controllers\gp\gestionsistema\RoleController;
use App\Http\Controllers\gp\gestionsistema\SedeController;
use App\Http\Controllers\gp\gestionsistema\UserController;
use App\Http\Controllers\gp\gestionsistema\ViewController;
use App\Http\Controllers\gp\tics\EquipmentController;
use App\Http\Controllers\gp\tics\EquipmentTypeController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

//    GENERAL
//    SEDE
    Route::apiResource('company', CompanyController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

    Route::apiResource('sede', SedeController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

//    SYSTEM
    Route::group(['prefix' => 'configuration'], function () {
//        ROLES
        Route::apiResource('role', RoleController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
        Route::get('role/{id}/users', [RoleController::class, 'users'])->name('role.users');
        Route::post('/roles/{role_id}/access', [AccessController::class, 'storeMany']);


//        VIEWS
        Route::apiResource('view', ViewController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        POSITIONS
        Route::apiResource('position', PositionController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

        Route::get('modules', [AuthController::class, 'modules'])->name('modules');

//        ACCESS
        Route::apiResource('access', AccessController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        ACCESS
        Route::apiResource('user', UserController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
    });


//    EQUIPMENTS
    Route::get('/equipment/useStateGraph', [EquipmentController::class, 'useStateGraph']);
    Route::get('/equipment/sedeGraph', [EquipmentController::class, 'sedeGraph']);
    Route::apiResource('equipment', EquipmentController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

//    TYPE EQUIPMENTS
    Route::apiResource('equipmentType', EquipmentTypeController::class)->only([
        'index', 'show', 'store', 'update', 'destroy'
    ]);

//    PERFORMANCE EVALUATION
    Route::group(['prefix' => 'performanceEvaluation'], function () {
//        METRICS
        Route::apiResource('metric', EvaluationMetricController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        OBJECTIVES
        Route::apiResource('objective', EvaluationObjectiveController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        COMPETENCES
        Route::apiResource('competence', EvaluationCompetenceController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        PERIODS
        Route::apiResource('period', EvaluationPeriodController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        HIERARCHICAL CATEGORIES
        Route::apiResource('hierarchicalCategory', HierarchicalCategoryController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
        Route::post('/hierarchicalCategory/{category}/details', [HierarchicalCategoryDetailController::class, 'storeMany']);

//        PARAMETER
        Route::apiResource('parameter', EvaluationParameterController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        CYCLE
        Route::apiResource('cycle', EvaluationCycleController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);

//        CYCLE CATEGORIES
        Route::get('/cycle/{cycle}/categories', [EvaluationCycleCategoryDetailController::class, 'index']);
        Route::post('/cycle/{cycle}/categories', [EvaluationCycleCategoryDetailController::class, 'storeMany']);
        Route::delete('/cycle/{cycle}/categories/{category}', [EvaluationCycleCategoryDetailController::class, 'destroyCategory']);
    });


    // Add other routes that require authentication here
});

<?php

use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApClassArticleController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApCommercialMastersController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApFamiliesController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApFuelTypeController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApModelsVnController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApVehicleStatusController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApVehicleBrandController;
use App\Http\Controllers\ap\maestroGeneral\TypeCurrencyController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCompetenceController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationMetricController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationObjectiveController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationParameterController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPeriodController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\HierarchicalCategoryController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\HierarchicalCategoryDetailController;
use App\Http\Controllers\gp\gestionhumana\personal\PersonController;
use App\Http\Controllers\gp\gestionhumana\personal\WorkerController;
use App\Http\Controllers\gp\gestionsistema\AccessController;
use App\Http\Controllers\gp\gestionsistema\CompanyController;
use App\Http\Controllers\gp\gestionsistema\DigitalFileController;
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
    'index',
    'show',
    'store',
    'update',
    'destroy'
  ]);

  Route::apiResource('sede', SedeController::class)->only([
    'index',
    'show',
    'store',
    'update',
    'destroy'
  ]);

//  DIGITAL FILE
  Route::apiResource('digital-file', DigitalFileController::class)->only([
    'index',
    'show',
    'store',
    'destroy'
  ]);

  //    SYSTEM
  Route::group(['prefix' => 'configuration'], function () {
    //        ROLES
    Route::apiResource('role', RoleController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);
    Route::get('role/{id}/users', [RoleController::class, 'users'])->name('role.users');
    Route::post('/roles/{role_id}/access', [AccessController::class, 'storeMany']);


    //        VIEWS
    Route::apiResource('view', ViewController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    Route::get('modules', [AuthController::class, 'modules'])->name('modules');

    //        ACCESS
    Route::apiResource('access', AccessController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        ACCESS
    Route::apiResource('user', UserController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);
  });

  Route::group(['prefix' => 'person'], function () {
    Route::get('/birthdays', [PersonController::class, 'birthdays'])->name('person.birthdays');
    Route::apiResource('/', PersonController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);
  });


  //    EQUIPMENTS
  Route::get('/equipment/useStateGraph', [EquipmentController::class, 'useStateGraph']);
  Route::get('/equipment/sedeGraph', [EquipmentController::class, 'sedeGraph']);
  Route::apiResource('equipment', EquipmentController::class)->only([
    'index',
    'show',
    'store',
    'update',
    'destroy'
  ]);

  //    TYPE EQUIPMENTS
  Route::apiResource('equipmentType', EquipmentTypeController::class)->only([
    'index',
    'show',
    'store',
    'update',
    'destroy'
  ]);

  //    PERSONAL MAIN
  Route::group(['prefix' => 'personal'], function () {
    //        PERSON
    Route::apiResource('person', PersonController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

//    WORKER
    Route::apiResource('worker', WorkerController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        POSITIONS
    Route::apiResource('position', PositionController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);
  });

  //    PERFORMANCE EVALUATION
  Route::group(['prefix' => 'performanceEvaluation'], function () {
    //        METRICS
    Route::apiResource('metric', EvaluationMetricController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        OBJECTIVES
    Route::apiResource('objective', EvaluationObjectiveController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        COMPETENCES
    Route::apiResource('competence', EvaluationCompetenceController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        PERIODS
    Route::apiResource('period', EvaluationPeriodController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        HIERARCHICAL CATEGORIES
    Route::get('/hierarchicalCategory/listAll', [HierarchicalCategoryController::class, 'listAll']);
    Route::apiResource('hierarchicalCategory', HierarchicalCategoryController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    Route::post('/hierarchicalCategory/{category}/details', [HierarchicalCategoryDetailController::class, 'storeMany']);
    Route::apiResource('hierarchicalCategoryDetail', HierarchicalCategoryDetailController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    Route::get('/categoryObjectiveDetail/{category}/workers', [EvaluationCategoryObjectiveDetailController::class, 'workers']);
    Route::apiResource('categoryObjectiveDetail', EvaluationCategoryObjectiveDetailController::class)->only([
      'index',
      'show',
      'store',
      'update',
    ]);
    Route::post('/categoryObjectiveDetail/destroy', [EvaluationCategoryObjectiveDetailController::class, 'destroy']);

    //        PARAMETER
    Route::apiResource('parameter', EvaluationParameterController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        CYCLE
    Route::apiResource('cycle', EvaluationCycleController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        CYCLE CATEGORIES
    Route::get('/cycle/{cycle}/categories', [EvaluationCycleCategoryDetailController::class, 'index']);
    Route::post('/cycle/{cycle}/categories', [EvaluationCycleCategoryDetailController::class, 'storeMany']);
    Route::get('/cycle/{cycle}/details', [EvaluationPersonCycleDetailController::class, 'index']);
    Route::get('/cycle/{id}/participants', [EvaluationCycleController::class, 'participants']);
    Route::get('/cycle/{id}/positions', [EvaluationCycleController::class, 'positions']);


    //        PERSON CYCLE DETAILS
    Route::apiResource('personCycleDetail', EvaluationPersonCycleDetailController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        PERSON CYCLE DETAILS
    Route::apiResource('evaluationPersonDetail', EvaluationPersonDetailController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);
  });

  /**
   * Routes for Automotores Pakatnamu
   */
  Route::group(['prefix' => 'ap'], function () {
    Route::group(['prefix' => 'configuration'], function () {
      //        CONFIGURATION COMMERCIAL
      Route::group(['prefix' => 'commercial'], function () {
        Route::apiResource('fuelType', ApFuelTypeController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('vehicleStatus', ApVehicleStatusController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('commercialMasters', ApCommercialMastersController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('vehicleBrand', ApVehicleBrandController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('deliveryReceivingChecklist', ApDeliveryReceivingChecklistController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('families', ApFamiliesController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('typeCurrency', TypeCurrencyController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('classArticle', ApClassArticleController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
        Route::apiResource('modelsVn', ApModelsVnController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);
      });
      //        CONFIGURATION AFTER SALES
      Route::group(['prefix' => 'commercial'], function () {
      });
    });

    //      COMMERCIAL
    Route::group(['prefix' => 'commercial'], function () {
    });

    //      WORKSHOP
    Route::group(['prefix' => 'workshop'], function () {
    });

    //      STORAGE
    Route::group(['prefix' => 'storage'], function () {
    });
  });


  // Add other routes that require authentication here
});

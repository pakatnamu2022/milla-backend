<?php

use App\Http\Controllers\ap\ApCommercialMastersController;
use App\Http\Controllers\ap\comercial\OpportunityActionController;
use App\Http\Controllers\ap\comercial\OpportunityController;
use App\Http\Controllers\ap\comercial\PotentialBuyersController;
use App\Http\Controllers\ap\comercial\PurchaseRequestQuoteController;
use App\Http\Controllers\ap\comercial\VehiclePurchaseOrderController;
use App\Http\Controllers\ap\comercial\VehiclePurchaseOrderMigrationController;
use App\Http\Controllers\ap\comercial\VehicleVNController;
use App\Http\Controllers\ap\postventa\ApprovedAccessoriesController;
use App\Http\Controllers\AuditLogsController;
use App\Http\Controllers\DocumentValidationController;
use App\Http\Controllers\ap\comercial\BusinessPartnersController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApClassArticleController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklistController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApFamiliesController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApFuelTypeController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApModelsVnController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApVehicleBrandController;
use App\Http\Controllers\ap\configuracionComercial\vehiculo\ApVehicleStatusController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApAccountingAccountPlanController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApAssignBrandConsultantController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApAssignCompanyBranchController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApAssignmentLeadershipController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApBankController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApGoalSellOutInController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApSafeCreditGoalController;
use App\Http\Controllers\ap\configuracionComercial\venta\ApShopController;
use App\Http\Controllers\ap\maestroGeneral\AssignSalesSeriesController;
use App\Http\Controllers\ap\maestroGeneral\TaxClassTypesController;
use App\Http\Controllers\ap\maestroGeneral\TypeCurrencyController;
use App\Http\Controllers\ap\maestroGeneral\UnitMeasurementController;
use App\Http\Controllers\ap\maestroGeneral\UserSeriesAssignmentController;
use App\Http\Controllers\ap\maestroGeneral\WarehouseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCompetenceController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationMetricController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationObjectiveController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationParameterController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPeriodController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationPersonResultController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\HierarchicalCategoryController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\HierarchicalCategoryDetailController;
use App\Http\Controllers\gp\gestionhumana\personal\PersonController;
use App\Http\Controllers\gp\gestionhumana\personal\WorkerController;
use App\Http\Controllers\gp\gestionsistema\AccessController;
use App\Http\Controllers\gp\gestionsistema\CompanyController;
use App\Http\Controllers\gp\gestionsistema\DepartmentController;
use App\Http\Controllers\gp\gestionsistema\DigitalFileController;
use App\Http\Controllers\gp\gestionsistema\DistrictController;
use App\Http\Controllers\gp\gestionsistema\PositionController;
use App\Http\Controllers\gp\gestionsistema\ProvinceController;
use App\Http\Controllers\gp\gestionsistema\RoleController;
use App\Http\Controllers\gp\gestionsistema\UserController;
use App\Http\Controllers\gp\gestionsistema\UserSedeController;
use App\Http\Controllers\gp\gestionsistema\ViewController;
use App\Http\Controllers\gp\maestroGeneral\SedeController;
use App\Http\Controllers\gp\tics\EquipmentController;
use App\Http\Controllers\gp\tics\EquipmentTypeController;
use App\Http\Controllers\Api\EmailTestController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

// Email testing routes (sin autenticación para facilitar pruebas)
Route::group(['prefix' => 'email/test'], function () {
  Route::get('/status', [App\Http\Controllers\Api\EmailTestController::class, 'status']);
  Route::post('/evaluation-reminder', [App\Http\Controllers\Api\EmailTestController::class, 'testEvaluationReminder']);
  Route::post('/basic-template', [App\Http\Controllers\Api\EmailTestController::class, 'testBasicTemplate']);
  Route::post('/notification-template', [App\Http\Controllers\Api\EmailTestController::class, 'testNotificationTemplate']);
});

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

    //        USERS
    Route::get('user/{user}/complete', [UserController::class, 'showComplete'])->name('user.showComplete');
    Route::apiResource('user', UserController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    //        USER-SEDE ASSIGNMENT
    Route::post('user-sede/store-many', [UserSedeController::class, 'storeMany'])->name('user-sede.store-many');
    Route::get('user-sede/user/{userId}/sedes', [UserSedeController::class, 'getSedesByUser'])->name('user-sede.sedes-by-user');
    Route::get('user-sede/sede/{sedeId}/users', [UserSedeController::class, 'getUsersBySede'])->name('user-sede.users-by-sede');
    Route::apiResource('user-sede', UserSedeController::class)->only([
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


  Route::group(['prefix' => 'gp'], function () {
    Route::group(['prefix' => 'mg'], function () {
      Route::get('sede/availableLocationsShop', [SedeController::class, 'availableLocationsShop']);
      Route::get('sede/my', [SedeController::class, 'mySedes']);
      Route::apiResource('sede', SedeController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);
    });

    Route::group(['prefix' => 'gs'], function () {

      Route::get('/department', [DepartmentController::class, 'index']);
      Route::get('/province', [ProvinceController::class, 'index']);
      Route::apiResource('district', DistrictController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);
    });

    Route::group(['prefix' => 'gh'], function () {
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
        Route::get('worker-without-categories-and-objectives', [WorkerController::class, 'getWorkersWithoutCategoriesAndObjectives']);
        Route::get('worker-without-objectives', [WorkerController::class, 'getWorkersWithoutObjectives']);
        Route::get('worker-without-categories', [WorkerController::class, 'getWorkersWithoutCategories']);
        Route::get('worker-without-competences', [WorkerController::class, 'getWorkersWithoutCompetences']);
        Route::post('worker-assign-objectives', [WorkerController::class, 'assignObjectivesToWorkers']);

        Route::apiResource('worker', WorkerController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        //      POSITIONS
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
        Route::get('metric/export', [EvaluationMetricController::class, 'export']);
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

        //    CATEGORY OBJECTIVE DETAILS
        Route::get('/categoryObjectiveDetail/{category}/workers', [EvaluationCategoryObjectiveDetailController::class, 'workers']);
        Route::apiResource('categoryObjectiveDetail', EvaluationCategoryObjectiveDetailController::class)->only([
          'index',
          'show',
          'store',
          'update',
        ]);
        Route::post('/categoryObjectiveDetail/destroy', [EvaluationCategoryObjectiveDetailController::class, 'destroy']);

        //    CATEGORY COMPETENCE DETAILS
        Route::get('/categoryCompetenceDetail/{category}/workers', [EvaluationCategoryCompetenceDetailController::class, 'workers']);
        Route::apiResource('categoryCompetenceDetail', EvaluationCategoryCompetenceDetailController::class)->only([
          'index',
          'show',
          'store',
          'update',
        ]);
        Route::post('/categoryCompetenceDetail/destroy', [EvaluationCategoryCompetenceDetailController::class, 'destroy']);

        //        PARAMETER
        Route::apiResource('parameter', EvaluationParameterController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        //        CYCLE
        Route::get('cycle/export', [EvaluationCycleController::class, 'export']);
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

        //        EVALUATION
        Route::get('/evaluation/export', [EvaluationController::class, 'export']);
        Route::get('/evaluation/check', [EvaluationController::class, 'checkActiveEvaluationByDateRange']);
        Route::get('/evaluation/active', [EvaluationController::class, 'active']);
        Route::post('/evaluation/{evaluation}/regenerateEvaluation', [EvaluationController::class, 'regenerateEvaluation']);
        Route::get('/evaluation/{evaluation}/participants', [EvaluationController::class, 'participants']);
        Route::get('/evaluation/{evaluation}/positions', [EvaluationController::class, 'positions']);
        Route::get('evaluation/{id}/testUpdateAllResultsWithGoals', [EvaluationPersonController::class, 'testUpdateAllResultsWithGoals']);

        //        EVALUATION NOTIFICATIONS
        Route::group(['prefix' => 'evaluation/notifications'], function () {
          Route::post('/send-reminders', [App\Http\Controllers\Api\EvaluationNotificationController::class, 'sendReminders']);
          Route::post('/send-hr-summary', [App\Http\Controllers\Api\EvaluationNotificationController::class, 'sendHrSummary']);
          Route::get('/pending-status', [App\Http\Controllers\Api\EvaluationNotificationController::class, 'getPendingStatus']);
          Route::post('/test-reminder', [App\Http\Controllers\Api\EvaluationNotificationController::class, 'testReminder']);
        });

        Route::apiResource('evaluation', EvaluationController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        Route::post('/evaluation/{evaluation}/competences', [EvaluationController::class, 'createCompetences'])
          ->name('evaluation.competences.create');

        //        EVALUATION PERSON
        Route::apiResource('evaluationPerson', EvaluationPersonController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        //        PERSON RESULT
        Route::get('personResult/export', [EvaluationPersonResultController::class, 'export']);
        Route::get('personResult/getByPersonAndEvaluation', [EvaluationPersonResultController::class, 'getByPersonAndEvaluation']);
        Route::get('personResult/getTeamByChief/{chief}', [EvaluationPersonResultController::class, 'getTeamByChief']);
        Route::post('personResult/regenerate/{personId}/{evaluationId}', [EvaluationPersonResultController::class, 'regenerate']);
        Route::apiResource('personResult', EvaluationPersonResultController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        // Agregar estas rutas dentro del grupo performanceEvaluation en routes/api.php

        Route::apiResource('personCompetenceDetail', EvaluationPersonCompetenceDetailController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        // Rutas adicionales para recálculo de resultados
        Route::post('/evaluation/{evaluation}/recalculateResults', [EvaluationPersonController::class, 'recalculateAllResults']);
        Route::get('/evaluation/{evaluation}/stats', [EvaluationPersonController::class, 'getEvaluationStats']);

        // Ruta para crear competencias en lote
        Route::post('/evaluation/{evaluation}/storeMany', [EvaluationPersonResultController::class, 'storeMany']);
      });
    });
  });


  /**
   * Routes for Automotores Pakatnamu
   */
  Route::group(['prefix' => 'ap'], function () {
    Route::apiResource('commercialMasters', ApCommercialMastersController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);
    Route::group(['prefix' => 'configuration'], function () {
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

      Route::apiResource('assignCompanyBranch', ApAssignCompanyBranchController::class)->only([
        'index',
        'show',
        'store',
        'update',
      ]);
      Route::get('assignCompanyBranch/{sedeId}/workers', [ApAssignCompanyBranchController::class, 'getWorkersBySede']);

      Route::get('assignBrandConsultant/showGrouped', [ApAssignBrandConsultantController::class, 'showGrouped']);
      Route::get('assignBrandConsultant/{sedeId}/brands', [ApAssignBrandConsultantController::class, 'getBrandsByBranch']);
      Route::get('assignBrandConsultant/{sedeId}/brands/{brandId}/advisors', [ApAssignBrandConsultantController::class, 'getAdvisorsByBranchAndBrand']);
      Route::apiResource('assignBrandConsultant', ApAssignBrandConsultantController::class)->only([
        'index',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('bankAp', ApBankController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::get('assignBrandConsultant/showGrouped', [ApAssignBrandConsultantController::class, 'showGrouped']);
      Route::apiResource('assignBrandConsultant', ApAssignBrandConsultantController::class)->only([
        'index',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('bankAp', ApBankController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('accountingAccountPlan', ApAccountingAccountPlanController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::get('apGoalSellOutIn/report', [ApGoalSellOutInController::class, 'report']);
      Route::get('apGoalSellOutIn/report/pdf', [ApGoalSellOutInController::class, 'reportPDF']); // Descargar
      Route::apiResource('apGoalSellOutIn', ApGoalSellOutInController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('assignmentLeadership', ApAssignmentLeadershipController::class)->only([
        'index',
        'show',
        'store',
        'update',
      ]);

      Route::apiResource('commercialManagerBrandGroup', ApCommercialManagerBrandGroupController::class)->only([
        'index',
        'show',
        'store',
        'update',
      ]);

      Route::apiResource('taxClassTypes', TaxClassTypesController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('assignSalesSeries', AssignSalesSeriesController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('unitMeasurement', UnitMeasurementController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('userSeriesAssignment', UserSeriesAssignmentController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::get('warehouse/my', [WarehouseController::class, 'myWarehouses']);
      Route::apiResource('warehouse', WarehouseController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('shop', ApShopController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('apSafeCreditGoal', ApSafeCreditGoalController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);
    });

    //      COMMERCIAL
    Route::group(['prefix' => 'commercial'], function () {
      Route::apiResource('businessPartners', BusinessPartnersController::class)->only([
        'index',
        'show',
        'store',
        'update',
      ]);
      Route::patch('businessPartners/{id}/remove-type', [BusinessPartnersController::class, 'removeType']);
      Route::get('businessPartners/{id}/establishments', [BusinessPartnersController::class, 'establishments']);
      Route::get('businessPartners/{id}/validateOpportunity', [BusinessPartnersController::class, 'validateOpportunity']);

      // Crear oportunidad desde un cliente
      Route::post('businessPartners/{clientId}/opportunities', [OpportunityController::class, 'storeFromClient']);


      Route::get('potentialBuyers/my', [PotentialBuyersController::class, 'myPotentialBuyers']);
      Route::put('potentialBuyers/{id}/discard', [PotentialBuyersController::class, 'discard']);
      Route::apiResource('potentialBuyers', PotentialBuyersController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);
      Route::post('potentialBuyers/import-derco', [PotentialBuyersController::class, 'importDerco']);
      Route::post('potentialBuyers/import-social-networks', [PotentialBuyersController::class, 'importSocialNetworks']);
      Route::post('potentialBuyers/assign-workers', [PotentialBuyersController::class, 'assignWorkers']);

      Route::apiResource('vehicleVN', VehicleVNController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Rutas especiales de oportunidades (deben ir antes del apiResource)
      Route::get('opportunities/my', [OpportunityController::class, 'myOpportunities']);
      Route::get('opportunities/agenda/my', [OpportunityController::class, 'myAgenda']);
      Route::get('opportunities/{opportunityId}/actions', [OpportunityController::class, 'getActions']);
      Route::put('opportunities/{opportunityId}/close', [OpportunityController::class, 'close']);

      Route::apiResource('opportunities', OpportunityController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('opportunityActions', OpportunityActionController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('purchaseRequestQuote', PurchaseRequestQuoteController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::apiResource('vehiclePurchaseOrder', VehiclePurchaseOrderController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Vehicle Purchase Order Migration Monitoring
      Route::group(['prefix' => 'vehiclePurchaseOrder/migration'], function () {
        Route::get('/summary', [VehiclePurchaseOrderMigrationController::class, 'summary']);
        Route::get('/statistics', [VehiclePurchaseOrderMigrationController::class, 'statistics']);
        Route::get('/orders', [VehiclePurchaseOrderMigrationController::class, 'index']);
        Route::get('/{id}/logs', [VehiclePurchaseOrderMigrationController::class, 'logs']);
        Route::get('/{id}/history', [VehiclePurchaseOrderMigrationController::class, 'history']);
      });
    });

    //      POST-VENTA
    Route::group(['prefix' => 'postVenta'], function () {
      Route::apiResource('ApprovedAccessories', ApprovedAccessoriesController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);
    });
  });

  // Document Validation Routes
  Route::group(['prefix' => 'document-validation'], function () {
    Route::post('/validate/general', [DocumentValidationController::class, 'validateGeneral']);
    Route::post('/validate/dni', [DocumentValidationController::class, 'validateDni']);
    Route::post('/validate/ruc', [DocumentValidationController::class, 'validateRuc']);
    Route::post('/validate/license', [DocumentValidationController::class, 'validateLicense']);
    Route::get('/document-types', [DocumentValidationController::class, 'documentTypes']);
    Route::get('/provider-info', [DocumentValidationController::class, 'providerInfo']);
    Route::delete('/cache', [DocumentValidationController::class, 'clearCache']);
    Route::delete('/cache/all', [DocumentValidationController::class, 'clearAllCache']);
  });

  // Audit Logs Routes
  Route::group(['prefix' => 'audit-logs'], function () {
    Route::get('/', [AuditLogsController::class, 'index']);
    Route::get('/stats', [AuditLogsController::class, 'stats']);
    Route::get('/user/{userId}', [AuditLogsController::class, 'userLogs']);
    Route::get('/model/{model}/{id}', [AuditLogsController::class, 'modelLogs']);
    Route::get('/export', [AuditLogsController::class, 'export']);
    Route::delete('/clean', [AuditLogsController::class, 'clean']);
  });
});

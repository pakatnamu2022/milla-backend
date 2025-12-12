<?php

use App\Http\Controllers\ap\ApCommercialMastersController;
use App\Http\Controllers\ap\ApPostVentaMastersController;
use App\Http\Controllers\ap\comercial\ApDailyDeliveryReportController;
use App\Http\Controllers\ap\comercial\ApReceivingChecklistController;
use App\Http\Controllers\ap\comercial\ApVehicleDeliveryController;
use App\Http\Controllers\ap\comercial\BusinessPartnersController;
use App\Http\Controllers\ap\comercial\BusinessPartnersEstablishmentController;
use App\Http\Controllers\ap\comercial\OpportunityActionController;
use App\Http\Controllers\ap\comercial\OpportunityController;
use App\Http\Controllers\ap\comercial\PotentialBuyersController;
use App\Http\Controllers\ap\comercial\PurchaseRequestQuoteController;
use App\Http\Controllers\ap\comercial\ShippingGuidesController;
use App\Http\Controllers\ap\comercial\VehiclePurchaseOrderMigrationController;
use App\Http\Controllers\ap\comercial\VehiclesController;
use App\Http\Controllers\ap\compras\PurchaseOrderController;
use App\Http\Controllers\ap\compras\PurchaseReceptionController;
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
use App\Http\Controllers\ap\facturacion\AccountingEntryController;
use App\Http\Controllers\ap\facturacion\BillingCatalogController;
use App\Http\Controllers\ap\facturacion\ElectronicDocumentController;
use App\Http\Controllers\ap\maestroGeneral\AssignSalesSeriesController;
use App\Http\Controllers\ap\maestroGeneral\TaxClassTypesController;
use App\Http\Controllers\ap\maestroGeneral\TypeCurrencyController;
use App\Http\Controllers\ap\maestroGeneral\UnitMeasurementController;
use App\Http\Controllers\ap\maestroGeneral\UserSeriesAssignmentController;
use App\Http\Controllers\ap\maestroGeneral\WarehouseController;
use App\Http\Controllers\ap\postventa\gestionProductos\InventoryMovementController;
use App\Http\Controllers\ap\postventa\gestionProductos\ProductsController;
use App\Http\Controllers\ap\postventa\gestionProductos\ProductWarehouseStockController;
use App\Http\Controllers\ap\postventa\gestionProductos\TransferReceptionController;
use App\Http\Controllers\ap\postventa\repuestos\ApprovedAccessoriesController;
use App\Http\Controllers\ap\postventa\taller\ApOrderPurchaseRequestsController;
use App\Http\Controllers\ap\postventa\taller\ApOrderQuotationDetailsController;
use App\Http\Controllers\ap\postventa\taller\ApOrderQuotationsController;
use App\Http\Controllers\ap\postventa\taller\AppointmentPlanningController;
use App\Http\Controllers\ap\postventa\taller\ApVehicleInspectionController;
use App\Http\Controllers\ap\postventa\taller\ApWorkOrderAssignOperatorController;
use App\Http\Controllers\ap\postventa\taller\ApWorkOrderPartsController;
use App\Http\Controllers\ap\postventa\taller\WorkOrderController;
use App\Http\Controllers\ap\postventa\taller\WorkOrderItemController;
use App\Http\Controllers\AuditLogsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard\ap\comercial\DashboardComercialController;
use App\Http\Controllers\DocumentValidationController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\DetailedDevelopmentPlanController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCompetenceController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationCycleController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationMetricController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationModelController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationNotificationController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationObjectiveController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationParameterController;
use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationParEvaluatorController;
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
use App\Http\Controllers\gp\gestionhumana\viaticos\ExpenseTypeController;
use App\Http\Controllers\gp\gestionhumana\viaticos\HotelAgreementController;
use App\Http\Controllers\gp\gestionhumana\viaticos\HotelReservationController;
use App\Http\Controllers\gp\gestionhumana\viaticos\PerDiemApprovalController;
use App\Http\Controllers\gp\gestionhumana\viaticos\PerDiemCategoryController;
use App\Http\Controllers\gp\gestionhumana\viaticos\PerDiemExpenseController;
use App\Http\Controllers\gp\gestionhumana\viaticos\PerDiemPolicyController;
use App\Http\Controllers\gp\gestionhumana\viaticos\PerDiemRequestController;
use App\Http\Controllers\gp\gestionsistema\AccessController;
use App\Http\Controllers\gp\gestionsistema\AreaController;
use App\Http\Controllers\gp\gestionsistema\CompanyController;
use App\Http\Controllers\gp\gestionsistema\DepartmentController;
use App\Http\Controllers\gp\gestionsistema\DigitalFileController;
use App\Http\Controllers\gp\gestionsistema\DistrictController;
use App\Http\Controllers\gp\gestionsistema\PermissionController;
use App\Http\Controllers\gp\gestionsistema\PositionController;
use App\Http\Controllers\gp\gestionsistema\ProvinceController;
use App\Http\Controllers\gp\gestionsistema\RoleController;
use App\Http\Controllers\gp\gestionsistema\TypeOnboardingController;
use App\Http\Controllers\gp\gestionsistema\UserController;
use App\Http\Controllers\gp\gestionsistema\UserSedeController;
use App\Http\Controllers\gp\gestionsistema\ViewController;
use App\Http\Controllers\gp\maestroGeneral\ExchangeRateController;
use App\Http\Controllers\gp\maestroGeneral\SedeController;
use App\Http\Controllers\gp\maestroGeneral\SunatConceptsController;
use App\Http\Controllers\gp\tics\EquipmentController;
use App\Http\Controllers\gp\tics\EquipmentTypeController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:sanctum'])->group(callback: function () {
  Route::get('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
  Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
  Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::get('view/with-permissions', [ViewController::class, 'viewsWithPermissions'])->name('view.with-permissions');
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

    // PERMISSIONS
    Route::get('permission', [PermissionController::class, 'index'])->name('permission.index');
    Route::get('permission/available-actions', [PermissionController::class, 'getAvailableActions'])->name('permission.available-actions');
    Route::get('permission/{id}/get-by-role', [PermissionController::class, 'getByRole'])->name('permission.getByRole');
    Route::post('permission/bulk-sync', [PermissionController::class, 'bulkSync'])->name('permission.bulk-sync');
    Route::post('permission/save-permissions-to-role', [PermissionController::class, 'saveToRole'])->name('permission.savePermissionsToRole');
    Route::delete('permission/remove-permission-from-role', [PermissionController::class, 'removeFromRole'])->name('permission.removePermissionFromRole');
  });

  Route::group(['prefix' => 'configuration'], function () {
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

  Route::group(['prefix' => 'gp'], function () {
//    TICS
    Route::group(['prefix' => 'tics'], function () {
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
    });

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

      Route::apiResource('sunatConcepts', SunatConceptsController::class)->only([
        'index',
      ]);

      Route::get('exchange-rate/by-date-and-currency', [ExchangeRateController::class, 'getByDateAndCurrency']);
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
        Route::get('worker/my-consultants', [WorkerController::class, 'myConsultants']);

        Route::get('worker/revalidate', [WorkerController::class, 'revalidate']);
        Route::apiResource('worker', WorkerController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        //      AREAS
        Route::apiResource('area', AreaController::class)->only([
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

        //      TYPE ONBOARDING
        Route::apiResource('type-onboarding', TypeOnboardingController::class)->only([
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
          Route::post('/send-opened', [EvaluationNotificationController::class, 'sendEvaluationOpened']); // Notifica apertura de evaluación - Correo 1
          Route::post('/send-reminders', [EvaluationNotificationController::class, 'sendReminders']); // Es correo de recordatorio - Correo 2
          Route::post('/send-closed', [EvaluationNotificationController::class, 'sendEvaluationClosed']); // Notifica cierre de evaluación - Correo 3
          Route::post('/send-hr-summary', [EvaluationNotificationController::class, 'sendHrSummary']);
          Route::get('/pending-status', [EvaluationNotificationController::class, 'getPendingStatus']);
          Route::post('/test-reminder', [EvaluationNotificationController::class, 'testReminder']);
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

        // EVALUATION PERSON
        Route::apiResource('evaluationPerson', EvaluationPersonController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        // PERSON RESULT
        Route::get('personResult/export', [EvaluationPersonResultController::class, 'export']);
        Route::get('personResult/getByPersonAndEvaluation', [EvaluationPersonResultController::class, 'getByPersonAndEvaluation']);
        Route::get('personResult/evaluations-to-evaluate/{id}', [EvaluationPersonResultController::class, 'getEvaluationsByPersonToEvaluate']);
        Route::get('leader-dashboard/{evaluation_id}', [EvaluationPersonResultController::class, 'getLeaderDashboard']);
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

        // DETAILED DEVELOPMENT PLAN
        Route::apiResource('detailedDevelopmentPlan', DetailedDevelopmentPlanController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        // PAR EVALUATOR
        Route::get('parEvaluator/worker/{workerId}', [EvaluationParEvaluatorController::class, 'getByWorker']);
        Route::apiResource('parEvaluator', EvaluationParEvaluatorController::class)->only([
          'index',
          'show',
          'store',
          'update',
          'destroy'
        ]);

        // EVALUATION MODEL
        Route::apiResource('evaluationModel', EvaluationModelController::class)->only([
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
    // Maestros Comercial
    Route::apiResource('commercialMasters', ApCommercialMastersController::class)->only([
      'index',
      'show',
      'store',
      'update',
      'destroy'
    ]);

    // Maestros Post Venta
    Route::apiResource('postVentaMasters', ApPostVentaMastersController::class)->only([
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

      Route::get('userSeriesAssignment/authorized-series', [UserSeriesAssignmentController::class, 'getAuthorizedSeries']);
      Route::apiResource('userSeriesAssignment', UserSeriesAssignmentController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::get('warehouse/by-model-sede', [WarehouseController::class, 'getWarehousesByModelAndSede']);
      Route::get('warehouse/warehouses-by-company', [WarehouseController::class, 'getWarehousesByCompany']);
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
      Route::get('businessPartners/{id}/opportunities', [BusinessPartnersController::class, 'opportunities']);
      Route::apiResource('businessPartners', BusinessPartnersController::class)->only([
        'index',
        'show',
        'store',
        'update',
      ]);
      Route::patch('businessPartners/{id}/remove-type', [BusinessPartnersController::class, 'removeType']);
      Route::get('businessPartners/{id}/validateOpportunity', [BusinessPartnersController::class, 'validateOpportunity']);

      Route::apiResource('businessPartnersEstablishments', BusinessPartnersEstablishmentController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Crear oportunidad desde un cliente
      Route::post('businessPartners/{clientId}/opportunities', [OpportunityController::class, 'storeFromClient']);

      Route::get('potentialBuyers/export', [PotentialBuyersController::class, 'export']);
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


      Route::get('purchaseRequestQuote/{id}/invoices', [PurchaseRequestQuoteController::class, 'getInvoices']);
      Route::get('purchaseRequestQuote/pdf/{purchaseRequestQuote}', [PurchaseRequestQuoteController::class, 'reportPDF']); // Descargar
      Route::post('purchaseRequestQuote/assignVehicle/{id}', [PurchaseRequestQuoteController::class, 'assignVehicle']); // Descargar
      Route::post('purchaseRequestQuote/unassignVehicle/{id}', [PurchaseRequestQuoteController::class, 'unassignVehicle']); // Descargar
      Route::apiResource('purchaseRequestQuote', PurchaseRequestQuoteController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::get('vehiclePurchaseOrder/export', [PurchaseOrderController::class, 'export']);
      Route::apiResource('vehiclePurchaseOrder', PurchaseOrderController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Resend purchase order with credit note (creates new OC with point)
      Route::post('vehiclePurchaseOrder/{id}/resend', [PurchaseOrderController::class, 'resend']);

      // Vehicle Purchase Order Migration Monitoring
      Route::group(['prefix' => 'vehiclePurchaseOrder/migration'], function () {
        Route::get('/summary', [VehiclePurchaseOrderMigrationController::class, 'summary']);
        Route::get('/statistics', [VehiclePurchaseOrderMigrationController::class, 'statistics']);
        Route::get('/orders', [VehiclePurchaseOrderMigrationController::class, 'index']);
        Route::get('/{id}/logs', [VehiclePurchaseOrderMigrationController::class, 'logs']);
        Route::get('/{id}/history', [VehiclePurchaseOrderMigrationController::class, 'history']);
      });

      // Vehicle Documents (Guías de Remisión/Traslado)
      Route::post('shippingGuides/{id}/cancel', [ShippingGuidesController::class, 'cancel']);
      Route::post('shippingGuides/{id}/send-to-nubefact', [ShippingGuidesController::class, 'sendToNubefact']);
      Route::post('shippingGuides/{id}/query-from-nubefact', [ShippingGuidesController::class, 'queryFromNubefact']);
      Route::post('shippingGuides/{id}/mark-as-received', [ShippingGuidesController::class, 'markAsReceived']);
      Route::get('shippingGuides/{id}/logs', [ShippingGuidesController::class, 'logs']);
      Route::get('shippingGuides/{id}/history', [ShippingGuidesController::class, 'history']);
      Route::get('shippingGuides/{id}/check-resources', [ShippingGuidesController::class, 'checkResources']);
      Route::apiResource('shippingGuides', ShippingGuidesController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Receiving Checklist
      Route::get('receivingChecklist/byShippingGuide/{shippingGuideId}', [ApReceivingChecklistController::class, 'getByShippingGuide']);
      Route::get('receivingChecklist', [ApReceivingChecklistController::class, 'index']);
      Route::put('receivingChecklist/{id}', [ApReceivingChecklistController::class, 'update']);
      Route::delete('receivingChecklist/byShippingGuide/{shippingGuideId}', [ApReceivingChecklistController::class, 'destroyByShippingGuide']);

      // Vehicles
      Route::get('vehicles/export/sales', [VehiclesController::class, 'exportSales']);
      Route::get('vehicles/costs', [VehiclesController::class, 'getCostsData']);
      Route::get('vehicles/{id}/invoices', [VehiclesController::class, 'getInvoices']);
      Route::get('vehicles/{id}/client-debt-info', [VehiclesController::class, 'getVehicleClientDebtInfo']);
      Route::apiResource('vehicles', VehiclesController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Daily Delivery Report
      Route::get('reports/daily-delivery', [ApDailyDeliveryReportController::class, 'index']);
      Route::get('reports/daily-delivery/export', [ApDailyDeliveryReportController::class, 'export']);
//      Route::get('vehicles/{id}/pending-anticipos', [VehiclesController::class, 'getPendingAnticipos']);
//      Route::post('vehicles/{id}/regularize-anticipos', [VehiclesController::class, 'regularizeAnticipos']);

      // Vehicles Delivery
      Route::post('vehiclesDelivery/{id}/generate-shipping-guide', [ApVehicleDeliveryController::class, 'generateShippingGuide']);
      Route::post('vehiclesDelivery/{id}/send-to-nubefact', [ApVehicleDeliveryController::class, 'sendToNubefact']);
      Route::post('vehiclesDelivery/{id}/query-from-nubefact', [ApVehicleDeliveryController::class, 'queryFromNubefact']);
      Route::post('vehiclesDelivery/{id}/send-to-dynamic', [ApVehicleDeliveryController::class, 'sendToDynamic']);
      Route::apiResource('vehiclesDelivery', ApVehicleDeliveryController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // DASHBOARD - Indicadores Comerciales
      Route::group(['prefix' => 'dashboard-visit-leads'], function () {
        Route::get('/by-date-range-total', [DashboardComercialController::class, 'getTotalsByDateRangeTotal']);
        Route::get('/by-date-range', [DashboardComercialController::class, 'getTotalsByDateRange']);
        Route::get('/by-sede', [DashboardComercialController::class, 'getTotalsBySede']);
        Route::get('/by-sede-and-brand', [DashboardComercialController::class, 'getTotalsBySedeAndBrand']);
        Route::get('/by-advisor', [DashboardComercialController::class, 'getTotalsByAdvisor']);
        Route::get('/by-user', [DashboardComercialController::class, 'getTotalsByUser']);
        Route::get('/by-campaign', [DashboardComercialController::class, 'getTotalsByCampaign']);
      });
    });

    //      POST-VENTA
    Route::group(['prefix' => 'postVenta'], function () {
      // Products - Gestión de Productos
      Route::get('products/low-stock', [ProductsController::class, 'lowStock']);
      Route::get('products/featured', [ProductsController::class, 'featured']);
      Route::post('products/{id}/update-stock', [ProductsController::class, 'updateStock']);
      Route::apiResource('products', ProductsController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Purchase Receptions - Recepciones de Compra
      Route::get('purchaseReceptions/by-order/{purchaseOrderId}', [PurchaseReceptionController::class, 'byPurchaseOrder']);
      Route::apiResource('purchaseReceptions', PurchaseReceptionController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Inventory Movements - Movimientos de Inventario
      Route::post('inventoryMovements/adjustments', [InventoryMovementController::class, 'createAdjustment']);
      Route::post('inventoryMovements/transfers', [InventoryMovementController::class, 'createTransfer']);
      Route::put('inventoryMovements/transfers/{id}', [InventoryMovementController::class, 'updateTransfer']);
      Route::delete('inventoryMovements/transfers/{id}', [InventoryMovementController::class, 'destroyTransfer']);
      Route::post('inventoryMovements/{id}/send-to-nubefact', [InventoryMovementController::class, 'sendShippingGuideToNubefact']);
      Route::get('inventoryMovements/kardex', [InventoryMovementController::class, 'getKardex']);
      Route::get('inventoryMovements/product/{productId}/warehouse/{warehouseId}/history', [InventoryMovementController::class, 'getProductMovementHistory']);
      Route::apiResource('inventoryMovements', InventoryMovementController::class)->only([
        'index',
        'show',
        'update',
        'destroy'
      ]);

      // Product Warehouse Stock - Stock de Productos por Almacén
      Route::get('productWarehouseStock/warehouse-stock-with-transit', [ProductWarehouseStockController::class, 'getWarehouseStockWithTransit']);

      // Transfer Receptions - Recepciones de Transferencias
      Route::apiResource('transferReceptions', TransferReceptionController::class)->only([
        'index',
        'show',
        'store',
        'destroy'
      ]);

      Route::apiResource('approvedAccessories', ApprovedAccessoriesController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      Route::get('appointmentPlanning/available-slots', [AppointmentPlanningController::class, 'availableSlots']);
      Route::get('appointmentPlanning/{id}/pdf', [AppointmentPlanningController::class, 'downloadPDF']);
      Route::apiResource('appointmentPlanning', AppointmentPlanningController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Work Orders - Órdenes de Trabajo
      Route::post('workOrders/{id}/calculate-totals', [WorkOrderController::class, 'calculateTotals']);
      Route::apiResource('workOrders', WorkOrderController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Work Order Items - Ítems de Órdenes de Trabajo
      Route::apiResource('workOrderItems', WorkOrderItemController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Work Order Assign Operators - Asignación de Operadores a Órdenes de Trabajo
      Route::apiResource('workOrderAssignOperators', ApWorkOrderAssignOperatorController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Work Order Parts - Repuestos de Órdenes de Trabajo
      Route::post('workOrderParts/{id}/confirm-delivery', [ApWorkOrderPartsController::class, 'confirmDelivery']);
      Route::apiResource('workOrderParts', ApWorkOrderPartsController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Work Order Quotations - Cotizaciones de Órdenes de Trabajo
      Route::get('orderQuotations/{id}/pdf', [ApOrderQuotationsController::class, 'downloadPDF']);
      Route::apiResource('orderQuotations', ApOrderQuotationsController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Order Quotation Details - Detalles de Cotización (Productos y Mano de Obra)
      Route::apiResource('orderQuotationDetails', ApOrderQuotationDetailsController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Order Purchase Requests - Solicitudes de Compra de Órdenes
      Route::apiResource('orderPurchaseRequests', ApOrderPurchaseRequestsController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy'
      ]);

      // Vehicle Inspections - Inspecciones Vehiculares
      Route::get('vehicleInspections/by-work-order/{workOrderId}', [ApVehicleInspectionController::class, 'getByWorkOrder']);
      Route::apiResource('vehicleInspections', ApVehicleInspectionController::class)->only([
        'index',
        'show',
        'store',
        'destroy'
      ]);
    });

    //      FACTURACIÓN ELECTRÓNICA
    Route::group(['prefix' => 'facturacion'], function () {

      // Operaciones especiales de documentos
      Route::get('electronic-documents/nextDocumentNumber', [ElectronicDocumentController::class, 'nextDocumentNumber']);
      Route::get('electronic-documents/{id}/nextCreditNoteNumber', [ElectronicDocumentController::class, 'nextCreditNoteNumber']);
      Route::get('electronic-documents/{id}/nextDebitNoteNumber', [ElectronicDocumentController::class, 'nextDebitNoteNumber']);
      Route::post('electronic-documents/{id}/send', [ElectronicDocumentController::class, 'sendToNubefact']);
      Route::post('electronic-documents/{id}/query', [ElectronicDocumentController::class, 'queryFromNubefact']);
      Route::get('electronic-documents/{id}/pre-cancel', [ElectronicDocumentController::class, 'preCancelInNubefact']);
      Route::post('electronic-documents/{id}/cancel', [ElectronicDocumentController::class, 'cancelInNubefact']);
      Route::post('electronic-documents/{id}/credit-note', [ElectronicDocumentController::class, 'createCreditNote']);
      Route::put('electronic-documents/{id}/credit-note', [ElectronicDocumentController::class, 'updateCreditNote']);
      Route::post('electronic-documents/{id}/debit-note', [ElectronicDocumentController::class, 'createDebitNote']);
      Route::put('electronic-documents/{id}/debit-note', [ElectronicDocumentController::class, 'updateDebitNote']);
      Route::get('electronic-documents/by-entity/{module}/{entityType}/{entityId}', [ElectronicDocumentController::class, 'getByOriginEntity']);
      Route::get('electronic-documents/{id}/pdf', [ElectronicDocumentController::class, 'generatePDF']);

      // Sincronización con Dynamics 365
      Route::post('electronic-documents/{id}/sync-dynamics', [ElectronicDocumentController::class, 'syncToDynamics']);

      // Preview de asientos contables
      Route::get('accounting-entries/preview/{shippingGuideId}', [AccountingEntryController::class, 'preview']);
      Route::get('accounting-entries/mappings', [AccountingEntryController::class, 'accountMappings']);
      Route::get('electronic-documents/{id}/sync-status', [ElectronicDocumentController::class, 'getSyncStatus']);
      Route::get('electronic-documents/checkResources/{id}', [ElectronicDocumentController::class, 'checkResources']);

      // Migration logs and history
      Route::get('electronic-documents/{id}/logs', [ElectronicDocumentController::class, 'logs']);
      Route::get('electronic-documents/{id}/history', [ElectronicDocumentController::class, 'history']);

      // CRUD de Documentos Electrónicos
      Route::apiResource('electronic-documents', ElectronicDocumentController::class);

      // Catálogos de facturación (con caché)
      Route::group(['prefix' => 'catalogs'], function () {
        Route::get('/all', [BillingCatalogController::class, 'getAllCatalogs']);
        Route::get('/document-types', [BillingCatalogController::class, 'getDocumentTypes']);
        Route::get('/transaction-types', [BillingCatalogController::class, 'getTransactionTypes']);
        Route::get('/identity-document-types', [BillingCatalogController::class, 'getIdentityDocumentTypes']);
        Route::get('/igv-types', [BillingCatalogController::class, 'getIgvTypes']);
        Route::get('/credit-note-types', [BillingCatalogController::class, 'getCreditNoteTypes']);
        Route::get('/debit-note-types', [BillingCatalogController::class, 'getDebitNoteTypes']);
        Route::get('/currencies', [BillingCatalogController::class, 'getCurrencies']);
        Route::get('/detraction-types', [BillingCatalogController::class, 'getDetractionTypes']);
        Route::delete('/cache', [BillingCatalogController::class, 'clearCache']);
      });
    });
  });

  // Document Validation Routes
  Route::group(['prefix' => 'document-validation'], function () {
    Route::post('/validate/general', [DocumentValidationController::class, 'validateGeneral']);
    Route::post('/validate/dni', [DocumentValidationController::class, 'validateDni']);
    Route::post('/validate/ruc', [DocumentValidationController::class, 'validateRuc']);
    Route::post('/validate/license', [DocumentValidationController::class, 'validateLicense']);
    Route::post('/validate/plate', [DocumentValidationController::class, 'validatePlate']);
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

  // GP - Gestión Humana - Viáticos Routes
  Route::group(['prefix' => 'gp/gestion-humana/viaticos'], function () {
    // Per Diem Requests
    Route::get('per-diem-requests/overdue', [PerDiemRequestController::class, 'overdue']);
    Route::get('per-diem-requests/rates', [PerDiemRequestController::class, 'rates']);
    Route::post('per-diem-requests/{id}/submit', [PerDiemRequestController::class, 'submit']);
    Route::post('per-diem-requests/{id}/mark-paid', [PerDiemRequestController::class, 'markAsPaid']);
    Route::post('per-diem-requests/{id}/start-settlement', [PerDiemRequestController::class, 'startSettlement']);
    Route::post('per-diem-requests/{id}/complete-settlement', [PerDiemRequestController::class, 'completeSettlement']);
    Route::apiResource('per-diem-requests', PerDiemRequestController::class);

    // Approvals
    Route::get('per-diem-approvals/pending', [PerDiemApprovalController::class, 'pending']);
    Route::post('per-diem-requests/{id}/approve', [PerDiemApprovalController::class, 'approve']);
    Route::post('per-diem-requests/{id}/reject', [PerDiemApprovalController::class, 'reject']);

    // Expenses
    Route::get('per-diem-requests/{requestId}/expenses', [PerDiemExpenseController::class, 'index']);
    Route::post('per-diem-requests/{requestId}/expenses', [PerDiemExpenseController::class, 'store']);
    Route::put('per-diem-expenses/{expenseId}', [PerDiemExpenseController::class, 'update']);
    Route::delete('per-diem-expenses/{expenseId}', [PerDiemExpenseController::class, 'destroy']);
    Route::post('per-diem-expenses/{expenseId}/validate', [PerDiemExpenseController::class, 'validate']);

    // Hotel Reservations
    Route::post('per-diem-requests/{requestId}/hotel-reservation', [HotelReservationController::class, 'store']);
    Route::put('hotel-reservations/{reservationId}', [HotelReservationController::class, 'update']);
    Route::delete('hotel-reservations/{reservationId}', [HotelReservationController::class, 'destroy']);
    Route::post('hotel-reservations/{reservationId}/mark-attended', [HotelReservationController::class, 'markAttended']);

    // Policies
    Route::get('per-diem-policies/current', [PerDiemPolicyController::class, 'current']);
    Route::post('per-diem-policies/{id}/activate', [PerDiemPolicyController::class, 'activate']);
    Route::post('per-diem-policies/{id}/close', [PerDiemPolicyController::class, 'close']);
    Route::apiResource('per-diem-policies', PerDiemPolicyController::class);

    // Categories
    Route::get('per-diem-categories/active', [PerDiemCategoryController::class, 'active']);
    Route::get('per-diem-categories', [PerDiemCategoryController::class, 'index']);

    // Expense Types
    Route::get('expense-types/active', [ExpenseTypeController::class, 'active']);
    Route::get('expense-types/parents', [ExpenseTypeController::class, 'parents']);
    Route::get('expense-types', [ExpenseTypeController::class, 'index']);

    // Hotel Agreements
    Route::get('hotel-agreements/active', [HotelAgreementController::class, 'active']);
    Route::get('hotel-agreements', [HotelAgreementController::class, 'index']);
  });
});

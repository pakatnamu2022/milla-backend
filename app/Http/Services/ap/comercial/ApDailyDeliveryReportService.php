<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroup;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApDailyDeliveryReportService
{
  /**
   * Genera el reporte diario de entregas y facturación
   *
   * @param string $date Fecha en formato Y-m-d
   * @return array
   */
  public function generate(string $date): array
  {
    $carbon = Carbon::parse($date);
    $year = $carbon->year;
    $month = $carbon->month;

    // Paso 1: Obtener vehículos con entrega en el mes
    $vehiclesWithDelivery = $this->getDeliveredVehicles($year, $month);

    // Paso 2: Obtener IDs de cotizaciones facturadas
    $invoicedQuoteIds = $this->getInvoicedQuoteIds($vehiclesWithDelivery->pluck('quote_id'));

    // Paso 3: Construir resumen por clase de artículo
    $summary = $this->buildSummaryByArticleClass($vehiclesWithDelivery, $invoicedQuoteIds);

    // Paso 4: Construir desglose por asesores
    $advisors = $this->buildAdvisorBreakdown($vehiclesWithDelivery, $invoicedQuoteIds);

    // Paso 5: Construir árbol jerárquico
    $hierarchy = $this->buildHierarchyTree($year, $month, $vehiclesWithDelivery, $invoicedQuoteIds);

    return [
      'date' => $date,
      'period' => [
        'year' => $year,
        'month' => $month,
      ],
      'summary' => $summary,
      'advisors' => $advisors,
      'hierarchy' => $hierarchy,
    ];
  }

  /**
   * Obtiene vehículos con entrega realizada en el mes
   *
   * @param int $year
   * @param int $month
   * @return Collection
   */
  protected function getDeliveredVehicles(int $year, int $month): Collection
  {
    $vehicles = DB::table('ap_vehicles')
      ->join('ap_vehicle_delivery', 'ap_vehicles.id', '=', 'ap_vehicle_delivery.vehicle_id')
      ->join('purchase_request_quote', 'ap_vehicles.id', '=', 'purchase_request_quote.ap_vehicle_id')
      ->join('ap_opportunity', 'purchase_request_quote.opportunity_id', '=', 'ap_opportunity.id')
      ->join('ap_models_vn', 'ap_vehicles.ap_models_vn_id', '=', 'ap_models_vn.id')
      ->join('ap_class_article', 'ap_models_vn.class_id', '=', 'ap_class_article.id')
      ->leftJoin('ap_familia_marca', 'ap_models_vn.family_id', '=', 'ap_familia_marca.id')
      ->leftJoin('ap_vehicle_brand', 'ap_familia_marca.marca_id', '=', 'ap_vehicle_brand.id')
      ->whereYear('ap_vehicle_delivery.real_delivery_date', $year)
      ->whereMonth('ap_vehicle_delivery.real_delivery_date', $month)
      ->whereNotNull('ap_vehicle_delivery.real_delivery_date')
      ->whereNull('ap_vehicles.deleted_at')
      ->whereNull('ap_vehicle_delivery.deleted_at')
      ->whereNull('purchase_request_quote.deleted_at')
      ->select([
        'ap_vehicles.id as vehicle_id',
        'ap_vehicle_delivery.real_delivery_date',
        'ap_opportunity.worker_id as advisor_id',
        'ap_class_article.id as article_class_id',
        'ap_class_article.description as article_class_description',
        'ap_class_article.type_class_id',
        'purchase_request_quote.id as quote_id',
        'ap_vehicle_brand.id as brand_id',
        'ap_vehicle_brand.group_id as brand_group_id',
      ])
      ->get();
//    dd($year, $month, $vehicles);
    return $vehicles;
  }

  /**
   * Obtiene IDs de cotizaciones que tienen facturas válidas
   *
   * @param Collection $quoteIds
   * @return Collection
   */
  protected function getInvoicedQuoteIds(Collection $quoteIds): Collection
  {
    if ($quoteIds->isEmpty()) {
      return collect([]);
    }

    return ElectronicDocument::whereIn('purchase_request_quote_id', $quoteIds)
      ->where('is_advance_payment', false)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereIn('sunat_concept_document_type_id', [
        SunatConcepts::ID_FACTURA_ELECTRONICA,
        SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA,
      ])
      ->whereNull('deleted_at')
      ->distinct()
      ->pluck('purchase_request_quote_id');
  }

  /**
   * Construye el resumen por clase de artículo (dinámico)
   *
   * Genera categorías basadas en las clases reales encontradas en los datos
   *
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function buildSummaryByArticleClass(Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    $classSummary = [];
    $totalEntregas = 0;
    $totalFacturacion = 0;

    // Agrupar por clase de artículo
    $groupedByClass = $vehicles->groupBy('article_class_description');

    foreach ($groupedByClass as $className => $classVehicles) {
      $entregas = $classVehicles->count();
      $facturacion = $classVehicles->filter(function ($vehicle) use ($invoicedQuoteIds) {
        return $invoicedQuoteIds->contains($vehicle->quote_id);
      })->count();

      $classSummary[$className] = [
        'entregas' => $entregas,
        'facturadas' => $facturacion,
        'reporteria_dealer_portal' => null,
      ];

      $totalEntregas += $entregas;
      $totalFacturacion += $facturacion;
    }

    // Agregar total general al final
    $classSummary['TOTAL'] = [
      'entregas' => $totalEntregas,
      'facturadas' => $totalFacturacion,
      'reporteria_dealer_portal' => null,
    ];

    return $classSummary;
  }

  /**
   * Construye el desglose por asesores
   *
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function buildAdvisorBreakdown(Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    $advisorStats = [];

    // Agrupar por asesor
    $groupedByAdvisor = $vehicles->groupBy('advisor_id');

    foreach ($groupedByAdvisor as $advisorId => $advisorVehicles) {
      if (!$advisorId) {
        continue; // Skip null advisors
      }

      $entregas = $advisorVehicles->count();
      $facturacion = $advisorVehicles->filter(function ($vehicle) use ($invoicedQuoteIds) {
        return $invoicedQuoteIds->contains($vehicle->quote_id);
      })->count();

      $advisor = Person::find($advisorId);

      $advisorStats[] = [
        'id' => $advisorId,
        'name' => $advisor ? $advisor->nombre_completo : 'Desconocido',
        'entregas' => $entregas,
        'facturadas' => $facturacion,
        'reporteria_dealer_portal' => null,
      ];
    }

    // Ordenar por nombre
    usort($advisorStats, function ($a, $b) {
      return strcmp($a['name'], $b['name']);
    });

    return $advisorStats;
  }

  /**
   * Construye el árbol jerárquico con 3 nodos principales fijos:
   * 1. Gerente TRADICIONAL (VEHICULOS)
   * 2. Gerente CHINA (VEHICULOS)
   * 3. Jefe CAMIONES
   *
   * @param int $year
   * @param int $month
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function buildHierarchyTree(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    // Obtener IDs de tipos de clase
    $vehicleTypeId = ApCommercialMasters::ofType('CLASS_TYPE')
      ->where('code', ApCommercialMasters::CLASS_TYPE_VEHICLE_CODE)
      ->value('id');

    $camionTypeId = ApCommercialMasters::ofType('CLASS_TYPE')
      ->where('code', ApCommercialMasters::CLASS_TYPE_CAMION_CODE)
      ->value('id');

    $vehiclesCamiones = $vehicles->filter(function ($v) use ($camionTypeId) {
      return $v->type_class_id == $camionTypeId;
    });

    // Obtener asignaciones y gerentes
    $assignments = ApAssignmentLeadership::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->with(['boss:id,nombre_completo', 'worker:id,nombre_completo'])
      ->get();

    $commercialManagers = ApCommercialManagerBrandGroup::where('year', $year)
      ->where('month', $month)
      ->with(['commercialManager:id,nombre_completo', 'brandGroup:id,code,description'])
      ->get();

    $brandAssignments = ApAssignBrandConsultant::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->with(['brand:id,name,group_id', 'worker:id,nombre_completo'])
      ->get();

    // Calcular conteos por asesor
    $advisorCounts = $this->calculateAdvisorCounts($vehicles, $invoicedQuoteIds);

    // Construir mapa de asesor -> grupos de marcas
    $advisorBrandGroups = $this->buildAdvisorBrandGroupMap($brandAssignments);

    // Construir mapa de asesor -> nombres de marcas
    $advisorBrands = $this->buildAdvisorBrandsMap($brandAssignments);

    // Construir mapa de jefe -> asesores
    $bossToWorkers = $assignments->groupBy('boss_id');

    // Árbol dinámico: agrupar por gerente (no por grupo de marcas)
    $tree = [];

    // Identificar al jefe de CAMIONES primero para excluirlo de los gerentes
    $camionesJefeId = null;
    $allBossIds = $assignments->pluck('boss_id')->unique();
    $allWorkerIds = $assignments->pluck('worker_id')->unique();
    $topBossIds = $allBossIds->diff($allWorkerIds);
    if ($topBossIds->isEmpty()) {
      $topBossIds = $allBossIds->take(1);
    } else {
      $topBossIds = $topBossIds->take(1);
    }
    $camionesJefeId = $topBossIds->first();

    // Agrupar gerentes por commercial_manager_id (para unificar si maneja múltiples grupos)
    $managersByPerson = $commercialManagers->groupBy('commercial_manager_id');

    // Construir un nodo por cada gerente único
    foreach ($managersByPerson as $managerId => $managerAssignments) {
      // Obtener todos los grupos que maneja este gerente
      $brandGroupIds = $managerAssignments->pluck('brand_group_id')->toArray();
      $brandGroupNames = $managerAssignments->pluck('brandGroup.description')->filter()->unique()->implode(', ');

      $node = $this->buildGerenteNodeMultiGroup($managerId, $brandGroupIds, $brandGroupNames, $bossToWorkers, $advisorBrandGroups, $advisorBrands, $advisorCounts, 'VEHICULOS NUEVO', $vehicleTypeId, $vehicles, $invoicedQuoteIds, $camionesJefeId);
      if ($node) {
        $tree[] = $node;
      }
    }

    // ÚLTIMO NODO: Jefe CAMIONES (directo, sin gerente) - siempre mostrar
    $node = $this->buildCamionesNode($year, $month, $vehiclesCamiones, $invoicedQuoteIds, $advisorBrands);
    if ($node) {
      $tree[] = $node;
    }

    return $tree;
  }

  /**
   * Construye árbol jerárquico para clases con marcas (VEHICLE o CAMION)
   * Estructura: Gerente Comercial > Jefe > Asesor
   *
   * @param int $year
   * @param int $month
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @param string $className
   * @return array
   */
  protected function buildHierarchyForClassWithBrands(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, string $className): array
  {
    // Paso 1: Obtener asignaciones de liderazgo (jefe-asesor)
    $assignments = ApAssignmentLeadership::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->with(['boss:id,nombre_completo', 'worker:id,nombre_completo'])
      ->get();

    // Paso 2: Obtener asignaciones de gerentes comerciales a grupos de marcas
    $commercialManagers = ApCommercialManagerBrandGroup::where('year', $year)
      ->where('month', $month)
      ->with(['commercialManager:id,nombre_completo', 'brandGroup:id,code,description'])
      ->get();

    // Paso 3: Obtener asignaciones de asesores a marcas
    $brandAssignments = ApAssignBrandConsultant::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->with(['brand:id,name,group_id', 'worker:id,nombre_completo'])
      ->get();

    if ($assignments->isEmpty() && $commercialManagers->isEmpty()) {
      return [];
    }

    // Paso 4: Calcular conteos por asesor
    $advisorCounts = $this->calculateAdvisorCounts($vehicles, $invoicedQuoteIds);

    // Paso 5: Construir mapa de asesor -> marcas -> grupo
    $advisorBrandGroups = $this->buildAdvisorBrandGroupMap($brandAssignments);

    // Paso 6: Construir mapa de jefe -> asesores
    $bossToWorkers = $assignments->groupBy('boss_id');

    // Paso 7: Identificar jefes (los que son boss_id)
    $allBossIds = $assignments->pluck('boss_id')->unique();

    // Paso 8: Construir árbol por gerente comercial
    $tree = [];

    foreach ($commercialManagers as $managerAssignment) {
      $managerId = $managerAssignment->commercial_manager_id;
      $brandGroupId = $managerAssignment->brand_group_id;

      $manager = Person::find($managerId);
      if (!$manager) {
        continue;
      }

      $managerNode = [
        'id' => $managerId,
        'name' => $manager->nombre_completo,
        'level' => 'gerente',
        'brand_group' => $managerAssignment->brandGroup?->description ?? 'Sin grupo',
        'article_class' => $className,
        'entregas' => 0,
        'facturadas' => 0,
        'reporteria_dealer_portal' => null,
        'children' => [],
      ];

      // Encontrar jefes que manejan este grupo de marcas
      foreach ($allBossIds as $jefeId) {
        $jefe = Person::find($jefeId);
        if (!$jefe) {
          continue;
        }

        // Verificar si este jefe tiene asesores con marcas de este grupo
        $jefeHasGroupBrands = $this->jefeHasBrandGroup($jefeId, $brandGroupId, $bossToWorkers, $advisorBrandGroups);

        if ($jefeHasGroupBrands) {
          $jefeNode = $this->buildJefeNode($jefeId, $brandGroupId, $bossToWorkers, $advisorBrandGroups, $advisorCounts);
          if ($jefeNode) {
            $managerNode['children'][] = $jefeNode;
            $managerNode['entregas'] += $jefeNode['entregas'];
            $managerNode['facturadas'] += $jefeNode['facturadas'];
          }
        }
      }

      // Solo agregar gerente si tiene jefes/asesores
      if (!empty($managerNode['children'])) {
        $tree[] = $managerNode;
      }
    }

    return $tree;
  }

  /**
   * Construye árbol jerárquico para otras clases (no vehículos nuevos)
   * Estructura: Jefe Principal > Asesores (sin grupos de marcas)
   *
   * @param int $year
   * @param int $month
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @param string $className
   * @return array
   */
  protected function buildHierarchyForOtherClasses(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, string $className): array
  {
    // Paso 1: Obtener asignaciones de liderazgo
    $assignments = ApAssignmentLeadership::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->with(['boss:id,nombre_completo', 'worker:id,nombre_completo'])
      ->get();

    if ($assignments->isEmpty()) {
      return [];
    }

    // Paso 2: Calcular conteos por asesor
    $advisorCounts = $this->calculateAdvisorCounts($vehicles, $invoicedQuoteIds);

    // Paso 3: Identificar jefes principales (los que no son workers de nadie más)
    $allBossIds = $assignments->pluck('boss_id')->unique();
    $allWorkerIds = $assignments->pluck('worker_id')->unique();
    $topBossIds = $allBossIds->diff($allWorkerIds);

    // Si no hay jefes principales (todos son workers), usar todos los boss_ids
    if ($topBossIds->isEmpty()) {
      $topBossIds = $allBossIds;
    }

    // Paso 4: Construir mapa de jefe -> asesores
    $bossToWorkers = $assignments->groupBy('boss_id');

    // Paso 5: Construir árbol por jefe principal
    $tree = [];

    foreach ($topBossIds as $bossId) {
      $boss = Person::find($bossId);
      if (!$boss) {
        continue;
      }

      $bossNode = [
        'id' => $bossId,
        'name' => $boss->nombre_completo,
        'level' => 'jefe',
        'article_class' => $className,
        'entregas' => 0,
        'facturadas' => 0,
        'reporteria_dealer_portal' => null,
        'children' => [],
      ];

      // Agregar todos los asesores bajo este jefe
      $workers = $bossToWorkers->get($bossId);
      if ($workers) {
        foreach ($workers as $assignment) {
          $workerId = $assignment->worker_id;

          // Verificar si el worker tiene entregas en esta clase
          if (!isset($advisorCounts[$workerId])) {
            continue;
          }

          $worker = Person::find($workerId);
          if (!$worker) {
            continue;
          }

          $workerEntregas = $advisorCounts[$workerId]['entregas'] ?? 0;
          $workerFacturadas = $advisorCounts[$workerId]['facturadas'] ?? 0;

          $bossNode['children'][] = [
            'id' => $workerId,
            'name' => $worker->nombre_completo,
            'level' => 'asesor',
            'entregas' => $workerEntregas,
            'facturadas' => $workerFacturadas,
            'reporteria_dealer_portal' => null,
          ];

          $bossNode['entregas'] += $workerEntregas;
          $bossNode['facturadas'] += $workerFacturadas;
        }
      }

      // Solo agregar jefe si tiene asesores con entregas
      if (!empty($bossNode['children'])) {
        $tree[] = $bossNode;
      }
    }

    return $tree;
  }

  /**
   * Construye mapa de asesor -> grupos de marcas
   */
  protected function buildAdvisorBrandGroupMap(Collection $brandAssignments): array
  {
    $map = [];

    foreach ($brandAssignments as $assignment) {
      $workerId = $assignment->worker_id;
      $groupId = $assignment->brand?->group_id;

      if ($groupId) {
        if (!isset($map[$workerId])) {
          $map[$workerId] = [];
        }
        if (!in_array($groupId, $map[$workerId])) {
          $map[$workerId][] = $groupId;
        }
      }
    }

    return $map;
  }

  /**
   * Construye mapa de asesor -> marcas asignadas
   */
  protected function buildAdvisorBrandsMap(Collection $brandAssignments): array
  {
    $map = [];

    foreach ($brandAssignments as $assignment) {
      $workerId = $assignment->worker_id;
      $brandName = $assignment->brand?->name;

      if ($brandName) {
        if (!isset($map[$workerId])) {
          $map[$workerId] = [];
        }
        if (!in_array($brandName, $map[$workerId])) {
          $map[$workerId][] = $brandName;
        }
      }
    }

    return $map;
  }

  /**
   * Verifica si un jefe tiene asesores con marcas de un grupo específico
   */
  protected function jefeHasBrandGroup(int $jefeId, int $brandGroupId, Collection $bossToWorkers, array $advisorBrandGroups): bool
  {
    $workers = $bossToWorkers->get($jefeId);
    if (!$workers) {
      return false;
    }

    foreach ($workers as $assignment) {
      $workerId = $assignment->worker_id;
      $workerGroups = $advisorBrandGroups[$workerId] ?? [];

      if (in_array($brandGroupId, $workerGroups)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Construye nodo de jefe con sus asesores filtrados por grupo de marca
   */
  protected function buildJefeNode(int $jefeId, int $brandGroupId, Collection $bossToWorkers, array $advisorBrandGroups, array $advisorCounts): ?array
  {
    $jefe = Person::find($jefeId);
    if (!$jefe) {
      return null;
    }

    $jefeNode = [
      'id' => $jefeId,
      'name' => $jefe->nombre_completo,
      'level' => 'jefe',
      'entregas' => 0,
      'facturadas' => 0,
      'reporteria_dealer_portal' => null,
      'children' => [],
    ];

    $workers = $bossToWorkers->get($jefeId);
    if (!$workers) {
      return null;
    }

    foreach ($workers as $assignment) {
      $workerId = $assignment->worker_id;
      $workerGroups = $advisorBrandGroups[$workerId] ?? [];

      // Solo incluir asesores que tienen marcas de este grupo
      if (in_array($brandGroupId, $workerGroups)) {
        $asesor = Person::find($workerId);
        if (!$asesor) {
          continue;
        }

        $asesorEntregas = $advisorCounts[$workerId]['entregas'] ?? 0;
        $asesorFacturacion = $advisorCounts[$workerId]['facturadas'] ?? 0;

        $jefeNode['children'][] = [
          'id' => $workerId,
          'name' => $asesor->nombre_completo,
          'level' => 'asesor',
          'entregas' => $asesorEntregas,
          'facturadas' => $asesorFacturacion,
          'reporteria_dealer_portal' => null,
        ];

        $jefeNode['entregas'] += $asesorEntregas;
        $jefeNode['facturadas'] += $asesorFacturacion;
      }
    }

    return empty($jefeNode['children']) ? null : $jefeNode;
  }

  /**
   * Calcula conteos de entregas y facturación por asesor
   *
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function calculateAdvisorCounts(Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    $counts = [];

    foreach ($vehicles->groupBy('advisor_id') as $advisorId => $advisorVehicles) {
      if (!$advisorId) {
        continue;
      }

      $counts[$advisorId] = [
        'entregas' => $advisorVehicles->count(),
        'facturadas' => $advisorVehicles->filter(function ($vehicle) use ($invoicedQuoteIds) {
          return $invoicedQuoteIds->contains($vehicle->quote_id);
        })->count(),
      ];
    }

    return $counts;
  }

  /**
   * Construye nodo de gerente que maneja múltiples grupos de marcas
   */
  protected function buildGerenteNodeMultiGroup(int $managerId, array $brandGroupIds, string $brandGroupNames, Collection $bossToWorkers, array $advisorBrandGroups, array $advisorBrands, array $advisorCounts, string $className, int $vehicleTypeId, Collection $allVehicles, Collection $invoicedQuoteIds, ?int $camionesJefeId = null): ?array
  {
    $manager = Person::find($managerId);

    if (!$manager) {
      return null;
    }

    // Filtrar vehículos de todos los grupos de este gerente
    $groupVehicles = $allVehicles->filter(function ($v) use ($vehicleTypeId, $brandGroupIds) {
      return $v->type_class_id == $vehicleTypeId && in_array($v->brand_group_id, $brandGroupIds);
    });

    // Recalcular conteos solo para estos grupos
    $groupAdvisorCounts = $this->calculateAdvisorCounts($groupVehicles, $invoicedQuoteIds);

    $managerNode = [
      'id' => $managerId,
      'name' => $manager->nombre_completo,
      'level' => 'gerente',
      'brand_group' => $brandGroupNames ?: 'Sin grupo',
      'article_class' => $className,
      'entregas' => 0,
      'facturadas' => 0,
      'reporteria_dealer_portal' => null,
      'children' => [],
    ];

    // Identificar todos los jefes
    $allBossIds = $bossToWorkers->keys();

    // Encontrar jefes que manejan cualquiera de estos grupos de marcas
    foreach ($allBossIds as $jefeId) {
      // Excluir al jefe de CAMIONES
      if ($camionesJefeId && $jefeId == $camionesJefeId) {
        continue;
      }

      // Construir nodo de jefe considerando TODOS los grupos del gerente
      $jefeNode = $this->buildJefeNodeForMultipleGroups($jefeId, $brandGroupIds, $bossToWorkers, $advisorBrandGroups, $advisorBrands, $groupAdvisorCounts);

      if ($jefeNode && !empty($jefeNode['children'])) {
        $managerNode['children'][] = $jefeNode;
        $managerNode['entregas'] += $jefeNode['entregas'];
        $managerNode['facturadas'] += $jefeNode['facturadas'];
      }
    }

    return empty($managerNode['children']) ? null : $managerNode;
  }

  /**
   * Construye nodo de gerente con sus jefes y asesores (versión antigua para un solo grupo)
   */
  protected function buildGerenteNode($managerAssignment, int $brandGroupId, Collection $bossToWorkers, array $advisorBrandGroups, array $advisorCounts, string $className, int $vehicleTypeId, Collection $allVehicles, Collection $invoicedQuoteIds, ?int $camionesJefeId = null): ?array
  {
    $managerId = $managerAssignment->commercial_manager_id;
    $manager = Person::find($managerId);

    if (!$manager) {
      return null;
    }

    // Filtrar vehículos de este grupo
    $groupVehicles = $allVehicles->filter(function ($v) use ($vehicleTypeId, $brandGroupId) {
      return $v->type_class_id == $vehicleTypeId && $v->brand_group_id == $brandGroupId;
    });

    // Recalcular conteos solo para este grupo
    $groupAdvisorCounts = $this->calculateAdvisorCounts($groupVehicles, $invoicedQuoteIds);

    $managerNode = [
      'id' => $managerId,
      'name' => $manager->nombre_completo,
      'level' => 'gerente',
      'brand_group' => $managerAssignment->brandGroup?->description ?? 'Sin grupo',
      'article_class' => $className,
      'entregas' => 0,
      'facturadas' => 0,
      'reporteria_dealer_portal' => null,
      'children' => [],
    ];

    // Identificar todos los jefes
    $allBossIds = $bossToWorkers->keys();

    // Encontrar jefes que manejan este grupo de marcas
    foreach ($allBossIds as $jefeId) {
      // Excluir al jefe de CAMIONES
      if ($camionesJefeId && $jefeId == $camionesJefeId) {
        continue;
      }

      $jefeNode = $this->buildJefeNodeForGroup($jefeId, $brandGroupId, $bossToWorkers, $advisorBrandGroups, $groupAdvisorCounts);

      if ($jefeNode && !empty($jefeNode['children'])) {
        $managerNode['children'][] = $jefeNode;
        $managerNode['entregas'] += $jefeNode['entregas'];
        $managerNode['facturadas'] += $jefeNode['facturadas'];
      }
    }

    return empty($managerNode['children']) ? null : $managerNode;
  }

  /**
   * Construye nodo de jefe para múltiples grupos de marcas
   */
  protected function buildJefeNodeForMultipleGroups(int $jefeId, array $brandGroupIds, Collection $bossToWorkers, array $advisorBrandGroups, array $advisorBrands, array $advisorCounts): ?array
  {
    $jefe = Person::find($jefeId);
    if (!$jefe) {
      return null;
    }

    $jefeNode = [
      'id' => $jefeId,
      'name' => $jefe->nombre_completo,
      'level' => 'jefe',
      'entregas' => 0,
      'facturadas' => 0,
      'reporteria_dealer_portal' => null,
      'children' => [],
    ];

    $workers = $bossToWorkers->get($jefeId);
    if (!$workers) {
      return null;
    }

    foreach ($workers as $assignment) {
      $workerId = $assignment->worker_id;
      $workerGroups = $advisorBrandGroups[$workerId] ?? [];
      $workerBrands = $advisorBrands[$workerId] ?? [];

      // Incluir asesores que tienen marcas de CUALQUIERA de estos grupos O que no tienen marcas asignadas
      $hasAnyGroup = !empty(array_intersect($brandGroupIds, $workerGroups));
      if ($hasAnyGroup || empty($workerGroups)) {
        $asesor = Person::find($workerId);
        if (!$asesor) {
          continue;
        }

        $asesorEntregas = $advisorCounts[$workerId]['entregas'] ?? 0;
        $asesorFacturacion = $advisorCounts[$workerId]['facturadas'] ?? 0;

        $jefeNode['children'][] = [
          'id' => $workerId,
          'name' => $asesor->nombre_completo,
          'level' => 'asesor',
          'brands' => !empty($workerBrands) ? $workerBrands : null,
          'entregas' => $asesorEntregas,
          'facturadas' => $asesorFacturacion,
          'reporteria_dealer_portal' => null,
        ];

        $jefeNode['entregas'] += $asesorEntregas;
        $jefeNode['facturadas'] += $asesorFacturacion;
      }
    }

    return $jefeNode;
  }

  /**
   * Construye nodo de jefe para un grupo específico
   */
  protected function buildJefeNodeForGroup(int $jefeId, int $brandGroupId, Collection $bossToWorkers, array $advisorBrandGroups, array $advisorCounts): ?array
  {
    $jefe = Person::find($jefeId);
    if (!$jefe) {
      return null;
    }

    $jefeNode = [
      'id' => $jefeId,
      'name' => $jefe->nombre_completo,
      'level' => 'jefe',
      'entregas' => 0,
      'facturadas' => 0,
      'reporteria_dealer_portal' => null,
      'children' => [],
    ];

    $workers = $bossToWorkers->get($jefeId);
    if (!$workers) {
      return null;
    }

    foreach ($workers as $assignment) {
      $workerId = $assignment->worker_id;
      $workerGroups = $advisorBrandGroups[$workerId] ?? [];

      // Incluir asesores que tienen marcas de este grupo O que no tienen marcas asignadas
      if (in_array($brandGroupId, $workerGroups) || empty($workerGroups)) {
        $asesor = Person::find($workerId);
        if (!$asesor) {
          continue;
        }

        $asesorEntregas = $advisorCounts[$workerId]['entregas'] ?? 0;
        $asesorFacturacion = $advisorCounts[$workerId]['facturadas'] ?? 0;

        $jefeNode['children'][] = [
          'id' => $workerId,
          'name' => $asesor->nombre_completo,
          'level' => 'asesor',
          'entregas' => $asesorEntregas,
          'facturadas' => $asesorFacturacion,
          'reporteria_dealer_portal' => null,
        ];

        $jefeNode['entregas'] += $asesorEntregas;
        $jefeNode['facturadas'] += $asesorFacturacion;
      }
    }

    return $jefeNode;
  }

  /**
   * Construye nodo para camiones (jefe directo sin gerente)
   * Siempre retorna un nodo, incluso si no hay entregas
   */
  protected function buildCamionesNode(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, array $advisorBrands): ?array
  {
    // Obtener asignaciones de liderazgo
    $assignments = ApAssignmentLeadership::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->with(['boss:id,nombre_completo', 'worker:id,nombre_completo'])
      ->get();

    if ($assignments->isEmpty()) {
      return null;
    }

    // Calcular conteos por asesor
    $advisorCounts = $this->calculateAdvisorCounts($vehicles, $invoicedQuoteIds);

    // Construir mapa de jefe -> asesores
    $bossToWorkers = $assignments->groupBy('boss_id');

    // Identificar jefes principales (los que no son workers de nadie más)
    $allBossIds = $assignments->pluck('boss_id')->unique();
    $allWorkerIds = $assignments->pluck('worker_id')->unique();
    $topBossIds = $allBossIds->diff($allWorkerIds);

    // Si no hay jefes principales, usar el primer boss_id disponible
    if ($topBossIds->isEmpty()) {
      $topBossIds = $allBossIds->take(1);
    } else {
      $topBossIds = $topBossIds->take(1); // Solo el primer jefe principal
    }

    foreach ($topBossIds as $bossId) {
      $boss = Person::find($bossId);
      if (!$boss) {
        continue;
      }

      $bossNode = [
        'id' => $bossId,
        'name' => $boss->nombre_completo,
        'level' => 'jefe',
        'article_class' => 'CAMIONES',
        'entregas' => 0,
        'facturadas' => 0,
        'reporteria_dealer_portal' => null,
        'children' => [],
      ];

      // Agregar todos los asesores bajo este jefe (incluso sin entregas)
      $workers = $bossToWorkers->get($bossId);
      if ($workers) {
        foreach ($workers as $assignment) {
          $workerId = $assignment->worker_id;

          $worker = Person::find($workerId);
          if (!$worker) {
            continue;
          }

          $workerEntregas = $advisorCounts[$workerId]['entregas'] ?? 0;
          $workerFacturadas = $advisorCounts[$workerId]['facturadas'] ?? 0;
          $workerBrands = $advisorBrands[$workerId] ?? [];

          $bossNode['children'][] = [
            'id' => $workerId,
            'name' => $worker->nombre_completo,
            'level' => 'asesor',
            'brands' => !empty($workerBrands) ? $workerBrands : null,
            'entregas' => $workerEntregas,
            'facturadas' => $workerFacturadas,
            'reporteria_dealer_portal' => null,
          ];

          $bossNode['entregas'] += $workerEntregas;
          $bossNode['facturadas'] += $workerFacturadas;
        }
      }

      // Siempre retornar el nodo, incluso sin hijos
      return $bossNode;
    }

    return null;
  }

}

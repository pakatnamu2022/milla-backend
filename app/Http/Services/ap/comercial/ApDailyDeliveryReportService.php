<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroup;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Carbon\Carbon;
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
    return DB::table('ap_vehicles')
      ->join('ap_vehicle_delivery', 'ap_vehicles.id', '=', 'ap_vehicle_delivery.vehicle_id')
      ->join('purchase_request_quote', 'ap_vehicles.id', '=', 'purchase_request_quote.ap_vehicle_id')
      ->join('ap_opportunity', 'purchase_request_quote.opportunity_id', '=', 'ap_opportunity.id')
      ->join('ap_models_vn', 'ap_vehicles.ap_models_vn_id', '=', 'ap_models_vn.id')
      ->join('ap_class_article', 'ap_models_vn.class_id', '=', 'ap_class_article.id')
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
        'ap_class_article.description as article_class',
        'purchase_request_quote.id as quote_id',
      ])
      ->get();
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
   * Construye el resumen por clase de artículo
   *
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function buildSummaryByArticleClass(Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    $totals = [
      'TOTAL_AP_LIVIANOS' => ['entregas' => 0, 'facturacion' => 0, 'reporteria_dealer_portal' => null],
      'TOTAL_AP_CAMIONES' => ['entregas' => 0, 'facturacion' => 0, 'reporteria_dealer_portal' => null],
      'TOTAL_AP' => ['entregas' => 0, 'facturacion' => 0, 'reporteria_dealer_portal' => null],
    ];

    foreach ($vehicles as $vehicle) {
      $category = $this->classifyArticle($vehicle->article_class);

      // Contar entrega
      $totals[$category]['entregas']++;
      $totals['TOTAL_AP']['entregas']++;

      // Contar facturación si aplica
      if ($invoicedQuoteIds->contains($vehicle->quote_id)) {
        $totals[$category]['facturacion']++;
        $totals['TOTAL_AP']['facturacion']++;
      }
    }

    return $totals;
  }

  /**
   * Clasifica un artículo en LIVIANOS o CAMIONES según su descripción
   *
   * @param string $description
   * @return string
   */
  protected function classifyArticle(string $description): string
  {
    $description = strtoupper($description);

    if (str_contains($description, 'LIVIAN')) {
      return 'TOTAL_AP_LIVIANOS';
    }

    if (str_contains($description, 'CAMION') || str_contains($description, 'PESAD')) {
      return 'TOTAL_AP_CAMIONES';
    }

    // Por defecto, clasificar como livianos
    return 'TOTAL_AP_LIVIANOS';
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
        'facturacion' => $facturacion,
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
   * Construye el árbol jerárquico Gerente Comercial > Jefe > Asesor
   *
   * La jerarquía real es:
   * - Gerente Comercial: Asignado a grupos de marcas (ap_commercial_manager_brand_group_periods)
   * - Jefe: boss_id en ap_assignment_leadership_periods
   * - Asesor: worker_id en ap_assignment_leadership_periods con marcas asignadas
   *
   * @param int $year
   * @param int $month
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function buildHierarchyTree(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds): array
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
    $allWorkerIds = $assignments->pluck('worker_id')->unique();

    // Jefes son los boss_id que también pueden ser worker_id o no
    $jefeIds = $allBossIds;

    // Paso 8: Construir árbol por gerente comercial
    // Los jefes/asesores pueden aparecer bajo múltiples gerentes si manejan varios grupos
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
        'entregas' => 0,
        'facturacion' => 0,
        'reporteria_dealer_portal' => null,
        'children' => [],
      ];

      // Encontrar jefes que manejan este grupo de marcas
      foreach ($jefeIds as $jefeId) {
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
            $managerNode['facturacion'] += $jefeNode['facturacion'];
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
      'facturacion' => 0,
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
        $asesorFacturacion = $advisorCounts[$workerId]['facturacion'] ?? 0;

        $jefeNode['children'][] = [
          'id' => $workerId,
          'name' => $asesor->nombre_completo,
          'level' => 'asesor',
          'entregas' => $asesorEntregas,
          'facturacion' => $asesorFacturacion,
          'reporteria_dealer_portal' => null,
        ];

        $jefeNode['entregas'] += $asesorEntregas;
        $jefeNode['facturacion'] += $asesorFacturacion;
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
        'facturacion' => $advisorVehicles->filter(function ($vehicle) use ($invoicedQuoteIds) {
          return $invoicedQuoteIds->contains($vehicle->quote_id);
        })->count(),
      ];
    }

    return $counts;
  }

}

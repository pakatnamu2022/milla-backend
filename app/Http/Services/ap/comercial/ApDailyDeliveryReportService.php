<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroup;
use App\Models\gp\gestionhumana\personal\Worker;
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
   * @param string $fechaInicio Fecha inicio en formato Y-m-d
   * @param string $fechaFin Fecha fin en formato Y-m-d
   * @return array
   */
  public function generate(string $fechaInicio, string $fechaFin)
  {
    $carbonInicio = Carbon::parse($fechaInicio);
    $carbonFin = Carbon::parse($fechaFin);
    $year = $carbonInicio->year;
    $month = $carbonInicio->month;

    // Paso 1: Obtener vehículos con entrega en el rango de fechas
    $vehiclesWithDelivery = $this->getDeliveredVehicles($fechaInicio, $fechaFin);

    // Paso 2: Obtener IDs de cotizaciones facturadas en el rango de fechas
    $invoicedQuoteIds = $this->getInvoicedQuoteIds($fechaInicio, $fechaFin);

    // Paso 3: Construir resumen por grupo de marca (TRADICIONALES, CHINAS, CAMIONES)
    $summary = $this->buildSummaryByBrandGroup($vehiclesWithDelivery, $invoicedQuoteIds);

    // Paso 4: Calcular conteos por asesor (se usa en múltiples lugares)
    $advisorCounts = $this->calculateAdvisorCounts($vehiclesWithDelivery, $invoicedQuoteIds);

    // Paso 5: Construir desglose por asesores (jefes se excluyen por nombre; sus vehículos van a "Sin asesor")
    $jefeBossIds = ApAssignmentLeadership::where('year', $year)
      ->where('month', $month)
      ->where('status', 1)
      ->pluck('boss_id')
      ->filter()
      ->unique()
      ->toArray();
    $advisorCountsForAdvisors = $this->moveBossCountsToSinAsesor($advisorCounts, $jefeBossIds);
    $advisors = $this->buildAdvisorBreakdownFromCounts($advisorCountsForAdvisors);

    // Paso 6: Construir árbol jerárquico
    $hierarchy = $this->buildHierarchyTree($year, $month, $vehiclesWithDelivery, $invoicedQuoteIds, $advisorCounts);

    // Paso 6: Construir reporte por marcas y sedes
    $brandReport = $this->buildBrandReport($year, $month, $vehiclesWithDelivery, $invoicedQuoteIds, $fechaInicio, $fechaFin);

    // Paso 7: Construir reporte de avance por sede
    $avancePorSede = $this->buildAvancePorSede($year, $month, $vehiclesWithDelivery, $invoicedQuoteIds, $fechaInicio, $fechaFin);

    // Paso 8: Construir reporte de compras por marca y por sede
    $purchasesReport = $this->buildPurchasesReport($fechaInicio, $fechaFin, $year, $month);

    return [
      'fecha_inicio' => $fechaInicio,
      'fecha_fin' => $fechaFin,
      'period' => [
        'year' => $year,
        'month' => $month,
      ],
      'summary' => $summary,
      'advisors' => $advisors,
      'hierarchy' => $hierarchy,
      'brand_report' => $brandReport,
      'avance_por_sede' => $avancePorSede,
      'purchases_report' => $purchasesReport,
    ];
  }

  /**
   * Obtiene vehículos con entrega realizada en el rango de fechas O con facturación válida
   * La entrega y facturación son independientes
   *
   * @param string $fechaInicio
   * @param string $fechaFin
   * @return Collection
   */
  protected function getDeliveredVehicles(string $fechaInicio, string $fechaFin): Collection
  {
    $vehicles = DB::table('ap_vehicles')
      ->leftJoin('ap_vehicle_delivery', function ($join) {
        $join->on('ap_vehicles.id', '=', 'ap_vehicle_delivery.vehicle_id')
          ->whereNull('ap_vehicle_delivery.deleted_at');
      })
      ->join('purchase_request_quote', 'ap_vehicles.id', '=', 'purchase_request_quote.ap_vehicle_id')
      ->join('ap_opportunity', 'purchase_request_quote.opportunity_id', '=', 'ap_opportunity.id')
      ->join('ap_models_vn', 'ap_vehicles.ap_models_vn_id', '=', 'ap_models_vn.id')
      ->join('ap_class_article', 'ap_models_vn.class_id', '=', 'ap_class_article.id')
      ->leftJoin('ap_families', 'ap_models_vn.family_id', '=', 'ap_families.id')
      ->leftJoin('ap_vehicle_brand', 'ap_families.brand_id', '=', 'ap_vehicle_brand.id')
      ->leftJoin('config_sede', 'purchase_request_quote.sede_id', '=', 'config_sede.id')
      ->where(function ($query) use ($fechaInicio, $fechaFin) {
        // Tiene entrega en el rango de fechas
        $query->where(function ($q) use ($fechaInicio, $fechaFin) {
          $q->whereBetween('ap_vehicle_delivery.real_delivery_date', [$fechaInicio, $fechaFin])
            ->whereNotNull('ap_vehicle_delivery.real_delivery_date');
        })
          // O tiene factura válida en el rango de fechas (independiente de la entrega)
          ->orWhereExists(function ($q) use ($fechaInicio, $fechaFin) {
            $q->select(DB::raw(1))
              ->from('ap_billing_electronic_documents')
              ->whereColumn('ap_billing_electronic_documents.purchase_request_quote_id', 'purchase_request_quote.id')
              ->where('ap_billing_electronic_documents.is_advance_payment', false)
              ->where('ap_billing_electronic_documents.aceptada_por_sunat', true)
              ->where('ap_billing_electronic_documents.anulado', false)
              ->whereIn('ap_billing_electronic_documents.sunat_concept_document_type_id', [
                SunatConcepts::ID_FACTURA_ELECTRONICA,
                SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA,
              ])
              ->where('ap_billing_electronic_documents.area_id', ApMasters::AREA_COMERCIAL)
              ->whereBetween('ap_billing_electronic_documents.fecha_de_emision', [$fechaInicio, $fechaFin])
              ->whereNull('ap_billing_electronic_documents.deleted_at');
          });
      })
      ->whereNull('ap_vehicles.deleted_at')
      ->whereNull('purchase_request_quote.deleted_at')
      ->select([
        'ap_vehicles.id as vehicle_id',
        'ap_vehicle_delivery.real_delivery_date',
        'ap_opportunity.worker_id as advisor_id',
        'purchase_request_quote.sede_id',
        'config_sede.abreviatura as sede_name',
        'ap_class_article.id as article_class_id',
        'ap_class_article.description as article_class_description',
        'ap_class_article.type_class_id',
        'purchase_request_quote.id as quote_id',
        'ap_vehicle_brand.id as brand_id',
        'ap_vehicle_brand.name as brand_name',
        'ap_vehicle_brand.group_id as brand_group_id',
      ])
      ->get();
    return $vehicles;
  }

  /**
   * Obtiene las compras (purchase orders) en el rango de fechas
   *
   * @param string $fechaInicio
   * @param string $fechaFin
   * @return Collection
   */
  protected function getPurchaseOrders(string $fechaInicio, string $fechaFin): Collection
  {
    $purchases = DB::table('ap_purchase_order')
      ->join('ap_vehicle_movement', 'ap_purchase_order.vehicle_movement_id', '=', 'ap_vehicle_movement.id')
      ->join('ap_vehicles', 'ap_vehicle_movement.ap_vehicle_id', '=', 'ap_vehicles.id')
      ->join('ap_models_vn', 'ap_vehicles.ap_models_vn_id', '=', 'ap_models_vn.id')
      ->join('ap_class_article', 'ap_models_vn.class_id', '=', 'ap_class_article.id')
      ->leftJoin('ap_families', 'ap_models_vn.family_id', '=', 'ap_families.id')
      ->leftJoin('ap_vehicle_brand', 'ap_families.brand_id', '=', 'ap_vehicle_brand.id')
      ->whereBetween('ap_purchase_order.emission_date', [$fechaInicio, $fechaFin])
      ->where('ap_purchase_order.status', true)
      ->whereNull('ap_purchase_order.deleted_at')
      ->select([
        'ap_purchase_order.id as purchase_order_id',
        'ap_purchase_order.sede_id',
        'ap_class_article.type_class_id',
        'ap_vehicle_brand.id as brand_id',
        'ap_vehicle_brand.name as brand_name',
        'ap_vehicle_brand.group_id as brand_group_id',
      ])
      ->get();

    return $purchases;
  }

  /**
   * Obtiene IDs de cotizaciones que tienen facturas válidas
   * Sin filtro de fecha - una factura válida cuenta independientemente de cuándo se emitió
   *
   * @return Collection
   */
  protected function getInvoicedQuoteIds(): Collection
  {
    return ElectronicDocument::where('is_advance_payment', false)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereIn('sunat_concept_document_type_id', [
        SunatConcepts::ID_FACTURA_ELECTRONICA,
        SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA,
      ])
      ->where('area_id', ApMasters::AREA_COMERCIAL)
      ->whereNull('deleted_at')
      ->distinct()
      ->pluck('purchase_request_quote_id');
  }

  protected function buildSummaryByBrandGroup(Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    $camionTypeId = ApMasters::ofType('CLASS_TYPE')
      ->where('code', ApMasters::CLASS_TYPE_CAMION_CODE)
      ->value('id');

    $tradicionalInchcapeIds = ApMasters::where('type', 'GRUPO_MARCAS')
      ->whereIn('description', ['TRADICIONAL', 'INCHCAPE'])
      ->pluck('id')
      ->toArray();

    $chinaGroupId = ApMasters::where('type', 'GRUPO_MARCAS')
      ->where('description', 'CHINA')
      ->value('id');

    $calc = function (Collection $group) use ($invoicedQuoteIds): array {
      return [
        'entregas'                 => $group->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
        'facturadas'               => $group->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
        'reporteria_dealer_portal' => null,
      ];
    };

    $tradicionales = $vehicles->filter(
      fn($v) => $v->type_class_id != $camionTypeId && in_array($v->brand_group_id, $tradicionalInchcapeIds)
    );

    $chinas = $vehicles->filter(
      fn($v) => $v->type_class_id != $camionTypeId && $v->brand_group_id == $chinaGroupId
    );

    $camiones = $vehicles->filter(
      fn($v) => $v->type_class_id == $camionTypeId
    );

    $tradData   = $calc($tradicionales);
    $chinaData  = $calc($chinas);
    $camionData = $calc($camiones);

    return [
      'TRADICIONALES' => $tradData,
      'CHINAS'        => $chinaData,
      'CAMIONES'      => $camionData,
      'TOTAL' => [
        'entregas'                 => $tradData['entregas'] + $chinaData['entregas'] + $camionData['entregas'],
        'facturadas'               => $tradData['facturadas'] + $chinaData['facturadas'] + $camionData['facturadas'],
        'reporteria_dealer_portal' => null,
      ],
    ];
  }

  /**
   * Construye el desglose por asesores desde conteos pre-calculados
   *
   * @param array $advisorCounts
   * @return array
   */
  protected function buildAdvisorBreakdownFromCounts(array $advisorCounts): array
  {
    $advisorStats = [];

    foreach ($advisorCounts as $advisorId => $counts) {
      if ($advisorId === 'sin_asesor') {
        $advisorStats[] = [
          'id'                       => null,
          'name'                     => 'Sin asesor',
          'entregas'                 => $counts['entregas'],
          'facturadas'               => $counts['facturadas'],
          'reporteria_dealer_portal' => null,
        ];
        continue;
      }

      $advisor = Worker::find($advisorId);

      $advisorStats[] = [
        'id'                       => $advisorId,
        'name'                     => $advisor ? $advisor->nombre_completo : 'Desconocido',
        'entregas'                 => $counts['entregas'],
        'facturadas'               => $counts['facturadas'],
        'reporteria_dealer_portal' => null,
      ];
    }

    // Ordenar por nombre
    usort($advisorStats, function ($a, $b) {
      return strcmp($a['name'], $b['name']);
    });

    return $advisorStats;
  }

  protected function moveBossCountsToSinAsesor(array $advisorCounts, array $bossIds): array
  {
    $sinAsesorEntregas = $advisorCounts['sin_asesor']['entregas'] ?? 0;
    $sinAsesorFacturadas = $advisorCounts['sin_asesor']['facturadas'] ?? 0;

    foreach ($bossIds as $bossId) {
      if (isset($advisorCounts[$bossId])) {
        $sinAsesorEntregas += $advisorCounts[$bossId]['entregas'];
        $sinAsesorFacturadas += $advisorCounts[$bossId]['facturadas'];
        unset($advisorCounts[$bossId]);
      }
    }

    if ($sinAsesorEntregas > 0 || $sinAsesorFacturadas > 0) {
      $advisorCounts['sin_asesor'] = [
        'entregas'   => $sinAsesorEntregas,
        'facturadas' => $sinAsesorFacturadas,
      ];
    }

    return $advisorCounts;
  }

  /**
   * Construye el desglose por asesores (método legacy, no usar)
   * Las entregas y facturaciones son independientes
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

      // Entregas: solo vehículos con fecha de entrega real
      $entregas = $advisorVehicles->filter(function ($vehicle) {
        return !is_null($vehicle->real_delivery_date);
      })->count();

      // Facturadas: vehículos con factura válida (independiente de la entrega)
      $facturacion = $advisorVehicles->filter(function ($vehicle) use ($invoicedQuoteIds) {
        return $invoicedQuoteIds->contains($vehicle->quote_id);
      })->count();

      $advisor = Worker::find($advisorId);

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
   * @param array $advisorCounts Conteos pre-calculados por asesor
   * @return array
   */
  protected function buildHierarchyTree(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, array $advisorCounts): array
  {
    // Obtener IDs de tipos de clase
    $vehicleTypeId = ApMasters::ofType('CLASS_TYPE')
      ->where('code', ApMasters::CLASS_TYPE_VEHICLE_CODE)
      ->value('id');

    $camionTypeId = ApMasters::ofType('CLASS_TYPE')
      ->where('code', ApMasters::CLASS_TYPE_CAMION_CODE)
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

    // Los conteos por asesor ya vienen pre-calculados como parámetro

    // Construir mapa de asesor -> grupos de marcas
    $advisorBrandGroups = $this->buildAdvisorBrandGroupMap($brandAssignments);

    // Construir mapa de asesor -> nombres de marcas
    $advisorBrands = $this->buildAdvisorBrandsMap($brandAssignments);

    // Construir mapa de jefe -> asesores
    $bossToWorkers = $assignments->groupBy('boss_id');

    // Árbol dinámico: agrupar por gerente (no por grupo de marcas)
    $tree = [];

    // Paso 1: Detectar jefe CAMIONES — el jefe cuyos workers tienen vehículos de tipo camión.
    // Se busca en TODOS los jefes (no solo los sin marcas), porque sus workers pueden tener
    // marcas asignadas pero operar vehículos camión.
    $camionAdvisorIds = $vehiclesCamiones->pluck('advisor_id')->filter()->unique()->toArray();
    $camionesJefeId = null;

    foreach ($bossToWorkers->keys() as $jefeId) {
      $workers = $bossToWorkers->get($jefeId);
      if ($workers) {
        foreach ($workers as $workerAssignment) {
          if (in_array($workerAssignment->worker_id, $camionAdvisorIds)) {
            $camionesJefeId = $jefeId;
            break 2;
          }
        }
      }
    }

    // Paso 2: Detectar jefes "otros independientes" — jefes cuyos workers NO tienen marcas asignadas
    // y que no son el jefe de CAMIONES.
    $otherIndependentJefeIds = [];
    foreach ($bossToWorkers->keys() as $jefeId) {
      if ($jefeId === $camionesJefeId) {
        continue;
      }
      $workers = $bossToWorkers->get($jefeId);
      $hasAnyBrandedWorker = false;
      foreach ($workers as $workerAssignment) {
        if (!empty($advisorBrandGroups[$workerAssignment->worker_id])) {
          $hasAnyBrandedWorker = true;
          break;
        }
      }
      if (!$hasAnyBrandedWorker) {
        $otherIndependentJefeIds[] = $jefeId;
      }
    }

    // Todos los jefes que NO deben aparecer dentro de un nodo de gerente
    $excludedFromGerenteIds = array_filter(array_merge(
      $camionesJefeId !== null ? [$camionesJefeId] : [],
      $otherIndependentJefeIds
    ));

    // Agrupar gerentes por commercial_manager_id (para unificar si maneja múltiples grupos)
    $managersByPerson = $commercialManagers->groupBy('commercial_manager_id');

    // IDs de workers asignados en el mes (para detectar huérfanos)
    $assignedWorkerIds = $assignments->pluck('worker_id')->filter()->unique()->toArray();

    foreach ($managersByPerson as $managerId => $managerAssignments) {
      $brandGroupIds = $managerAssignments->pluck('brand_group_id')->toArray();
      $brandGroupNames = $managerAssignments->pluck('brandGroup.description')->filter()->unique()->implode(', ');

      $node = $this->buildGerenteNodeMultiGroup($managerId, $brandGroupIds, $brandGroupNames, $bossToWorkers, $advisorBrandGroups, $advisorBrands, $advisorCounts, 'VEHICULOS NUEVO', $vehicleTypeId, $vehicles, $invoicedQuoteIds, $excludedFromGerenteIds, $assignedWorkerIds, $camionTypeId);
      if ($node) {
        $tree[] = $node;
      }
    }

    // Nodo CAMIONES (jefe directo, sin gerente)
    if ($camionesJefeId) {
      $node = $this->buildCamionesNode($year, $month, $vehiclesCamiones, $invoicedQuoteIds, $advisorBrands, $advisorCounts, $camionesJefeId);
      if ($node) {
        $tree[] = $node;
      }
    }

    // Nodos para jefes independientes no-CAMIONES (sin marcas en sus asesores)
    foreach ($otherIndependentJefeIds as $jefeId) {
      $node = $this->buildIndependentJefeNode($jefeId, $bossToWorkers, $advisorBrands, $advisorCounts);
      if ($node) {
        $tree[] = $node;
      }
    }

    // Nodo SIN ASESOR top-level: solo vehículos huérfanos cuyo grupo no está gestionado por ningún gerente
    $allManagedBrandGroupIds = $commercialManagers->pluck('brand_group_id')->unique()->toArray();
    $sinAsesorVehicles = $vehicles->filter(
      fn($v) => (is_null($v->advisor_id) || !in_array($v->advisor_id, $assignedWorkerIds))
        && !in_array($v->brand_group_id, $allManagedBrandGroupIds)
    );
    if ($sinAsesorVehicles->isNotEmpty()) {
      $tradicGroupIds = ApMasters::where('type', 'GRUPO_MARCAS')
        ->whereIn('description', ['TRADICIONAL', 'INCHCAPE'])
        ->pluck('id')->toArray();
      $chinaGroupId = ApMasters::where('type', 'GRUPO_MARCAS')
        ->where('description', 'CHINA')->value('id');

      $calcSinAsesor = function (Collection $g) use ($invoicedQuoteIds): array {
        return [
          'entregas'   => $g->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
          'facturadas' => $g->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
        ];
      };

      $groups = [
        'TRADICIONALES' => $sinAsesorVehicles->filter(fn($v) => $v->type_class_id != $camionTypeId && in_array($v->brand_group_id, $tradicGroupIds)),
        'CHINAS'        => $sinAsesorVehicles->filter(fn($v) => $v->type_class_id != $camionTypeId && $v->brand_group_id == $chinaGroupId),
        'CAMIONES'      => $sinAsesorVehicles->filter(fn($v) => $v->type_class_id == $camionTypeId),
      ];

      $sinAsesorChildren = [];
      $sinAsesorEntregas = 0;
      $sinAsesorFacturadas = 0;

      foreach ($groups as $label => $group) {
        if ($group->isEmpty()) {
          continue;
        }
        $totals = $calcSinAsesor($group);
        $sinAsesorChildren[] = [
          'id'                       => null,
          'name'                     => $label,
          'level'                    => 'grupo',
          'entregas'                 => $totals['entregas'],
          'facturadas'               => $totals['facturadas'],
          'reporteria_dealer_portal' => null,
        ];
        $sinAsesorEntregas   += $totals['entregas'];
        $sinAsesorFacturadas += $totals['facturadas'];
      }

      if (!empty($sinAsesorChildren)) {
        $tree[] = [
          'id'                       => null,
          'name'                     => 'Sin asesor',
          'level'                    => 'sin_asesor',
          'entregas'                 => $sinAsesorEntregas,
          'facturadas'               => $sinAsesorFacturadas,
          'reporteria_dealer_portal' => null,
          'children'                 => $sinAsesorChildren,
        ];
      }
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

      $manager = Worker::find($managerId);
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
        $jefe = Worker::find($jefeId);
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
      $boss = Worker::find($bossId);
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

          $worker = Worker::find($workerId);
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
    $jefe = Worker::find($jefeId);
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
        $asesor = Worker::find($workerId);
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
   * Las entregas y facturaciones son independientes
   *
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @return array
   */
  protected function calculateAdvisorCounts(Collection $vehicles, Collection $invoicedQuoteIds): array
  {
    $counts = [];

    foreach ($vehicles->groupBy('advisor_id') as $advisorId => $advisorVehicles) {
      $key = $advisorId ?: 'sin_asesor';

      $counts[$key] = [
        'entregas'   => $advisorVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
        'facturadas' => $advisorVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
      ];
    }

    return $counts;
  }

  /**
   * Construye nodo de gerente que maneja múltiples grupos de marcas
   */
  protected function buildGerenteNodeMultiGroup(int $managerId, array $brandGroupIds, string $brandGroupNames, Collection $bossToWorkers, array $advisorBrandGroups, array $advisorBrands, array $advisorCounts, string $className, int $vehicleTypeId, Collection $allVehicles, Collection $invoicedQuoteIds, array $excludedJefeIds = [], ?array $assignedWorkerIds = null, int $camionTypeId = 0): ?array
  {
    $manager = Worker::find($managerId);

    if (!$manager) {
      return null;
    }

    // Usar los conteos pre-calculados que ya vienen del parámetro
    // NO recalcular para evitar perder datos de vehículos facturados

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

    // Filtrar conteos de asesor solo a vehículos de los grupos de marcas de este gerente
    // (excluir camiones) para evitar cruce con vehículos de otros grupos
    $groupFilteredVehicles = $allVehicles->filter(function ($v) use ($brandGroupIds, $camionTypeId) {
      return in_array($v->brand_group_id, $brandGroupIds)
        && ($camionTypeId === 0 || $v->type_class_id != $camionTypeId);
    });
    $groupAdvisorCounts = $this->calculateAdvisorCounts($groupFilteredVehicles, $invoicedQuoteIds);

    // Identificar todos los jefes
    $allBossIds = $bossToWorkers->keys();

    // Encontrar jefes que manejan cualquiera de estos grupos de marcas
    foreach ($allBossIds as $jefeId) {
      // Excluir jefes top-level (camiones y otros independientes)
      if (in_array($jefeId, $excludedJefeIds)) {
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

    // Agregar "Sin asesor" para vehículos huérfanos de este grupo (no camiones)
    if ($assignedWorkerIds !== null) {
      $orphanVehicles = $allVehicles->filter(function ($v) use ($brandGroupIds, $assignedWorkerIds, $camionTypeId) {
        return in_array($v->brand_group_id, $brandGroupIds)
          && ($camionTypeId === 0 || $v->type_class_id != $camionTypeId)
          && (is_null($v->advisor_id) || !in_array($v->advisor_id, $assignedWorkerIds));
      });

      if ($orphanVehicles->isNotEmpty()) {
        $orphanEntregas = $orphanVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
        $orphanFacturadas = $orphanVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();
        $managerNode['children'][] = [
          'id' => null,
          'name' => 'Sin asesor',
          'level' => 'sin_asesor',
          'entregas' => $orphanEntregas,
          'facturadas' => $orphanFacturadas,
          'reporteria_dealer_portal' => null,
        ];
        $managerNode['entregas'] += $orphanEntregas;
        $managerNode['facturadas'] += $orphanFacturadas;
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
    $manager = Worker::find($managerId);

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
    $jefe = Worker::find($jefeId);
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

      // Incluir asesores que: tienen marcas de este grupo, no tienen marcas asignadas,
      // O tienen vehículos reales en este grupo (cruza de grupo)
      $hasAnyGroup = !empty(array_intersect($brandGroupIds, $workerGroups));
      $hasCountsInGroup = (($advisorCounts[$workerId]['facturadas'] ?? 0) > 0)
        || (($advisorCounts[$workerId]['entregas'] ?? 0) > 0);
      if ($hasAnyGroup || empty($workerGroups) || $hasCountsInGroup) {
        $asesor = Worker::find($workerId);
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
    $jefe = Worker::find($jefeId);
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
        $asesor = Worker::find($workerId);
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
  protected function buildCamionesNode(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, array $advisorBrands, array $advisorCounts, ?int $specificBossId = null): ?array
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

    // Usar los conteos pre-calculados que vienen como parámetro

    // Construir mapa de jefe -> asesores
    $bossToWorkers = $assignments->groupBy('boss_id');

    // Identificar jefes principales (los que no son workers de nadie más)
    $allBossIds = $assignments->pluck('boss_id')->unique();
    $allWorkerIds = $assignments->pluck('worker_id')->unique();
    $topBossIds = $allBossIds->diff($allWorkerIds);

    // Usar el jefe específico si se proporcionó, si no auto-detectar el primero
    if ($specificBossId !== null) {
      $topBossIds = collect([$specificBossId]);
    } elseif ($topBossIds->isEmpty()) {
      $topBossIds = $allBossIds->take(1);
    } else {
      $topBossIds = $topBossIds->take(1);
    }

    foreach ($topBossIds as $bossId) {
      $boss = Worker::find($bossId);
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

          $worker = Worker::find($workerId);
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

  protected function buildIndependentJefeNode(int $jefeId, Collection $bossToWorkers, array $advisorBrands, array $advisorCounts): ?array
  {
    $jefe = Worker::find($jefeId);
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
      $worker = Worker::find($workerId);
      if (!$worker) {
        continue;
      }

      $workerEntregas = $advisorCounts[$workerId]['entregas'] ?? 0;
      $workerFacturadas = $advisorCounts[$workerId]['facturadas'] ?? 0;
      $workerBrands = $advisorBrands[$workerId] ?? [];

      $jefeNode['children'][] = [
        'id' => $workerId,
        'name' => $worker->nombre_completo,
        'level' => 'asesor',
        'brands' => !empty($workerBrands) ? $workerBrands : null,
        'entregas' => $workerEntregas,
        'facturadas' => $workerFacturadas,
        'reporteria_dealer_portal' => null,
      ];

      $jefeNode['entregas'] += $workerEntregas;
      $jefeNode['facturadas'] += $workerFacturadas;
    }

    return empty($jefeNode['children']) ? null : $jefeNode;
  }

  /**
   * Construye reporte por marcas y sedes
   *
   * @param int $year
   * @param int $month
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @param string $fechaInicio
   * @param string $fechaFin
   * @return array
   */
  protected function buildBrandReport(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, string $fechaInicio, string $fechaFin): array
  {
    // Obtener IDs de tipos de clase
    $vehicleTypeId = ApMasters::ofType('CLASS_TYPE')
      ->where('code', ApMasters::CLASS_TYPE_VEHICLE_CODE)
      ->value('id');

    $camionTypeId = ApMasters::ofType('CLASS_TYPE')
      ->where('code', ApMasters::CLASS_TYPE_CAMION_CODE)
      ->value('id');

    // Obtener compras del rango de fechas
    $purchaseOrders = $this->getPurchaseOrders($fechaInicio, $fechaFin);

    // Mapear compras a shops de sus sedes
    $sedeToShopMap = $this->getSedeToShopMap();
    $purchaseOrders = $purchaseOrders->map(function ($p) use ($sedeToShopMap) {
      $p->shop_id = $sedeToShopMap[$p->sede_id] ?? null;
      return $p;
    });

    // Obtener asignaciones de sedes de los asesores
    $advisorSedeAssignments = $this->getAdvisorSedeAssignments($year, $month);

    // Mapear cada vehículo a la sede de su asesor
    $vehicles = $vehicles->map(function ($v) use ($advisorSedeAssignments) {
      $v->advisor_sede_id = $advisorSedeAssignments[$v->advisor_id]['sede_id'] ?? null;
      $v->advisor_sede_name = $advisorSedeAssignments[$v->advisor_id]['sede_name'] ?? 'Sin Sede';
      return $v;
    });

    // Separar vehículos y camiones
    $livianos = $vehicles->filter(fn($v) => $v->type_class_id == $vehicleTypeId);
    $camiones = $vehicles->filter(fn($v) => $v->type_class_id == $camionTypeId);

    // Separar compras de livianos y camiones
    $comprasLivianos = $purchaseOrders->filter(fn($p) => $p->type_class_id == $vehicleTypeId);
    $comprasCamiones = $purchaseOrders->filter(fn($p) => $p->type_class_id == $camionTypeId);

    // Obtener todas las sedes disponibles
    $allSedes = $this->getAllSedesFromAssignments($year, $month);

    $report = [];

    // Reporte por grupos de marcas (Chinas, Tradicionales, Inchcape)
    $brandGroupSections = $this->buildBrandGroupSections($year, $month, $vehicleTypeId, $livianos, $invoicedQuoteIds, $allSedes, $comprasLivianos);
    foreach ($brandGroupSections as $section) {
      $report[] = $section;
    }

    // Reporte de camiones
    $report[] = $this->buildCamionesSection($year, $month, $camionTypeId, $camiones, $invoicedQuoteIds, $allSedes, $comprasCamiones);

    return $report;
  }

  /**
   * Construye sección de totales generales
   * Las entregas y facturaciones son independientes
   */
  protected function buildTotalSection(Collection $livianos, Collection $camiones, Collection $invoicedQuoteIds): array
  {
    $livianosCompras = $livianos->whereNotNull('purchase_order_id')->count();
    $livianosEntregas = $livianos->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $livianosFacturadas = $livianos->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    $camionesCompras = $camiones->whereNotNull('purchase_order_id')->count();
    $camionesEntregas = $camiones->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $camionesFacturadas = $camiones->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    return [
      'title' => 'TOTALES GENERALES',
      'items' => [
        [
          'name' => 'TOTAL AP LIVIANOS',
          'compras' => $livianosCompras,
          'entregas' => $livianosEntregas,
          'facturadas' => $livianosFacturadas,
          'reporteria_dealer_portal' => null,
        ],
        [
          'name' => 'TOTAL AP CAMIONES',
          'compras' => $camionesCompras,
          'entregas' => $camionesEntregas,
          'facturadas' => $camionesFacturadas,
          'reporteria_dealer_portal' => null,
        ],
        [
          'name' => 'TOTAL AP',
          'compras' => $livianosCompras + $camionesCompras,
          'entregas' => $livianosEntregas + $camionesEntregas,
          'facturadas' => $livianosFacturadas + $camionesFacturadas,
          'reporteria_dealer_portal' => null,
        ],
      ],
    ];
  }

  /**
   * Construye secciones por grupo de marcas.
   * Orden: TRADICIONALES (TRADICIONAL + INCHCAPE fusionados), CHINA
   */
  protected function buildBrandGroupSections(int $year, int $month, int $typeClassId, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders): array
  {
    $sections = [];

    // SECCIÓN 1: TRADICIONALES = TRADICIONAL + INCHCAPE fusionados
    $tradicionalInchcapeGroups = ApMasters::where('type', 'GRUPO_MARCAS')
      ->whereIn('description', ['TRADICIONAL', 'INCHCAPE'])
      ->get();

    $tradicionalInchcapeIds = $tradicionalInchcapeGroups->pluck('id')->toArray();

    if (!empty($tradicionalInchcapeIds)) {
      $traditionalesVehicles = $vehicles->whereIn('brand_group_id', $tradicionalInchcapeIds);
      $traditionalesPurchases = $purchaseOrders->whereIn('brand_group_id', $tradicionalInchcapeIds);
      $brandsByShopTradicionales = $this->getBrandsByShopForGroups($year, $month, $tradicionalInchcapeIds, $typeClassId);
      $sections[] = $this->buildBrandGroupSectionForGroups('TRADICIONALES', $traditionalesVehicles, $invoicedQuoteIds, $allSedes, $traditionalesPurchases, $brandsByShopTradicionales);
    }

    // SECCIÓN 2: CHINA
    $chinaGroup = ApMasters::where('type', 'GRUPO_MARCAS')->where('description', 'CHINA')->first();
    if ($chinaGroup) {
      $chinaVehicles = $vehicles->where('brand_group_id', $chinaGroup->id);
      $chinaPurchases = $purchaseOrders->where('brand_group_id', $chinaGroup->id);
      $brandsByShopChina = $this->getBrandsByShop($year, $month, $chinaGroup->id, $typeClassId);
      $sections[] = $this->buildBrandGroupSection($chinaGroup, $chinaVehicles, $invoicedQuoteIds, $allSedes, $chinaPurchases, $brandsByShopChina);
    }

    return $sections;
  }

  /**
   * Obtiene marcas asignadas por shop para múltiples grupos de marcas
   * Retorna: [shop_id => [brand_id => brand_name]]
   */
  protected function getBrandsByShopForGroups(int $year, int $month, array $brandGroupIds, int $typeClassId): array
  {
    $sedeToShopMap = $this->getSedeToShopMap();

    $assignments = DB::table('ap_assign_brand_consultant')
      ->join('ap_vehicle_brand', 'ap_assign_brand_consultant.brand_id', '=', 'ap_vehicle_brand.id')
      ->where('ap_assign_brand_consultant.year', $year)
      ->where('ap_assign_brand_consultant.month', $month)
      ->whereIn('ap_vehicle_brand.group_id', $brandGroupIds)
      ->where('ap_vehicle_brand.type_class_id', $typeClassId)
      ->where('ap_vehicle_brand.status', 1)
      ->whereNull('ap_vehicle_brand.deleted_at')
      ->whereNull('ap_assign_brand_consultant.deleted_at')
      ->select([
        'ap_assign_brand_consultant.sede_id',
        'ap_vehicle_brand.id as brand_id',
        'ap_vehicle_brand.name as brand_name',
      ])
      ->distinct()
      ->get();

    $brandsByShop = [];
    foreach ($assignments as $assignment) {
      $shopId = $sedeToShopMap[$assignment->sede_id] ?? null;
      if ($shopId) {
        if (!isset($brandsByShop[$shopId])) {
          $brandsByShop[$shopId] = [];
        }
        $brandsByShop[$shopId][$assignment->brand_id] = $assignment->brand_name;
      }
    }

    return $brandsByShop;
  }

  /**
   * Construye una sección de grupo de marcas unificando múltiples grupos bajo un mismo título
   */
  protected function buildBrandGroupSectionForGroups(string $title, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders, array $brandsByShop): array
  {
    $items = [];

    $totalCompras = $purchaseOrders->count();
    $totalEntregas = $vehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $totalFacturadas = $vehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    foreach ($allSedes as $sedeId => $sedeName) {
      $shopBrands = $brandsByShop[$sedeId] ?? [];

      if (empty($shopBrands)) {
        continue;
      }

      $sedeVehicles = $vehicles->filter(fn($v) => $v->advisor_sede_id == $sedeId);
      $sedePurchases = $purchaseOrders->filter(fn($p) => $p->shop_id == $sedeId);

      $items[] = [
        'name' => $sedeName,
        'level' => 'sede',
        'compras' => $sedePurchases->count(),
        'entregas' => $sedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
        'facturadas' => $sedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
        'reporteria_dealer_portal' => null,
      ];

      foreach ($shopBrands as $brandId => $brandName) {
        $brandVehicles = $sedeVehicles->filter(fn($v) => $v->brand_id == $brandId);
        $brandPurchases = $sedePurchases->filter(fn($p) => $p->brand_id == $brandId);

        $items[] = [
          'name' => $brandName,
          'level' => 'brand',
          'compras' => $brandPurchases->count(),
          'entregas' => $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
          'facturadas' => $brandVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
          'reporteria_dealer_portal' => null,
        ];
      }
    }

    // Vehículos sin sede asignada (asesor sin asignación de shop en el período)
    $sinSedeVehicles = $vehicles->filter(fn($v) => is_null($v->advisor_sede_id));
    $sinSedeCompras = $purchaseOrders->filter(fn($p) => is_null($p->shop_id))->count();
    $sinSedeEntregas = $sinSedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $sinSedeFacturadas = $sinSedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    if ($sinSedeFacturadas > 0 || $sinSedeEntregas > 0 || $sinSedeCompras > 0) {
      $items[] = [
        'name' => 'Sin Sede',
        'level' => 'sede',
        'compras' => $sinSedeCompras,
        'entregas' => $sinSedeEntregas,
        'facturadas' => $sinSedeFacturadas,
        'reporteria_dealer_portal' => null,
      ];
    }

    return [
      'title' => $title,
      'total_compras' => $totalCompras,
      'total_entregas' => $totalEntregas,
      'total_facturadas' => $totalFacturadas,
      'items' => $items,
    ];
  }

  /**
   * Construye una sección de grupo de marcas con sus sedes y marcas
   * Las entregas y facturaciones son independientes
   */
  protected function buildBrandGroupSection($brandGroup, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders, array $brandsByShop): array
  {
    $groupName = $brandGroup->description;

    $items = [];

    // Total del grupo
    $totalCompras = $purchaseOrders->count();
    $totalEntregas = $vehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $totalFacturadas = $vehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    // Por cada shop que tenga marcas asignadas
    foreach ($allSedes as $sedeId => $sedeName) {
      // Obtener solo las marcas asignadas a asesores en esta sede/shop
      $shopBrands = $brandsByShop[$sedeId] ?? [];

      // Si no hay marcas asignadas en este shop, no mostrar el shop
      if (empty($shopBrands)) {
        continue;
      }

      // Filtrar vehículos y compras de esta sede/shop
      $sedeVehicles = $vehicles->filter(fn($v) => $v->advisor_sede_id == $sedeId);
      $sedePurchases = $purchaseOrders->filter(fn($p) => $p->shop_id == $sedeId);

      // Total por sede
      $sedeCompras = $sedePurchases->count();
      $sedeEntregas = $sedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
      $sedeFacturadas = $sedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

      $items[] = [
        'name' => $sedeName,
        'level' => 'sede',
        'compras' => $sedeCompras,
        'entregas' => $sedeEntregas,
        'facturadas' => $sedeFacturadas,
        'reporteria_dealer_portal' => null,
      ];

      // Mostrar solo las marcas asignadas (aunque tengan 0)
      foreach ($shopBrands as $brandId => $brandName) {
        $brandVehicles = $sedeVehicles->filter(fn($v) => $v->brand_id == $brandId);
        $brandPurchases = $sedePurchases->filter(fn($p) => $p->brand_id == $brandId);

        $brandCompras = $brandPurchases->count();
        $brandEntregas = $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
        $brandFacturadas = $brandVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

        $items[] = [
          'name' => $brandName,
          'level' => 'brand',
          'compras' => $brandCompras,
          'entregas' => $brandEntregas,
          'facturadas' => $brandFacturadas,
          'reporteria_dealer_portal' => null,
        ];
      }
    }

    // Vehículos sin sede asignada (asesor sin asignación de shop en el período)
    $sinSedeVehicles = $vehicles->filter(fn($v) => is_null($v->advisor_sede_id));
    $sinSedeCompras = $purchaseOrders->filter(fn($p) => is_null($p->shop_id))->count();
    $sinSedeEntregas = $sinSedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $sinSedeFacturadas = $sinSedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    if ($sinSedeFacturadas > 0 || $sinSedeEntregas > 0 || $sinSedeCompras > 0) {
      $items[] = [
        'name' => 'Sin Sede',
        'level' => 'sede',
        'compras' => $sinSedeCompras,
        'entregas' => $sinSedeEntregas,
        'facturadas' => $sinSedeFacturadas,
        'reporteria_dealer_portal' => null,
      ];
    }

    return [
      'title' => strtoupper($groupName),
      'total_compras' => $totalCompras,
      'total_entregas' => $totalEntregas,
      'total_facturadas' => $totalFacturadas,
      'items' => $items,
    ];
  }

  /**
   * Construye sección de camiones
   * Las entregas y facturaciones son independientes
   */
  protected function buildCamionesSection(int $year, int $month, int $typeClassId, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders): array
  {
    $items = [];

    // Total de camiones
    $totalCompras = $purchaseOrders->count();
    $totalEntregas = $vehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $totalFacturadas = $vehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

    // Obtener el grupo de JAC CAMIONES
    $jacCamionesGroup = ApMasters::where('type', 'GRUPO_MARCAS')
      ->where('description', 'CHINA')
      ->first();

    // Obtener marcas de camiones asignadas por shop
    $brandsByShop = [];
    if ($jacCamionesGroup) {
      $brandsByShop = $this->getBrandsByShop($year, $month, $jacCamionesGroup->id, $typeClassId);
    }

    // Por cada shop que tenga marcas de camiones asignadas
    foreach ($allSedes as $sedeId => $sedeName) {
      // Obtener solo las marcas asignadas a asesores en esta sede/shop
      $shopBrands = $brandsByShop[$sedeId] ?? [];

      // Si no hay marcas asignadas en este shop, no mostrar el shop
      if (empty($shopBrands)) {
        continue;
      }

      // Filtrar vehículos y compras de esta sede/shop
      $sedeVehicles = $vehicles->filter(fn($v) => $v->advisor_sede_id == $sedeId);
      $sedePurchases = $purchaseOrders->filter(fn($p) => $p->shop_id == $sedeId);

      // Total por sede
      $sedeCompras = $sedePurchases->count();
      $sedeEntregas = $sedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
      $sedeFacturadas = $sedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

      $items[] = [
        'name' => $sedeName,
        'level' => 'sede',
        'compras' => $sedeCompras,
        'entregas' => $sedeEntregas,
        'facturadas' => $sedeFacturadas,
        'reporteria_dealer_portal' => null,
      ];

      // Mostrar solo las marcas de camiones asignadas (aunque tengan 0)
      foreach ($shopBrands as $brandId => $brandName) {
        $brandVehicles = $sedeVehicles->filter(fn($v) => $v->brand_id == $brandId);
        $brandPurchases = $sedePurchases->filter(fn($p) => $p->brand_id == $brandId);

        $brandCompras = $brandPurchases->count();
        $brandEntregas = $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
        $brandFacturadas = $brandVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();

        $items[] = [
          'name' => $brandName,
          'level' => 'brand',
          'compras' => $brandCompras,
          'entregas' => $brandEntregas,
          'facturadas' => $brandFacturadas,
          'reporteria_dealer_portal' => null,
        ];
      }
    }

    return [
      'title' => 'JAC CAMIONES',
      'total_compras' => $totalCompras,
      'total_entregas' => $totalEntregas,
      'total_facturadas' => $totalFacturadas,
      'items' => $items,
    ];
  }

  /**
   * Construye el reporte de compras por marca y por sede
   * Por Marca: cada marca tiene total_compras y detalle por sede
   * Por Sede: cada sede tiene total_compras y detalle por marca
   */
  protected function buildPurchasesReport(string $fechaInicio, string $fechaFin, int $year, int $month): array
  {
    $purchaseOrders = $this->getPurchaseOrders($fechaInicio, $fechaFin);
    $sedeToShopMap = $this->getSedeToShopMap();
    $allShops = $this->getAllSedesFromAssignments($year, $month);

    $purchaseOrders = $purchaseOrders->map(function ($p) use ($sedeToShopMap) {
      $p->shop_id = $sedeToShopMap[$p->sede_id] ?? null;
      return $p;
    });

    $brandNames = [];
    foreach ($purchaseOrders as $p) {
      if ($p->brand_id && $p->brand_name) {
        $brandNames[$p->brand_id] = $p->brand_name;
      }
    }

    // Por Marca: brand → sedes con conteo
    $byBrand = [];
    foreach ($purchaseOrders->groupBy('brand_id') as $brandId => $brandOrders) {
      if (!$brandId) continue;

      $sedeDetail = [];
      foreach ($allShops as $shopId => $shopName) {
        $shopOrders = $brandOrders->filter(fn($p) => $p->shop_id == $shopId);
        if ($shopOrders->count() > 0) {
          $sedeDetail[] = [
            'sede_id' => $shopId,
            'sede_name' => $shopName,
            'compras' => $shopOrders->count(),
          ];
        }
      }

      $byBrand[] = [
        'brand_id' => $brandId,
        'brand_name' => $brandNames[$brandId] ?? 'Desconocida',
        'total_compras' => $brandOrders->count(),
        'sedes' => $sedeDetail,
      ];
    }

    usort($byBrand, fn($a, $b) => strcmp($a['brand_name'], $b['brand_name']));

    // Por Sede: sede → marcas con conteo
    $bySede = [];
    foreach ($allShops as $shopId => $shopName) {
      $shopOrders = $purchaseOrders->filter(fn($p) => $p->shop_id == $shopId);

      if ($shopOrders->isEmpty()) continue;

      $brandDetail = [];
      foreach ($shopOrders->groupBy('brand_id') as $brandId => $brandOrders) {
        if (!$brandId) continue;
        $brandDetail[] = [
          'brand_id' => $brandId,
          'brand_name' => $brandNames[$brandId] ?? 'Desconocida',
          'compras' => $brandOrders->count(),
        ];
      }

      usort($brandDetail, fn($a, $b) => strcmp($a['brand_name'], $b['brand_name']));

      $bySede[] = [
        'sede_id' => $shopId,
        'sede_name' => $shopName,
        'total_compras' => $shopOrders->count(),
        'brands' => $brandDetail,
      ];
    }

    return [
      'by_brand' => $byBrand,
      'by_sede' => $bySede,
    ];
  }

  /**
   * Obtiene un mapa de sede_id => shop_id
   */
  protected function getSedeToShopMap(): array
  {
    $sedes = DB::table('config_sede')
      ->whereNotNull('shop_id')
      ->select('id', 'shop_id')
      ->get();

    $map = [];
    foreach ($sedes as $sede) {
      $map[$sede->id] = $sede->shop_id;
    }

    return $map;
  }

  /**
   * Obtiene las marcas asignadas a asesores por shop en el período
   * Retorna: [shop_id => [brand_id => brand_name]]
   */
  protected function getBrandsByShop(int $year, int $month, int $brandGroupId, int $typeClassId): array
  {
    $sedeToShopMap = $this->getSedeToShopMap();

    // Obtener asignaciones de marcas a asesores en el período
    $assignments = DB::table('ap_assign_brand_consultant')
      ->join('ap_vehicle_brand', 'ap_assign_brand_consultant.brand_id', '=', 'ap_vehicle_brand.id')
      ->where('ap_assign_brand_consultant.year', $year)
      ->where('ap_assign_brand_consultant.month', $month)
      ->where('ap_vehicle_brand.group_id', $brandGroupId)
      ->where('ap_vehicle_brand.type_class_id', $typeClassId)
      ->where('ap_vehicle_brand.status', 1)
      ->whereNull('ap_vehicle_brand.deleted_at')
      ->whereNull('ap_assign_brand_consultant.deleted_at')
      ->select([
        'ap_assign_brand_consultant.sede_id',
        'ap_vehicle_brand.id as brand_id',
        'ap_vehicle_brand.name as brand_name',
      ])
      ->distinct()
      ->get();

    // Agrupar por shop
    $brandsByShop = [];
    foreach ($assignments as $assignment) {
      $shopId = $sedeToShopMap[$assignment->sede_id] ?? null;
      if ($shopId) {
        if (!isset($brandsByShop[$shopId])) {
          $brandsByShop[$shopId] = [];
        }
        $brandsByShop[$shopId][$assignment->brand_id] = $assignment->brand_name;
      }
    }

    return $brandsByShop;
  }

  /**
   * Obtiene las asignaciones de sedes de los asesores (mapeados a shop)
   */
  protected function getAdvisorSedeAssignments(int $year, int $month): array
  {
    $assignments = DB::table('ap_assign_company_branch_period')
      ->join('config_sede', 'ap_assign_company_branch_period.sede_id', '=', 'config_sede.id')
      ->leftJoin('ap_masters as shop', 'config_sede.shop_id', '=', 'shop.id')
      ->where('ap_assign_company_branch_period.year', $year)
      ->where('ap_assign_company_branch_period.month', $month)
      ->select([
        'ap_assign_company_branch_period.worker_id',
        'shop.id as shop_id',
        'shop.description as shop_name'
      ])
      ->get();

    $map = [];
    foreach ($assignments as $assignment) {
      $map[$assignment->worker_id] = [
        'sede_id' => $assignment->shop_id ?? 0,
        'sede_name' => $assignment->shop_name ?? 'Sin Shop',
      ];
    }

    return $map;
  }

  /**
   * Obtiene todos los shops (sitios) de las sedes del período
   */
  protected function getAllSedesFromAssignments(int $year, int $month): array
  {
    $shops = DB::table('ap_assign_company_branch_period')
      ->join('config_sede', 'ap_assign_company_branch_period.sede_id', '=', 'config_sede.id')
      ->leftJoin('ap_masters as shop', 'config_sede.shop_id', '=', 'shop.id')
      ->where('ap_assign_company_branch_period.year', $year)
      ->where('ap_assign_company_branch_period.month', $month)
      ->whereNotNull('shop.id')
      ->select([
        'shop.id as shop_id',
        'shop.description as shop_name'
      ])
      ->distinct()
      ->get();

    $map = [];
    foreach ($shops as $shop) {
      $map[$shop->shop_id] = $shop->shop_name;
    }

    return $map;
  }

  /**
   * Construye el reporte de Avance por Sede
   * Estructura: Sede > Marcas
   * 3 Secciones de columnas:
   * 1. Objetivo AP Entregas (Sell Out), Resultado Entrega, Cumplimiento (%)
   * 2. Objetivos Reporte Inchcape (sell out), Reporte Dealer Portal, Cumplimiento (%)
   * 3. Objetivos Compra Inchcape (Sell In), Avance de Compra, Cumplimiento (%)
   *
   * @param int $year
   * @param int $month
   * @param Collection $vehicles
   * @param Collection $invoicedQuoteIds
   * @param string $fechaInicio
   * @param string $fechaFin
   * @return array
   */
  protected function buildAvancePorSede(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, string $fechaInicio, string $fechaFin): array
  {
    // Obtener compras del período
    $purchaseOrders = $this->getPurchaseOrders($fechaInicio, $fechaFin);

    // Mapear compras a shops de sus sedes
    $sedeToShopMap = $this->getSedeToShopMap();
    $purchaseOrders = $purchaseOrders->map(function ($p) use ($sedeToShopMap) {
      $p->shop_id = $sedeToShopMap[$p->sede_id] ?? null;
      return $p;
    });

    // Mapear vehículos a shops a través de asignaciones de asesores
    $advisorSedeAssignments = $this->getAdvisorSedeAssignments($year, $month);
    $vehicles = $vehicles->map(function ($v) use ($advisorSedeAssignments) {
      $v->advisor_sede_id = $advisorSedeAssignments[$v->advisor_id]['sede_id'] ?? null;
      $v->advisor_sede_name = $advisorSedeAssignments[$v->advisor_id]['sede_name'] ?? 'Sin Sede';
      return $v;
    });

    // Obtener todos los shops activos
    $allShops = $this->getAllSedesFromAssignments($year, $month);

    // Obtener objetivos sell out y sell in del período
    $goalsOut = $this->getGoalsForPeriod($year, $month, 'OUT');
    $goalsIn = $this->getGoalsForPeriod($year, $month, 'IN');

    $report = [];

    // Por cada shop
    foreach ($allShops as $shopId => $shopName) {
      $shopNode = [
        'sede_id' => $shopId,
        'sede_name' => $shopName,
        'level' => 'sede',
        'brands' => [],
      ];

      // Obtener marcas con objetivos en este shop
      $brandsWithGoals = $this->getBrandsWithGoalsInShop($shopId, $goalsOut, $goalsIn);

      // Por cada marca con objetivos
      foreach ($brandsWithGoals as $brandId => $brandName) {
        // Filtrar vehículos de esta sede y marca
        $brandVehicles = $vehicles->filter(function ($v) use ($shopId, $brandId) {
          return $v->advisor_sede_id == $shopId && $v->brand_id == $brandId;
        });

        // Filtrar compras de esta sede y marca
        $brandPurchases = $purchaseOrders->filter(function ($p) use ($shopId, $brandId) {
          return $p->shop_id == $shopId && $p->brand_id == $brandId;
        });

        // SECCIÓN 1: Sell Out (Entregas)
        $objetivoApEntregas = $goalsOut->where('shop_id', $shopId)->where('brand_id', $brandId)->sum('goal');
        $resultadoEntrega = $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
        $cumplimientoEntrega = $objetivoApEntregas > 0 ? round(($resultadoEntrega / $objetivoApEntregas) * 100, 2) : 0;

        // SECCIÓN 2: Reportes (Inchcape = sell out, Dealer Portal pendiente)
        $objetivosReporteInchcape = $objetivoApEntregas; // Es el mismo sell out
        $reporteDealerPortal = 0; // Pendiente según requerimiento
        $cumplimientoReporte = $objetivosReporteInchcape > 0
          ? round(($reporteDealerPortal / $objetivosReporteInchcape) * 100, 2)
          : 0;

        // SECCIÓN 3: Sell In (Compras)
        $objetivosCompraInchcape = $goalsIn->where('shop_id', $shopId)->where('brand_id', $brandId)->sum('goal');
        $avanceCompra = $brandPurchases->count();
        $cumplimientoCompra = $objetivosCompraInchcape > 0 ? round(($avanceCompra / $objetivosCompraInchcape) * 100, 2) : 0;

        $shopNode['brands'][] = [
          'brand_id' => $brandId,
          'brand_name' => $brandName,
          'level' => 'brand',

          // Sección 1: Entregas (Sell Out)
          'objetivo_ap_entregas' => $objetivoApEntregas,
          'resultado_entrega' => $resultadoEntrega,
          'cumplimiento_entrega' => $cumplimientoEntrega,

          // Sección 2: Reportes
          'objetivos_reporte_inchcape' => $objetivosReporteInchcape,
          'reporte_dealer_portal' => $reporteDealerPortal,
          'cumplimiento_reporte' => $cumplimientoReporte,

          // Sección 3: Compras (Sell In)
          'objetivos_compra_inchcape' => $objetivosCompraInchcape,
          'avance_compra' => $avanceCompra,
          'cumplimiento_compra' => $cumplimientoCompra,
        ];
      }

      // Solo agregar sede si tiene marcas con objetivos
      if (!empty($shopNode['brands'])) {
        $report[] = $shopNode;
      }
    }

    return $report;
  }

  /**
   * Obtiene los objetivos (sell out o sell in) para un período
   *
   * @param int $year
   * @param int $month
   * @param string $type 'OUT' o 'IN'
   * @return Collection
   */
  protected function getGoalsForPeriod(int $year, int $month, string $type): Collection
  {
    return DB::table('ap_goal_sell_out_in')
      ->where('year', $year)
      ->where('month', $month)
      ->where('type', $type)
      ->whereNull('deleted_at')
      ->get();
  }

  /**
   * Obtiene las marcas con objetivos (sell out o sell in) en un shop específico
   *
   * @param int $shopId
   * @param Collection $goalsOut
   * @param Collection $goalsIn
   * @return array [brand_id => brand_name]
   */
  protected function getBrandsWithGoalsInShop(int $shopId, Collection $goalsOut, Collection $goalsIn): array
  {
    // Unir objetivos out e in para este shop
    $allGoals = $goalsOut->where('shop_id', $shopId)
      ->merge($goalsIn->where('shop_id', $shopId));

    // Obtener IDs únicos de marcas
    $brandIds = $allGoals->pluck('brand_id')->unique();

    // Obtener nombres de marcas
    $brands = DB::table('ap_vehicle_brand')
      ->whereIn('id', $brandIds)
      ->where('status', 1)
      ->whereNull('deleted_at')
      ->pluck('name', 'id')
      ->toArray();

    return $brands;
  }
}

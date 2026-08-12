<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
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

    // Paso 7: Inventario actual — se computa antes del brand/purchases report para compartir libre_map
    $currentInventory = $this->buildCurrentInventory();
    $stockLibreMap = $currentInventory['libre_map'];
    unset($currentInventory['libre_map']);

    // Paso 8: Construir reporte por marcas y sedes
    $brandReport = $this->buildBrandReport($year, $month, $vehiclesWithDelivery, $invoicedQuoteIds, $fechaInicio, $fechaFin, $stockLibreMap);

    // Paso 9: Construir reporte de compras por marca y por sede
    $purchasesReport = $this->buildPurchasesReport($fechaInicio, $fechaFin, $year, $month, $vehiclesWithDelivery);

    return [
      'fecha_inicio'      => $fechaInicio,
      'fecha_fin'         => $fechaFin,
      'period'            => [
        'year'  => $year,
        'month' => $month,
      ],
      'summary'           => $summary,
      'advisors'          => $advisors,
      'hierarchy'         => $hierarchy,
      'brand_report'      => $brandReport,
      'purchases_report'  => $purchasesReport,
      'current_inventory' => $currentInventory,
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
      ->leftJoin('purchase_request_quote', function ($join) {
        $join->on('ap_vehicles.id', '=', 'purchase_request_quote.ap_vehicle_id')
          ->whereNull('purchase_request_quote.deleted_at');
      })
      ->leftJoin('ap_opportunity', 'purchase_request_quote.opportunity_id', '=', 'ap_opportunity.id')
      ->join('ap_models_vn', 'ap_vehicles.ap_models_vn_id', '=', 'ap_models_vn.id')
      ->join('ap_class_article', 'ap_models_vn.class_id', '=', 'ap_class_article.id')
      ->leftJoin('ap_families', 'ap_models_vn.family_id', '=', 'ap_families.id')
      ->leftJoin('ap_vehicle_brand', 'ap_families.brand_id', '=', 'ap_vehicle_brand.id')
      ->leftJoin('config_sede', DB::raw('COALESCE(purchase_request_quote.sede_id, ap_vehicle_delivery.sede_id)'), '=', 'config_sede.id')
      ->where(function ($query) use ($fechaInicio, $fechaFin) {
        $fechaFinFull = $fechaFin . ' 23:59:59';
        // Tiene entrega en el rango de fechas
        $query->where(function ($q) use ($fechaInicio, $fechaFinFull) {
          $q->whereBetween('ap_vehicle_delivery.real_delivery_date', [$fechaInicio, $fechaFinFull])
            ->whereNotNull('ap_vehicle_delivery.real_delivery_date');
        })
          // O tiene factura válida en el rango de fechas (independiente de la entrega)
          ->orWhereExists(function ($q) use ($fechaInicio, $fechaFinFull) {
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
              ->whereBetween('ap_billing_electronic_documents.fecha_de_emision', [$fechaInicio, $fechaFinFull])
              ->whereNull('ap_billing_electronic_documents.deleted_at');
          });
      })
      ->whereNull('ap_vehicles.deleted_at')
      ->select([
        'ap_vehicles.id as vehicle_id',
        'ap_vehicle_delivery.real_delivery_date',
        DB::raw('COALESCE(ap_opportunity.worker_id, ap_vehicle_delivery.advisor_id) as advisor_id'),
        DB::raw('COALESCE(purchase_request_quote.sede_id, ap_vehicle_delivery.sede_id) as sede_id'),
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
      ->whereBetween('ap_purchase_order.emission_date', [$fechaInicio, $fechaFin . ' 23:59:59'])
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
   * Obtiene IDs de cotizaciones con facturas emitidas dentro del periodo
   */
  protected function getInvoicedQuoteIds(string $fechaInicio, string $fechaFin): Collection
  {
    return ElectronicDocument::where('is_advance_payment', false)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereIn('sunat_concept_document_type_id', [
        SunatConcepts::ID_FACTURA_ELECTRONICA,
        SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA,
      ])
      ->where('area_id', ApMasters::AREA_COMERCIAL)
      ->whereNotNull('purchase_request_quote_id')
      ->whereBetween('fecha_de_emision', [$fechaInicio, $fechaFin . ' 23:59:59'])
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

    $tradData = $calc($tradicionales);
    $chinaData = $calc($chinas);
    $camionData = $calc($camiones);

    return [
      'TRADICIONALES' => $tradData,
      'CHINAS'        => $chinaData,
      'CAMIONES'      => $camionData,
      'TOTAL'         => [
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
        'id'                       => $advisorId,
        'name'                     => $advisor ? $advisor->nombre_completo : 'Desconocido',
        'entregas'                 => $entregas,
        'facturadas'               => $facturacion,
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
        $sinAsesorEntregas += $totals['entregas'];
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
        'id'                       => $managerId,
        'name'                     => $manager->nombre_completo,
        'level'                    => 'gerente',
        'brand_group'              => $managerAssignment->brandGroup?->description ?? 'Sin grupo',
        'article_class'            => $className,
        'entregas'                 => 0,
        'facturadas'               => 0,
        'reporteria_dealer_portal' => null,
        'children'                 => [],
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
        'id'                       => $bossId,
        'name'                     => $boss->nombre_completo,
        'level'                    => 'jefe',
        'article_class'            => $className,
        'entregas'                 => 0,
        'facturadas'               => 0,
        'reporteria_dealer_portal' => null,
        'children'                 => [],
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
            'id'                       => $workerId,
            'name'                     => $worker->nombre_completo,
            'level'                    => 'asesor',
            'entregas'                 => $workerEntregas,
            'facturadas'               => $workerFacturadas,
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
      'id'                       => $jefeId,
      'name'                     => $jefe->nombre_completo,
      'level'                    => 'jefe',
      'entregas'                 => 0,
      'facturadas'               => 0,
      'reporteria_dealer_portal' => null,
      'children'                 => [],
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
          'id'                       => $workerId,
          'name'                     => $asesor->nombre_completo,
          'level'                    => 'asesor',
          'entregas'                 => $asesorEntregas,
          'facturadas'               => $asesorFacturacion,
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
      'id'                       => $managerId,
      'name'                     => $manager->nombre_completo,
      'level'                    => 'gerente',
      'brand_group'              => $brandGroupNames ?: 'Sin grupo',
      'article_class'            => $className,
      'entregas'                 => 0,
      'facturadas'               => 0,
      'reporteria_dealer_portal' => null,
      'children'                 => [],
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
          'id'                       => null,
          'name'                     => 'Sin asesor',
          'level'                    => 'sin_asesor',
          'entregas'                 => $orphanEntregas,
          'facturadas'               => $orphanFacturadas,
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
      'id'                       => $managerId,
      'name'                     => $manager->nombre_completo,
      'level'                    => 'gerente',
      'brand_group'              => $managerAssignment->brandGroup?->description ?? 'Sin grupo',
      'article_class'            => $className,
      'entregas'                 => 0,
      'facturadas'               => 0,
      'reporteria_dealer_portal' => null,
      'children'                 => [],
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
      'id'                       => $jefeId,
      'name'                     => $jefe->nombre_completo,
      'level'                    => 'jefe',
      'entregas'                 => 0,
      'facturadas'               => 0,
      'reporteria_dealer_portal' => null,
      'children'                 => [],
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
          'id'                       => $workerId,
          'name'                     => $asesor->nombre_completo,
          'level'                    => 'asesor',
          'brands'                   => !empty($workerBrands) ? $workerBrands : null,
          'entregas'                 => $asesorEntregas,
          'facturadas'               => $asesorFacturacion,
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
      'id'                       => $jefeId,
      'name'                     => $jefe->nombre_completo,
      'level'                    => 'jefe',
      'entregas'                 => 0,
      'facturadas'               => 0,
      'reporteria_dealer_portal' => null,
      'children'                 => [],
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
          'id'                       => $workerId,
          'name'                     => $asesor->nombre_completo,
          'level'                    => 'asesor',
          'entregas'                 => $asesorEntregas,
          'facturadas'               => $asesorFacturacion,
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
        'id'                       => $bossId,
        'name'                     => $boss->nombre_completo,
        'level'                    => 'jefe',
        'article_class'            => 'CAMIONES',
        'entregas'                 => 0,
        'facturadas'               => 0,
        'reporteria_dealer_portal' => null,
        'children'                 => [],
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
            'id'                       => $workerId,
            'name'                     => $worker->nombre_completo,
            'level'                    => 'asesor',
            'brands'                   => !empty($workerBrands) ? $workerBrands : null,
            'entregas'                 => $workerEntregas,
            'facturadas'               => $workerFacturadas,
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
      'id'                       => $jefeId,
      'name'                     => $jefe->nombre_completo,
      'level'                    => 'jefe',
      'entregas'                 => 0,
      'facturadas'               => 0,
      'reporteria_dealer_portal' => null,
      'children'                 => [],
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
        'id'                       => $workerId,
        'name'                     => $worker->nombre_completo,
        'level'                    => 'asesor',
        'brands'                   => !empty($workerBrands) ? $workerBrands : null,
        'entregas'                 => $workerEntregas,
        'facturadas'               => $workerFacturadas,
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
  protected function buildBrandReport(int $year, int $month, Collection $vehicles, Collection $invoicedQuoteIds, string $fechaInicio, string $fechaFin, array $stockLibreMap = []): array
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

    // Mapear cada vehículo a la sede de su asesor; si el asesor no tiene asignación, usar la sede de la solicitud
    $vehicles = $vehicles->map(function ($v) use ($advisorSedeAssignments, $sedeToShopMap) {
      $v->advisor_sede_id = $advisorSedeAssignments[$v->advisor_id]['sede_id'] ?? ($sedeToShopMap[$v->sede_id] ?? null);
      $v->advisor_sede_name = $advisorSedeAssignments[$v->advisor_id]['sede_name'] ?? ($v->sede_name ?? 'Sin Sede');
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
    $brandGroupSections = $this->buildBrandGroupSections($year, $month, $vehicleTypeId, $livianos, $invoicedQuoteIds, $allSedes, $comprasLivianos, $stockLibreMap);
    foreach ($brandGroupSections as $section) {
      $report[] = $section;
    }

    // Reporte de camiones
    $report[] = $this->buildCamionesSection($year, $month, $camionTypeId, $camiones, $invoicedQuoteIds, $allSedes, $comprasCamiones, $stockLibreMap);

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
          'name'                     => 'TOTAL AP LIVIANOS',
          'compras'                  => $livianosCompras,
          'entregas'                 => $livianosEntregas,
          'facturadas'               => $livianosFacturadas,
          'reporteria_dealer_portal' => null,
        ],
        [
          'name'                     => 'TOTAL AP CAMIONES',
          'compras'                  => $camionesCompras,
          'entregas'                 => $camionesEntregas,
          'facturadas'               => $camionesFacturadas,
          'reporteria_dealer_portal' => null,
        ],
        [
          'name'                     => 'TOTAL AP',
          'compras'                  => $livianosCompras + $camionesCompras,
          'entregas'                 => $livianosEntregas + $camionesEntregas,
          'facturadas'               => $livianosFacturadas + $camionesFacturadas,
          'reporteria_dealer_portal' => null,
        ],
      ],
    ];
  }

  /**
   * Construye secciones por grupo de marcas.
   * Orden: TRADICIONALES (TRADICIONAL + INCHCAPE fusionados), CHINA
   */
  protected function buildBrandGroupSections(int $year, int $month, int $typeClassId, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders, array $stockLibreMap = []): array
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
      $sections[] = $this->buildBrandGroupSectionForGroups('TRADICIONALES', $traditionalesVehicles, $invoicedQuoteIds, $allSedes, $traditionalesPurchases, $brandsByShopTradicionales, $stockLibreMap);
    }

    // SECCIÓN 2: CHINA
    $chinaGroup = ApMasters::where('type', 'GRUPO_MARCAS')->where('description', 'CHINA')->first();
    if ($chinaGroup) {
      $chinaVehicles = $vehicles->where('brand_group_id', $chinaGroup->id);
      $chinaPurchases = $purchaseOrders->where('brand_group_id', $chinaGroup->id);
      $brandsByShopChina = $this->getBrandsByShop($year, $month, $chinaGroup->id, $typeClassId);
      $sections[] = $this->buildBrandGroupSection($chinaGroup, $chinaVehicles, $invoicedQuoteIds, $allSedes, $chinaPurchases, $brandsByShopChina, $stockLibreMap);
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
  protected function buildBrandGroupSectionForGroups(string $title, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders, array $brandsByShop, array $stockLibreMap = []): array
  {
    $items = [];

    $totalCompras = $purchaseOrders->count();
    $totalEntregas = $vehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $totalFacturadas = $vehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();
    $totalLibre = 0;

    foreach ($allSedes as $sedeId => $sedeName) {
      $shopBrands = $brandsByShop[$sedeId] ?? [];

      if (empty($shopBrands)) {
        continue;
      }

      $sedeVehicles = $vehicles->filter(fn($v) => $v->advisor_sede_id == $sedeId);
      $sedePurchases = $purchaseOrders->filter(fn($p) => $p->shop_id == $sedeId);
      $sedeLibre = array_sum(array_intersect_key($stockLibreMap[$sedeId] ?? [], $shopBrands));
      $totalLibre += $sedeLibre;

      $items[] = [
        'name'                     => $sedeName,
        'level'                    => 'sede',
        'compras'                  => $sedePurchases->count(),
        'entregas'                 => $sedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
        'facturadas'               => $sedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
        'stock_libre'              => $sedeLibre,
        'reporteria_dealer_portal' => null,
      ];

      foreach ($shopBrands as $brandId => $brandName) {
        $brandVehicles = $sedeVehicles->filter(fn($v) => $v->brand_id == $brandId);
        $brandPurchases = $sedePurchases->filter(fn($p) => $p->brand_id == $brandId);

        $items[] = [
          'name'                     => $brandName,
          'level'                    => 'brand',
          'compras'                  => $brandPurchases->count(),
          'entregas'                 => $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
          'facturadas'               => $brandVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
          'stock_libre'              => $stockLibreMap[$sedeId][$brandId] ?? 0,
          'reporteria_dealer_portal' => null,
        ];
      }
    }

    return [
      'title'            => $title,
      'total_compras'    => $totalCompras,
      'total_entregas'   => $totalEntregas,
      'total_facturadas' => $totalFacturadas,
      'total_libre'      => $totalLibre,
      'items'            => $items,
    ];
  }

  /**
   * Construye una sección de grupo de marcas con sus sedes y marcas
   * Las entregas y facturaciones son independientes
   */
  protected function buildBrandGroupSection($brandGroup, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders, array $brandsByShop, array $stockLibreMap = []): array
  {
    $groupName = $brandGroup->description;

    $items = [];

    $totalCompras = $purchaseOrders->count();
    $totalEntregas = $vehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $totalFacturadas = $vehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();
    $totalLibre = 0;

    foreach ($allSedes as $sedeId => $sedeName) {
      $shopBrands = $brandsByShop[$sedeId] ?? [];

      if (empty($shopBrands)) {
        continue;
      }

      $sedeVehicles = $vehicles->filter(fn($v) => $v->advisor_sede_id == $sedeId);
      $sedePurchases = $purchaseOrders->filter(fn($p) => $p->shop_id == $sedeId);
      $sedeLibre = array_sum(array_intersect_key($stockLibreMap[$sedeId] ?? [], $shopBrands));
      $totalLibre += $sedeLibre;

      $items[] = [
        'name'                     => $sedeName,
        'level'                    => 'sede',
        'compras'                  => $sedePurchases->count(),
        'entregas'                 => $sedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
        'facturadas'               => $sedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
        'stock_libre'              => $sedeLibre,
        'reporteria_dealer_portal' => null,
      ];

      foreach ($shopBrands as $brandId => $brandName) {
        $brandVehicles = $sedeVehicles->filter(fn($v) => $v->brand_id == $brandId);
        $brandPurchases = $sedePurchases->filter(fn($p) => $p->brand_id == $brandId);

        $items[] = [
          'name'                     => $brandName,
          'level'                    => 'brand',
          'compras'                  => $brandPurchases->count(),
          'entregas'                 => $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
          'facturadas'               => $brandVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
          'stock_libre'              => $stockLibreMap[$sedeId][$brandId] ?? 0,
          'reporteria_dealer_portal' => null,
        ];
      }
    }

    return [
      'title'            => strtoupper($groupName),
      'total_compras'    => $totalCompras,
      'total_entregas'   => $totalEntregas,
      'total_facturadas' => $totalFacturadas,
      'total_libre'      => $totalLibre,
      'items'            => $items,
    ];
  }

  /**
   * Construye sección de camiones
   * Las entregas y facturaciones son independientes
   */
  protected function buildCamionesSection(int $year, int $month, int $typeClassId, Collection $vehicles, Collection $invoicedQuoteIds, array $allSedes, Collection $purchaseOrders, array $stockLibreMap = []): array
  {
    $items = [];

    $totalCompras = $purchaseOrders->count();
    $totalEntregas = $vehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count();
    $totalFacturadas = $vehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count();
    $totalLibre = 0;

    $jacCamionesGroup = ApMasters::where('type', 'GRUPO_MARCAS')
      ->where('description', 'CHINA')
      ->first();

    $brandsByShop = [];
    if ($jacCamionesGroup) {
      $brandsByShop = $this->getBrandsByShop($year, $month, $jacCamionesGroup->id, $typeClassId);
    }

    foreach ($allSedes as $sedeId => $sedeName) {
      $shopBrands = $brandsByShop[$sedeId] ?? [];

      if (empty($shopBrands)) {
        continue;
      }

      $sedeVehicles = $vehicles->filter(fn($v) => $v->advisor_sede_id == $sedeId);
      $sedePurchases = $purchaseOrders->filter(fn($p) => $p->shop_id == $sedeId);
      $sedeLibre = array_sum(array_intersect_key($stockLibreMap[$sedeId] ?? [], $shopBrands));
      $totalLibre += $sedeLibre;

      $items[] = [
        'name'                     => $sedeName,
        'level'                    => 'sede',
        'compras'                  => $sedePurchases->count(),
        'entregas'                 => $sedeVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
        'facturadas'               => $sedeVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
        'stock_libre'              => $sedeLibre,
        'reporteria_dealer_portal' => null,
      ];

      foreach ($shopBrands as $brandId => $brandName) {
        $brandVehicles = $sedeVehicles->filter(fn($v) => $v->brand_id == $brandId);
        $brandPurchases = $sedePurchases->filter(fn($p) => $p->brand_id == $brandId);

        $items[] = [
          'name'                     => $brandName,
          'level'                    => 'brand',
          'compras'                  => $brandPurchases->count(),
          'entregas'                 => $brandVehicles->filter(fn($v) => !is_null($v->real_delivery_date))->count(),
          'facturadas'               => $brandVehicles->filter(fn($v) => $invoicedQuoteIds->contains($v->quote_id))->count(),
          'stock_libre'              => $stockLibreMap[$sedeId][$brandId] ?? 0,
          'reporteria_dealer_portal' => null,
        ];
      }
    }

    return [
      'title'            => 'JAC CAMIONES',
      'total_compras'    => $totalCompras,
      'total_entregas'   => $totalEntregas,
      'total_facturadas' => $totalFacturadas,
      'total_libre'      => $totalLibre,
      'items'            => $items,
    ];
  }

  /**
   * Construye el reporte de compras por marca y por sede
   * Por Marca: cada marca tiene total_compras y detalle por sede
   * Por Sede: cada sede tiene total_compras y detalle por marca
   */
  protected function buildPurchasesReport(string $fechaInicio, string $fechaFin, int $year, int $month, Collection $deliveredVehicles = null): array
  {
    $purchaseOrders = $this->getPurchaseOrders($fechaInicio, $fechaFin);
    $sedeToShopMap = $this->getSedeToShopMap();
    $allShops = $this->getAllSedesFromAssignments($year, $month);
    $advisorSedeAssignments = $this->getAdvisorSedeAssignments($year, $month);

    $purchaseOrders = $purchaseOrders->map(function ($p) use ($sedeToShopMap) {
      $p->shop_id = $sedeToShopMap[$p->sede_id] ?? null;
      return $p;
    });

    // Mapear vehículos vendidos a shops via asesor
    $deliveredVehicles = ($deliveredVehicles ?? collect())->map(function ($v) use ($advisorSedeAssignments, $sedeToShopMap) {
      $v->advisor_shop_id = $advisorSedeAssignments[$v->advisor_id]['sede_id'] ?? ($sedeToShopMap[$v->sede_id] ?? null);
      return $v;
    });
    $deliveriesOnly = $deliveredVehicles->filter(fn($v) => !is_null($v->real_delivery_date));

    $goalsIn = $this->getGoalsForPeriod($year, $month, 'IN');
    $goalsOut = $this->getGoalsForPeriod($year, $month, 'OUT');

    $brandNames = [];
    foreach ($purchaseOrders as $p) {
      if ($p->brand_id && $p->brand_name) {
        $brandNames[$p->brand_id] = $p->brand_name;
      }
    }
    foreach ($deliveredVehicles as $v) {
      if ($v->brand_id && $v->brand_name && !isset($brandNames[$v->brand_id])) {
        $brandNames[$v->brand_id] = $v->brand_name;
      }
    }

    // Todos los brand_id con algún dato (compras, ventas u objetivos)
    $allBrandIds = collect($purchaseOrders)->pluck('brand_id')
      ->merge($deliveredVehicles->pluck('brand_id'))
      ->merge($goalsIn->pluck('brand_id'))
      ->merge($goalsOut->pluck('brand_id'))
      ->filter()
      ->unique();

    // Por Marca: brand → sedes con conteo
    $byBrand = [];
    foreach ($allBrandIds as $brandId) {
      $brandOrders = $purchaseOrders->filter(fn($p) => $p->brand_id == $brandId);
      $brandDeliveries = $deliveriesOnly->filter(fn($v) => $v->brand_id == $brandId);
      $brandSellIn = $goalsIn->where('brand_id', $brandId)->sum('goal');
      $brandSellOut = $goalsOut->where('brand_id', $brandId)->sum('goal');

      $sedeDetail = [];
      foreach ($allShops as $shopId => $shopName) {
        $shopOrders = $brandOrders->filter(fn($p) => $p->shop_id == $shopId);
        $shopDeliveries = $brandDeliveries->filter(fn($v) => $v->advisor_shop_id == $shopId);
        $shopSellIn = $goalsIn->where('brand_id', $brandId)->where('shop_id', $shopId)->sum('goal');
        $shopSellOut = $goalsOut->where('brand_id', $brandId)->where('shop_id', $shopId)->sum('goal');

        if ($shopOrders->count() || $shopDeliveries->count() || $shopSellIn || $shopSellOut) {
          $sedeDetail[] = [
            'sede_id'           => $shopId,
            'sede_name'         => $shopName,
            'compras'           => $shopOrders->count(),
            'ventas'            => $shopDeliveries->count(),
            'objetivo_sell_in'  => $shopSellIn,
            'objetivo_sell_out' => $shopSellOut,
          ];
        }
      }

      if ($brandOrders->count() || $brandDeliveries->count() || $brandSellIn || $brandSellOut) {
        $byBrand[] = [
          'brand_id'          => $brandId,
          'brand_name'        => $brandNames[$brandId] ?? 'Desconocida',
          'total_compras'     => $brandOrders->count(),
          'total_ventas'      => $brandDeliveries->count(),
          'objetivo_sell_in'  => $brandSellIn,
          'objetivo_sell_out' => $brandSellOut,
          'sedes'             => $sedeDetail,
        ];
      }
    }

    usort($byBrand, fn($a, $b) => strcmp($a['brand_name'], $b['brand_name']));

    // Por Sede: sede → marcas con conteo
    $bySede = [];
    foreach ($allShops as $shopId => $shopName) {
      $shopOrders = $purchaseOrders->filter(fn($p) => $p->shop_id == $shopId);
      $shopDeliveries = $deliveriesOnly->filter(fn($v) => $v->advisor_shop_id == $shopId);
      $shopSellIn = $goalsIn->where('shop_id', $shopId)->sum('goal');
      $shopSellOut = $goalsOut->where('shop_id', $shopId)->sum('goal');

      if ($shopOrders->isEmpty() && $shopDeliveries->isEmpty() && !$shopSellIn && !$shopSellOut) continue;

      $shopBrandIds = collect($shopOrders)->pluck('brand_id')
        ->merge($shopDeliveries->pluck('brand_id'))
        ->merge($goalsIn->where('shop_id', $shopId)->pluck('brand_id'))
        ->merge($goalsOut->where('shop_id', $shopId)->pluck('brand_id'))
        ->filter()
        ->unique();

      $brandDetail = [];
      foreach ($shopBrandIds as $brandId) {
        $brandOrders = $shopOrders->filter(fn($p) => $p->brand_id == $brandId);
        $brandDeliveries = $shopDeliveries->filter(fn($v) => $v->brand_id == $brandId);
        $brandSellIn = $goalsIn->where('shop_id', $shopId)->where('brand_id', $brandId)->sum('goal');
        $brandSellOut = $goalsOut->where('shop_id', $shopId)->where('brand_id', $brandId)->sum('goal');

        $brandDetail[] = [
          'brand_id'          => $brandId,
          'brand_name'        => $brandNames[$brandId] ?? 'Desconocida',
          'compras'           => $brandOrders->count(),
          'ventas'            => $brandDeliveries->count(),
          'objetivo_sell_in'  => $brandSellIn,
          'objetivo_sell_out' => $brandSellOut,
        ];
      }

      usort($brandDetail, fn($a, $b) => strcmp($a['brand_name'], $b['brand_name']));

      $bySede[] = [
        'sede_id'           => $shopId,
        'sede_name'         => $shopName,
        'total_compras'     => $shopOrders->count(),
        'total_ventas'      => $shopDeliveries->count(),
        'objetivo_sell_in'  => $shopSellIn,
        'objetivo_sell_out' => $shopSellOut,
        'brands'            => $brandDetail,
      ];
    }

    return [
      'by_brand' => $byBrand,
      'by_sede'  => $bySede,
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
        'sede_id'   => $assignment->shop_id ?? 0,
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
        'sede_id'   => $shopId,
        'sede_name' => $shopName,
        'level'     => 'sede',
        'brands'    => [],
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
          'brand_id'                   => $brandId,
          'brand_name'                 => $brandName,
          'level'                      => 'brand',

          // Sección 1: Entregas (Sell Out)
          'objetivo_ap_entregas'       => $objetivoApEntregas,
          'resultado_entrega'          => $resultadoEntrega,
          'cumplimiento_entrega'       => $cumplimientoEntrega,

          // Sección 2: Reportes
          'objetivos_reporte_inchcape' => $objetivosReporteInchcape,
          'reporte_dealer_portal'      => $reporteDealerPortal,
          'cumplimiento_reporte'       => $cumplimientoReporte,

          // Sección 3: Compras (Sell In)
          'objetivos_compra_inchcape'  => $objetivosCompraInchcape,
          'avance_compra'              => $avanceCompra,
          'cumplimiento_compra'        => $cumplimientoCompra,
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

  // VINs del stock inicial que ya estaban facturados pero sin movimiento registrado.
  // Temporal hasta que se regularice o se encuentre una mejor forma de manejarlos.
  protected const VINS_FACTURADOS_STOCK_INICIAL = [
    'LS4ASE2E8TA946156',
    'LJ166A3D6V2240304',
    'LJ11PABD4VC000204',
    'LS4ASE2E4VA993512',
    'MP2TFS40JVT600317',
    'LGWFF7A56TJ641378',
    'LS4ASL2E4VG800910',
    'LS5A3DKE5VA941891',
    'LS4ASL2E2VG800288',
    'LGWCB4178TB656358',
    'LS5A3DKE3VA940383',
    'LS5A3DKE0VA940602',
    'LJ11PABD1TC014722',
    'LS5A3DBE1VD800248',
    'LGWCB4175TB656463',
    'LJ166B3D2V2250138',
    'LS5A3ABE2VD910322',
    'LS5A3DSE0VD910305',
    'LJ11PABD4VC004883',
    'LS4ASE2E6VA990997',
    'LJ11PABD7TC014711',
    'LGWDCF199VJ604566',
    'LJ12EKR27V4000695',
    'LGWDCF192VJ614906',
    'LJ11PABD2TC014700',
    'LJ166A337V2240268',
    'LS4ASE2E5VA992708',
    'LS4ASE2E7VA992998',
    'LS4ASC2E4VG800535',
    'LSCBBZ2G8VG801233',
    'LS5A3DKE1VA942326',
    'LS4ASC2E8VG800599',
    'LS4ASE2E0TA998137',
    'LS4ASE2E0VA993457',
    'LS4ASE2E4VA993509',
    'LS4ASE2E5VA993504',
    'LGWCB4179VB601100',
    'LJ166A333V2240414',
    'LJ11PABDXVC006993',
    'LJ11PABD2VC006938',
    'LJ11PABD9VC006497',
    'LS4ASL2E8VG801834',
    'LS4AAB3R5VG801000',
    'LJ11KRBD9V1900962',
    'LJ11PABDXTC014816',
    'LS4ASC2E5VG800365',
    '93Y9SR333TJ470455',
    'JM7KF2W7AV0165531',
    'MA3YPLCS6VK162866',
    'MA3JC74W3V0308111',
    'MBHWDB3S3TG582110',
    'MBHZCEES5VG537994',
    'LVZA53P92VCB00383',
    'LVZA53P9XVCB00468',
    'JF1SL46M4TG035272',
    'MHYDN71V0VJ400076',
    'MA3ZFEFS1VA413930',
    'MA3ZFEFS5VA414191',
    'MHYDN71V3VJ400458',
    'MA3JC74W3V0301384',
    'MA3YPLCS4VK167242',
    'LVZA53P90VAA00939',
    'LVZA53P98VAA02194',
    'LVZA53P9XVAA02133',
    'LVZA53P9XVAA02147',
    'LVZA53P99VAA02074',
    'JF1SL46M6TG036083',
    'JF1SL48M2TG036692',
    'LS5A3ABE1VD800510',
    'LS4ASE2E7VA992130',
    'LS5A2DBE3VA960426',
    'LS5A3DSE3VD910332',
    'LS5A3DBE2VD910158',
    'LS5A3ABE9VD910530',
    'LS5A3DKE3VA941839',
    'LS4ASE2E6VB082348',
    'LS4ASE2E5VA993518',
    'LS4AAB3R4VG800985',
    'LS4ASE2E4VA993459',
    'LS5A3DBE5VD800253',
    'LS5A2DBE0VA960464',
    'LS4ASE2E2VB081519',
    'LS5A3DBEXVD910313',
    'LS4ASE2E2VA993556',
    'LS4ASE2E3VA993565',
    'LS4ASE2E3VB083098',
    'LS5A3DBE3VD910119',
    'LS4ASL2E8VG801879',
    'LS5A3DBE3VD910346',
    'LS5A3DBE3VD910167',
    'LGWDCF193VJ606717',
    'LGWDCF197VJ614447',
    'LGWDCF197VJ614710',
    'LGWDCF19XVJ613907',
    'LGWDCF198VJ614716',
    'LGWDCF191VJ614203',
    'LGWDCF198VJ612626',
    'LJ12EKR21V4004516',
    'LJ11PABE3VC000378',
    'LJ11PABE1VC000380',
    'LJ166B3D2V2250091',
    'LJ166A259V4000923',
    'LJ12EKS37V4700574',
    'LJ12EKR28V4001631',
    'LJ11KAAC2V1300438',
    'LJ11PABD0VC003729',
    'LJ166B3D2V2250303',
    'LJ166A331V2240363',
    'LJ166A3D0V2240301',
    'LJ166A3D6V2240321',
    'LJ12EKR27V4003077',
    'LJ11PABD0VC006937',
    'LJ11PABE0VC003075',
    'LJ166A3D9V2240412',
    'LJ166B3D2V2250401',
    'LJ11PABD2VC005630',
    'LJ11PABD4VC006939',
    'LJ12EKS2XV4701614',
    'LJ11PABD5VC003564',
    'LJ11PABE9VC002314',
    'LJ11PABE0VC002332',
    'LJ11PABD4VC006942',
    'LJ11R9EH5T3500148',
    'LJ11KDBD4V1900967',
    'LJ11KDBD4V1701269',
    'LJ11KDAD8V1300700',
    'LJ11R9DE6V3000218',
    'MP2TFS40JVT500007',
    'JM7KF2W7AV0164572',
    'JM7KF2W7AT0157468',
    'MP2TFS40JVT600302',
    '93YRBB000VJ629000',
    'MBHZCEES0TG440957',
    'MA3YPLCS6VK171857',
    'MBHZCEES0VG529348',
    'MA3YPLCS2VK175338',
    'MA3ZFEFS4VA413257',
    'MA3ZFEFS6VA413163',
    'LS4ASL2EXVG800295',
    'LJ12EKR2XV4001775',
    'LS4ASL2E9VG800384',
    'LS4ASC2E6VG800598',
    'LGWDCF196VJ612897',
    'LGWDCF198VJ612707',
    'LJ11PABD4VC004897',
    'MA3FL61SXVA592638',
    'LJ11PABD2VC003019',
    'LS4ASE2E7VA993570',
    'MA3ZFEFS0VA414101',
    'MBHWDB3S8VG821170',
    'LJ11PABD6VC006487',
    'LS5A2DBE8TA960032',
    'MBHWDB3S6TG464505',
    'LGWDCF197VJ606784',
    'LS4ASE2E4VA992876',
    'LGWDCF196VJ612706',
    'MA3JC74W5T0229407',
    'LVZZ42F90TAA03242',
    'LS4ASL2E6VG800293',
    'MA3JC74W3V0311316',
    'LJ11KDAD6V1300727',
    'LJ11KDAD8V1300728',
    '93YJ62S07VJ563317',
    'MA3YPLCS0VK182594',
  ];

  protected function buildCurrentInventory(): array
  {
    $vehicles = Vehicles::with([
      'model.family.brand',
      'model.fuelType',
      'color',
      'vehicleStatus',
      'warehouse.sede',
      'purchaseOrder',
      'purchaseRequestQuote.opportunity.worker',
      'purchaseRequestQuote.holder',
      'vehicleMovements',
    ])
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->whereNotIn('ap_vehicle_status_id', [ApVehicleStatus::VENDIDO_ENTREGADO])
      ->get();

    $vinsFacturadosIniciales = collect(self::VINS_FACTURADOS_STOCK_INICIAL);
    $sedeToShopMap = $this->getSedeToShopMap();

    $items = $vehicles->map(function ($vehicle) use ($vinsFacturadosIniciales) {
      $po = $vehicle->purchaseOrder;
      $quote = $vehicle->purchaseRequestQuote;
      $emissionDate = $po?->emission_date;

      $invoiceNumber = null;
      if ($po?->invoice_series || $po?->invoice_number) {
        $invoiceNumber = trim(($po->invoice_series ?? '') . '-' . ($po->invoice_number ?? ''), '-');
      }

      $isReceived = (bool)($vehicle->warehouse?->is_received ?? false);
      $tieneMovFacturadoFinal = $vehicle->vehicleMovements
        ->contains('ap_vehicle_status_id', ApVehicleStatus::FACTURADO_FINAL);
      $enArrayInicial = $vinsFacturadosIniciales->contains($vehicle->vin);
      $esFacturado = $tieneMovFacturadoFinal || $enArrayInicial;
      $tieneQuote = $quote !== null;

      if (!$isReceived && $esFacturado) {
        $estadoCalculado = 'TRAVESIA FACTURADO';
      } elseif (!$isReceived && !$tieneQuote && !$enArrayInicial) {
        $estadoCalculado = 'TRAVESIA LIBRE';
      } elseif ($isReceived && $esFacturado) {
        $estadoCalculado = 'PISO FACTURADO';
      } elseif ($isReceived && !$tieneQuote && !$enArrayInicial) {
        $estadoCalculado = 'PISO LIBRE';
      } else {
        $estadoCalculado = 'SIN CLASIFICAR';
      }

      return [
        'estado'          => $estadoCalculado,
        'estado_real'     => $vehicle->vehicleStatus?->description,
        'fecha_emision'   => $emissionDate?->format('d/m/Y'),
        'importe_inicial' => $po?->total,
        'numero_factura'  => $invoiceNumber,
        'marca'           => $vehicle->model?->family?->brand?->name,
        'modelo'          => $vehicle->model?->version,
        'color'           => $vehicle->color?->description,
        'anio_modelo'     => $vehicle->model?->model_year,
        'combustible'     => $vehicle->model?->fuelType?->description,
        'vin'             => $vehicle->vin,
        'serie_motor'     => $vehicle->engine_number,
        'sede'            => $vehicle->warehouse?->sede?->abreviatura,
        'almacen'         => $vehicle->warehouse?->description,
        'dias_en_stock'   => $emissionDate ? (int)$emissionDate->diffInDays(now()) : null,
        'solicitud_id'    => $quote?->id,
        'solicitud'       => $quote ? 'COT-' . $quote->correlative : null,
        'cliente'         => $quote?->holder?->full_name,
        'asesor'          => $quote?->opportunity?->worker?->nombre_completo,
      ];
    })->values()->all();

    $estadosConfig = [
      'TRAVESIA FACTURADO' => '#F97316',
      'TRAVESIA LIBRE'     => '#3B82F6',
      'PISO FACTURADO'     => '#10B981',
      'PISO LIBRE'         => '#8B5CF6',
      'SIN CLASIFICAR'     => '#6B7280',
    ];

    $grouped = collect($items)->groupBy('estado');

    $summary = collect($estadosConfig)
      ->filter(fn($color, $estado) => $grouped->has($estado))
      ->map(fn($color, $estado) => [
        'estado' => $estado,
        'total'  => $grouped[$estado]->count(),
        'color'  => $color,
      ])
      ->values()
      ->all();

    $summary[] = [
      'estado' => 'TOTAL',
      'total'  => count($items),
      'color'  => null,
    ];

    // Construir mapa de stock libre para compartir con brand_report
    $libreMap = [];
    foreach ($vehicles as $vehicle) {
      $enArrayInicial = $vinsFacturadosIniciales->contains($vehicle->vin);
      $tieneMovFacturadoFinal = $vehicle->vehicleMovements
        ->contains('ap_vehicle_status_id', ApVehicleStatus::FACTURADO_FINAL);
      $esLibre = !$enArrayInicial && !$tieneMovFacturadoFinal && $vehicle->purchaseRequestQuote === null;

      if ($esLibre) {
        $brandId = $vehicle->model?->family?->brand?->id;
        $sedeId = $vehicle->warehouse?->sede_id;
        $shopId = $sedeToShopMap[$sedeId] ?? null;
        if ($brandId && $shopId) {
          $libreMap[$shopId][$brandId] = ($libreMap[$shopId][$brandId] ?? 0) + 1;
        }
      }
    }

    return [
      'summary'   => $summary,
      'items'     => $items,
      'libre_map' => $libreMap,
    ];
  }
}

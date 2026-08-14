<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WorkOrderOpeningReportService
{
  /**
   * Obtiene el reporte de Órdenes de Trabajo por Apertura
   *
   * @param array $filters
   * @return Collection
   */
  public function getWorkOrderOpeningsReport(array $filters = []): Collection
  {
    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->getUserSedeIds();

    // Consultar Órdenes de Trabajo
    $query = ApWorkOrder::query()
      ->with([
        'invoiceTo.documentType',
        'invoiceTo.typePerson',
        'vehicle.model.family.brand',
        'vehicle.model.family',
        'sede',
        'advisor',
        'items.typePlanning',
        'items.typeOperation',
        'plannings.worker',
        'labours',
        'parts.product',
        'typeCurrency',
      ]);

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $query->whereIn('sede_id', $userSedeIds);
    }

    // Aplicar filtros
    $this->applyFilters($query, $filters);

    $workOrders = $query->orderBy('opening_date', 'asc')->get();

    // Transformar OTs para el reporte
    return $workOrders->map(function ($workOrder) {
      return $this->transformWorkOrderForReport($workOrder);
    });
  }

  /**
   * Transforma una Orden de Trabajo en el formato del reporte
   *
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function transformWorkOrderForReport(ApWorkOrder $workOrder): array
  {
    $invoiceTo = $workOrder->invoiceTo;
    $vehicle = $workOrder->vehicle;
    $firstItem = $workOrder->items->first();

    // Obtener técnicos únicos consolidados
    $technicians = $this->getConsolidatedTechnicians($workOrder);

    // Calcular precios en la moneda original de la OT
    $prices = $this->calculatePrices($workOrder);

    // Obtener símbolo de moneda
    $currencySymbol = $workOrder->typeCurrency?->symbol ?? '';

    return [
      'taller' => $workOrder->sede?->abreviatura ?? '',
      'marca' => $vehicle?->model?->family?->brand?->name ?? '',
      'modelo_vehiculo' => $vehicle?->model?->family?->description ?? '',
      'kilometraje' => $vehicle?->mileage ?? '',
      'placa' => $workOrder->vehicle_plate ?? '',
      'vin' => $workOrder->vehicle_vin ?? '',
      'tipo_ingreso' => $workOrder->appointment_planning_id ? 'CON CITA' : 'SIN CITA',
      'numero_ot' => $workOrder->correlative ?? '',
      'tipo_servicio' => $firstItem?->typePlanning?->description ?? '',
      'tipo_operacion' => $firstItem?->typeOperation?->description ?? '',
      'asesor_servicio' => $workOrder->advisor?->nombre_completo ?? '',
      'nombre_tecnico' => $technicians,
      'fecha_apertura_ot' => $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : '',
      'fecha_cierre_ot' => $workOrder->actual_delivery_date ? $workOrder->actual_delivery_date->format('d/m/Y') : '',
      'precio_total' => number_format($prices['total'], 2, '.', ''),
      'moneda' => $currencySymbol,
    ];
  }

  /**
   * Obtiene el tipo de cliente (Natural/Jurídica)
   *
   * @param $invoiceTo
   * @return string
   */
  private function getCustomerType($invoiceTo): string
  {
    if (!$invoiceTo || !$invoiceTo->type_person_id) {
      return '';
    }

    if ($invoiceTo->type_person_id == ApMasters::TYPE_PERSON_NATURAL_ID) {
      return 'NATURAL';
    } elseif ($invoiceTo->type_person_id == ApMasters::TYPE_PERSON_JURIDICA_ID) {
      return 'JURIDICA';
    }

    return '';
  }

  /**
   * Obtiene los técnicos únicos consolidados
   *
   * @param ApWorkOrder $workOrder
   * @return string
   */
  private function getConsolidatedTechnicians(ApWorkOrder $workOrder): string
  {
    $technicians = $workOrder->plannings
      ->whereNotNull('worker_id')
      ->pluck('worker.nombre_completo')
      ->filter()
      ->unique()
      ->values();

    return $technicians->implode(', ');
  }

  /**
   * Calcula los precios en la moneda original de la OT (sin conversión)
   *
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function calculatePrices(ApWorkOrder $workOrder): array
  {
    // Precio de mano de obra
    $labourCost = $workOrder->labours->sum('net_amount');

    // Precio de repuestos (sin lubricantes)
    $partsCost = $workOrder->parts
      ->filter(function ($part) {
        return $part->product && $part->product->product_category_id != ApMasters::LUBRICANTE_ID;
      })
      ->sum('net_amount');

    // Precio de lubricantes
    $lubricantsCost = $workOrder->parts
      ->filter(function ($part) {
        return $part->product && $part->product->product_category_id == ApMasters::LUBRICANTE_ID;
      })
      ->sum('net_amount');

    // Total
    $total = $labourCost + $partsCost + $lubricantsCost;

    return [
      'mano_obra' => $labourCost,
      'repuestos' => $partsCost,
      'lubricantes' => $lubricantsCost,
      'total' => $total,
    ];
  }

  /**
   * Aplica filtros a la query
   *
   * @param $query
   * @param array $filters
   * @return void
   */
  private function applyFilters($query, array $filters): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      switch ($operator) {
        case 'between':
          if (is_array($value) && count($value) === 2) {
            // Si el filtro es para opening_date, usar whereDate para comparar solo la fecha
            if ($column === 'opening_date') {
              $query->whereDate($column, '>=', $value[0])
                ->whereDate($column, '<=', $value[1]);
            } else {
              $query->whereBetween($column, [$value[0], $value[1]]);
            }
          }
          break;
        case '=':
          $query->where($column, $value);
          break;
        case 'like':
          $query->where($column, 'like', '%' . $value . '%');
          break;
      }
    }
  }

  /**
   * Obtiene los IDs de las sedes asociadas al usuario autenticado
   *
   * @return array
   */
  private function getUserSedeIds(): array
  {
    $user = Auth::user();

    if (!$user) {
      return [];
    }

    return UserSede::where('user_id', $user->id)
      ->where('status', true)
      ->pluck('sede_id')
      ->toArray();
  }
}

<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Support\Collection;

class TallerReportService
{
  /**
   * Obtiene el reporte de Órdenes de Trabajo
   *
   * @param array $filters
   * @param bool $amountsInSoles
   * @return Collection
   */
  public function getWorkOrdersReport(array $filters = [], bool $amountsInSoles = false): Collection
  {
    $query = ApWorkOrder::query()
      ->with([
        'invoiceTo.documentType',
        'invoiceTo.typePerson',
        'vehicle.model.family.brand',
        'vehicle.model.family',
        'sede',
        'advisor',
        'items.typePlanning',
        'plannings.worker',
        'labours',
        'parts.product',
        'typeCurrency',
        'exchangeRate'
      ]);

    // Aplicar filtros
    $this->applyFilters($query, $filters);

    $workOrders = $query->get();

    return $workOrders->map(function ($workOrder) use ($amountsInSoles) {
      return $this->transformWorkOrderForReport($workOrder, $amountsInSoles);
    });
  }

  /**
   * Transforma una Orden de Trabajo en el formato del reporte
   *
   * @param ApWorkOrder $workOrder
   * @param bool $amountsInSoles
   * @return array
   */
  private function transformWorkOrderForReport(ApWorkOrder $workOrder, bool $amountsInSoles = false): array
  {
    $invoiceTo = $workOrder->invoiceTo;
    $vehicle = $workOrder->vehicle;
    $firstItem = $workOrder->items->first();

    // Obtener técnicos únicos consolidados
    $technicians = $this->getConsolidatedTechnicians($workOrder);

    // Calcular precios según la moneda solicitada
    $prices = $amountsInSoles
      ? $this->calculatePricesInSoles($workOrder)
      : $this->calculatePricesInDollars($workOrder);

    return [
      'tipo_documento' => $invoiceTo?->documentType?->description ?? '',
      'numero_documento' => $invoiceTo?->num_doc ?? '',
      'nombre_completo_razon_social' => $invoiceTo?->full_name ?? '',
      'tipo_cliente' => $this->getCustomerType($invoiceTo),
      'email' => $invoiceTo?->email ?? '',
      'numero_telefonico' => $invoiceTo?->phone ?? '',
      'marca' => $vehicle?->model?->family?->brand?->name ?? '',
      'modelo_vehiculo' => $vehicle?->model?->family?->description ?? '',
      'kilometraje' => $vehicle?->mileage ?? '',
      'placa' => $workOrder->vehicle_plate ?? '',
      'vin' => $workOrder->vehicle_vin ?? '',
      'concesionario' => $workOrder->sede?->abreviatura ?? '',
      'tipo_ingreso' => $workOrder->appointment_planning_id ? 'CON CITA' : 'SIN CITA',
      'numero_ot' => $workOrder->correlative ?? '',
      'tipo_servicio' => $firstItem?->typePlanning?->description ?? '',
      'ot_inicial_reingreso' => '', // En blanco según requerimiento
      'detalle' => $firstItem?->description ?? '',
      'asesor_servicio' => $workOrder->advisor?->nombre_completo ?? '',
      'nombre_tecnico' => $technicians,
      'fecha_apertura_ot' => $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : '',
      'hora_apertura_ot' => $workOrder->created_at ? $workOrder->created_at->format('H:i') : '',
      'fecha_cierre_ot' => $workOrder->actual_delivery_date ? $workOrder->actual_delivery_date->format('d/m/Y') : '',
      'hora_cierre_ot' => $workOrder->actual_delivery_date ? $workOrder->actual_delivery_date->format('H:i') : '',
      'precio_mano_obra' => number_format($prices['mano_obra'], 2, '.', ''),
      'precio_repuesto' => number_format($prices['repuestos'], 2, '.', ''),
      'precio_lubricantes' => number_format($prices['lubricantes'], 2, '.', ''),
      'precio_trabajo_externo' => '', // En blanco según requerimiento
      'precio_insumo' => '', // En blanco según requerimiento
      'precio_total' => number_format($prices['total'], 2, '.', ''),
      'autorizacion_datos_personales' => '', // En blanco según requerimiento
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
   * Calcula los precios en dólares
   *
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function calculatePricesInDollars(ApWorkOrder $workOrder): array
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

    // Si la OT ya está en dólares, no convertir
    if ($workOrder->currency_id == TypeCurrency::USD_ID) {
      $labourCostUSD = $labourCost;
      $partsCostUSD = $partsCost;
      $lubricantsCostUSD = $lubricantsCost;
    } else {
      // La OT está en soles, convertir a dólares
      $exchangeRate = $workOrder->getExchangeRateToUsd();
      $labourCostUSD = $labourCost / $exchangeRate;
      $partsCostUSD = $partsCost / $exchangeRate;
      $lubricantsCostUSD = $lubricantsCost / $exchangeRate;
    }

    // Total
    $totalUSD = $labourCostUSD + $partsCostUSD + $lubricantsCostUSD;

    return [
      'mano_obra' => $labourCostUSD,
      'repuestos' => $partsCostUSD,
      'lubricantes' => $lubricantsCostUSD,
      'total' => $totalUSD,
    ];
  }

  /**
   * Calcula los precios en soles
   *
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function calculatePricesInSoles(ApWorkOrder $workOrder): array
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

    // Si la OT ya está en soles, no convertir
    if ($workOrder->currency_id == TypeCurrency::PEN_ID) {
      $labourCostPEN = $labourCost;
      $partsCostPEN = $partsCost;
      $lubricantsCostPEN = $lubricantsCost;
    } else {
      // La OT está en dólares, convertir a soles
      // Obtener el tipo de cambio real (no el de getExchangeRateToUsd que retorna 1.0 para USD)
      $exchangeRate = $this->getRealExchangeRate($workOrder);
      $labourCostPEN = $labourCost * $exchangeRate;
      $partsCostPEN = $partsCost * $exchangeRate;
      $lubricantsCostPEN = $lubricantsCost * $exchangeRate;
    }

    // Total
    $totalPEN = $labourCostPEN + $partsCostPEN + $lubricantsCostPEN;

    return [
      'mano_obra' => $labourCostPEN,
      'repuestos' => $partsCostPEN,
      'lubricantes' => $lubricantsCostPEN,
      'total' => $totalPEN,
    ];
  }

  /**
   * Obtiene el tipo de cambio real de la OT, sin la validación de USD
   * Esto permite convertir de USD a PEN correctamente
   *
   * @param ApWorkOrder $workOrder
   * @return float
   */
  private function getRealExchangeRate(ApWorkOrder $workOrder): float
  {
    // Intenta obtener el tipo de cambio de la columna exchange_rate
    if ($workOrder->exchange_rate) {
      return (float) $workOrder->exchange_rate;
    }

    // Intenta obtener el tipo de cambio de la relación exchangeRate
    if ($workOrder->exchangeRate && $workOrder->exchangeRate->rate) {
      return (float) $workOrder->exchangeRate->rate;
    }

    // Intenta obtener el tipo de cambio del último documento electrónico
    $lastDocument = $workOrder->exchangeRateDocuments()
      ->whereNotNull('exchange_rate_id')
      ->orderByDesc('created_at')
      ->first();

    if ($lastDocument && $lastDocument->exchangeRate && $lastDocument->exchangeRate->rate) {
      return (float) $lastDocument->exchangeRate->rate;
    }

    // Valor por defecto si no hay ningún tipo de cambio
    return 3.75;
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
        case '=':
          $query->where($column, $value);
          break;
        case 'in':
        case 'in_or_equal':
          if (is_array($value)) {
            $query->whereIn($column, $value);
          } else {
            $query->where($column, $value);
          }
          break;
        case 'closed_or_invoiced':
          // Filtrar OTs que estén cerradas O tengan factura final emitida
          $query->where(function ($q) use ($value) {
            $q->where('status_id', $value)
              ->orWhereHas('exchangeRateDocuments', function ($docQuery) {
                $docQuery->where('is_advance_payment', false)
                  ->where('anulado', false);
              });
          });
          break;
        case 'like':
          $query->where($column, 'like', '%' . $value . '%');
          break;
        case 'between':
        case 'date_between':
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween($column, [$value[0], $value[1]]);
          }
          break;
      }
    }
  }
}

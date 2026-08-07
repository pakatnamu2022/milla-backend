<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PartsReportService
{
  /**
   * Obtiene el reporte de Repuestos cargados a Órdenes de Trabajo
   *
   * @param array $filters
   * @param bool $amountsInSoles
   * @return Collection
   */
  public function getPartsReport(array $filters = [], bool $amountsInSoles = false): Collection
  {
    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->getUserSedeIds();

    // Consultar WorkOrders cerradas con repuestos
    $query = ApWorkOrder::query()
      ->with([
        'parts.product',
        'parts.warehouse',
        'items.typePlanning',
        'sede',
        'exchangeRate',
      ])
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->whereHas('parts'); // Solo OTs que tengan repuestos

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $query->whereIn('sede_id', $userSedeIds);
    }

    // Aplicar filtros
    $this->applyFilters($query, $filters);

    $workOrders = $query->get();

    // Transformar datos para el reporte (una fila por cada repuesto)
    $reportData = $workOrders->flatMap(function ($workOrder) use ($amountsInSoles) {
      return $workOrder->parts->map(function ($part) use ($workOrder, $amountsInSoles) {
        return $this->transformPartForReport($workOrder, $part, $amountsInSoles);
      });
    })->values();

    return $reportData;
  }

  /**
   * Transforma un repuesto de una Orden de Trabajo en el formato del reporte
   *
   * @param ApWorkOrder $workOrder
   * @param $part
   * @param bool $amountsInSoles
   * @return array
   */
  private function transformPartForReport(ApWorkOrder $workOrder, $part, bool $amountsInSoles = false): array
  {
    $firstItem = $workOrder->items->first();

    // Obtener el almacén físico de postventa para la sede
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($workOrder->sede_id);

    // Obtener el costo del producto desde ProductWarehouseStock
    $costPrice = 0;
    if ($warehouse && $part->product_id) {
      $stock = ProductWarehouseStock::where('product_id', $part->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      $costPrice = $stock ? (float)$stock->cost_price : 0;
    }

    // Calcular precios según la moneda solicitada
    $prices = $amountsInSoles
      ? $this->calculatePricesInSoles($workOrder, $part, $costPrice)
      : $this->calculatePricesInDollars($workOrder, $part, $costPrice);

    return [
      'numero_ot' => $workOrder->correlative ?? '',
      'fecha_apertura_ot' => $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : '',
      'fecha_cierre_ot' => $workOrder->actual_delivery_date ? $workOrder->actual_delivery_date->format('d/m/Y') : '',
      'tipo_servicio' => $firstItem?->typePlanning?->description ?? '',
      'codigo_repuesto' => $part->product?->code ?? '',
      'nombre_repuesto' => $part->product?->name ?? '',
      'cantidad' => number_format((float)$part->quantity_used, 2, '.', ''),
      'pvp' => number_format($prices['pvp'], 2, '.', ''),
      'descuento' => number_format($prices['descuento_porcentaje'], 2, '.', ''),
      'neto' => number_format($prices['neto'], 2, '.', ''),
      'costo' => number_format($prices['costo'], 2, '.', ''),
      'beneficio' => number_format($prices['beneficio'], 2, '.', ''),
    ];
  }

  /**
   * Calcula los precios en dólares
   *
   * @param ApWorkOrder $workOrder
   * @param $part
   * @param float $costPrice
   * @return array
   */
  private function calculatePricesInDollars(ApWorkOrder $workOrder, $part, float $costPrice): array
  {
    // Si la OT ya está en dólares, no convertir
    if ($workOrder->currency_id == TypeCurrency::USD_ID) {
      $pvp = (float)$part->unit_price;
      $neto = (float)$part->net_amount;
      $costo = $costPrice;
    } else {
      // La OT está en soles, convertir a dólares
      $exchangeRate = $workOrder->getExchangeRateToUsd();
      $pvp = (float)$part->unit_price / $exchangeRate;
      $neto = (float)$part->net_amount / $exchangeRate;
      $costo = $costPrice / $exchangeRate;
    }

    // Descuento porcentaje
    $descuentoPorcentaje = (float)$part->discount_percentage;

    // Beneficio = Neto - (Costo * Cantidad)
    $beneficio = $neto - ($costo * (float)$part->quantity_used);

    return [
      'pvp' => $pvp,
      'descuento_porcentaje' => $descuentoPorcentaje,
      'neto' => $neto,
      'costo' => $costo,
      'beneficio' => $beneficio,
    ];
  }

  /**
   * Calcula los precios en soles
   *
   * @param ApWorkOrder $workOrder
   * @param $part
   * @param float $costPrice
   * @return array
   */
  private function calculatePricesInSoles(ApWorkOrder $workOrder, $part, float $costPrice): array
  {
    // Si la OT ya está en soles, no convertir
    if ($workOrder->currency_id == TypeCurrency::PEN_ID) {
      $pvp = (float)$part->unit_price;
      $neto = (float)$part->net_amount;
      $costo = $costPrice;
    } else {
      // La OT está en dólares, convertir a soles
      $exchangeRate = $this->getRealExchangeRate($workOrder);
      $pvp = (float)$part->unit_price * $exchangeRate;
      $neto = (float)$part->net_amount * $exchangeRate;
      $costo = $costPrice * $exchangeRate;
    }

    // Descuento porcentaje
    $descuentoPorcentaje = (float)$part->discount_percentage;

    // Beneficio = Neto - (Costo * Cantidad)
    $beneficio = $neto - ($costo * (float)$part->quantity_used);

    return [
      'pvp' => $pvp,
      'descuento_porcentaje' => $descuentoPorcentaje,
      'neto' => $neto,
      'costo' => $costo,
      'beneficio' => $beneficio,
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
      return (float)$workOrder->exchange_rate;
    }

    // Intenta obtener el tipo de cambio de la relación exchangeRate
    if ($workOrder->exchangeRate && $workOrder->exchangeRate->rate) {
      return (float)$workOrder->exchangeRate->rate;
    }

    // Intenta obtener el tipo de cambio del último documento electrónico
    $lastDocument = $workOrder->exchangeRateDocuments()
      ->whereNotNull('exchange_rate_id')
      ->orderByDesc('created_at')
      ->first();

    if ($lastDocument && $lastDocument->exchangeRate && $lastDocument->exchangeRate->rate) {
      return (float)$lastDocument->exchangeRate->rate;
    }

    // Valor por defecto si no hay ningún tipo de cambio
    return 3.75;
  }

  /**
   * Aplica filtros a la query de WorkOrders
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
        case 'workOrderDateFilter':
          // Filtro de rango de fechas de cierre de OT
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween('actual_delivery_date', [$value[0], $value[1]]);
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
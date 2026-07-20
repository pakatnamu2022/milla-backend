<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    $allWorkOrders = collect();

    // 1. Consultar WorkOrders que tienen documentos electrónicos (SIMPLE y MASSIVE)
    $queryDocuments = ElectronicDocument::query()
      ->with([
        'workOrder.invoiceTo.documentType',
        'workOrder.invoiceTo.typePerson',
        'workOrder.vehicle.model.family.brand',
        'workOrder.vehicle.model.family',
        'workOrder.sede',
        'workOrder.advisor',
        'workOrder.items.typePlanning',
        'workOrder.plannings.worker',
        'workOrder.labours',
        'workOrder.parts.product',
        'workOrder.typeCurrency',
        'workOrder.exchangeRate',
        'workOrder.internalNotes',
        'internalNotes.workOrder.invoiceTo.documentType',
        'internalNotes.workOrder.invoiceTo.typePerson',
        'internalNotes.workOrder.vehicle.model.family.brand',
        'internalNotes.workOrder.vehicle.model.family',
        'internalNotes.workOrder.sede',
        'internalNotes.workOrder.advisor',
        'internalNotes.workOrder.items.typePlanning',
        'internalNotes.workOrder.plannings.worker',
        'internalNotes.workOrder.labours',
        'internalNotes.workOrder.parts.product',
        'internalNotes.workOrder.typeCurrency',
        'internalNotes.workOrder.exchangeRate',
        'internalNotes.workOrder.internalNotes',
      ])
      ->where('anulado', false)
      ->where('is_advance_payment', false) // Solo facturas finales, no anticipos
      ->where(function ($q) {
        // Facturación SIMPLE: tiene work_order_id directo
        $q->whereNotNull('work_order_id')
          // Facturación MASIVA: tiene notas internas facturadas
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Aplicar filtros de documentos
    $this->applyDocumentFilters($queryDocuments, $filters);

    $documents = $queryDocuments->get();

    // Extraer las WorkOrders de los documentos
    foreach ($documents as $document) {
      // SIMPLE: tiene work_order_id directo
      if ($document->workOrder) {
        $allWorkOrders->push($document->workOrder);
      }

      // MASSIVE: tiene notas internas → múltiples WorkOrders
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        foreach ($document->internalNotes as $internalNote) {
          if ($internalNote->workOrder) {
            $allWorkOrders->push($internalNote->workOrder);
          }
        }
      }
    }

    // 2. Consultar WorkOrders cerradas con nota interna SIN factura
    $queryInternalNoteWorkOrders = ApWorkOrder::query()
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
        'exchangeRate',
        'internalNotes'
      ])
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->whereHas('internalNotes', function ($q) {
        $q->whereNotNull('number');
      })
      ->whereHas('items', function ($q) {
        $q->whereHas('typePlanning', function ($subQ) {
          $subQ->where('type_document', 'INTERNA')
            ->whereNotIn('id', [
              \App\Models\ap\postventa\taller\TypePlanningWorkOrder::TYPE_PLANNING_DERCO_WARRANTY_ID,
              \App\Models\ap\postventa\taller\TypePlanningWorkOrder::TYPE_PLANNING_ODEBRECHT_MAINTENANCE,
            ]);
        });
      })
      ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('ap_billing_electronic_documents')
          ->whereColumn('ap_billing_electronic_documents.work_order_id', 'ap_work_orders.id')
          ->where('ap_billing_electronic_documents.anulado', false);
      })
      ->whereDoesntHave('internalNotes', function ($q) {
        $q->whereHas('electronicDocuments');
      });

    // Aplicar filtros de notas internas
    $this->applyInternalNoteFilters($queryInternalNoteWorkOrders, $filters);

    $internalNoteWorkOrders = $queryInternalNoteWorkOrders->get();

    // Agregar estas OTs a la colección
    $allWorkOrders = $allWorkOrders->concat($internalNoteWorkOrders);

    // Eliminar duplicados por ID y transformar
    return $allWorkOrders->unique('id')->map(function ($workOrder) use ($amountsInSoles) {
      return $this->transformWorkOrderForReport($workOrder, $amountsInSoles);
    })->values();
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
   * Aplica filtros a la query de ElectronicDocument
   *
   * @param $query
   * @param array $filters
   * @return void
   */
  private function applyDocumentFilters($query, array $filters): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      switch ($operator) {
        case 'documentDateFilter':
          // Filtro de fecha de emisión en documentos
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween('fecha_de_emision', [$value[0], $value[1]]);
          }
          break;
        case '=':
          // Filtros en la tabla workOrder
          if (in_array($column, ['sede_id'])) {
            $query->where(function ($q) use ($column, $value) {
              // Filtrar por sede desde workOrder (simple) o desde internalNotes->workOrder (massive)
              $q->whereHas('workOrder', function ($subQ) use ($column, $value) {
                $subQ->where($column, $value);
              })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($column, $value) {
                $subQ->where($column, $value);
              });
            });
          }
          break;
        case 'like':
          // Filtros like en la tabla workOrder
          if (in_array($column, ['correlative'])) {
            $query->where(function ($q) use ($column, $value) {
              // Filtrar por correlativo desde workOrder (simple) o desde internalNotes->workOrder (massive)
              $q->whereHas('workOrder', function ($subQ) use ($column, $value) {
                $subQ->where($column, 'like', '%' . $value . '%');
              })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($column, $value) {
                $subQ->where($column, 'like', '%' . $value . '%');
              });
            });
          }
          break;
      }
    }
  }

  /**
   * Aplica filtros a la query de OTs con nota interna sin factura
   *
   * @param $query
   * @param array $filters
   * @return void
   */
  private function applyInternalNoteFilters($query, array $filters): void
  {
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      switch ($operator) {
        case 'documentDateFilter':
          // Filtro de fecha en created_date de la nota interna
          if (is_array($value) && count($value) === 2) {
            $query->whereHas('internalNotes', function ($q) use ($value) {
              $q->whereBetween('created_date', [$value[0], $value[1]]);
            });
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
}
<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false) // Solo facturas finales, no anticipos
      ->where(function ($q) {
        // Facturación SIMPLE: tiene work_order_id directo
        $q->whereNotNull('work_order_id')
          // Facturación MASIVA: tiene notas internas facturadas
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $queryDocuments->where(function ($q) use ($userSedeIds) {
        // Sede desde workOrder (simple) o desde internalNotes->workOrder (massive)
        $q->whereHas('workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        });
      });
    }

    // Aplicar filtros de documentos
    $this->applyDocumentFilters($queryDocuments, $filters);

    $documents = $queryDocuments->get();

    // Transformar documentos para el reporte (una fila por cada repuesto)
    $reportData = $documents->flatMap(function ($document) use ($amountsInSoles) {
      // SIMPLE: tiene work_order_id directo → tomar repuestos de esa OT
      if ($document->workOrder) {
        return $this->extractPartsFromWorkOrder($document->workOrder, $amountsInSoles, $document);
      }

      // MASSIVE: tiene notas internas → tomar repuestos de cada OT de las notas internas
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        return $document->internalNotes->flatMap(function ($internalNote) use ($amountsInSoles, $document) {
          if ($internalNote->workOrder) {
            return $this->extractPartsFromWorkOrder($internalNote->workOrder, $amountsInSoles, $document);
          }
          return [];
        });
      }

      return []; // Sin OT, skip
    })->values();

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
          $subQ->whereIn('type_document', [
              TypePlanningWorkOrder::INTERNA_SC,
              TypePlanningWorkOrder::INTERNA_CC,
            ])
            ->whereNotIn('id', [
              TypePlanningWorkOrder::TYPE_PLANNING_DERCO_WARRANTY_ID,
              TypePlanningWorkOrder::TYPE_PLANNING_ODEBRECHT_MAINTENANCE,
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

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $queryInternalNoteWorkOrders->whereIn('sede_id', $userSedeIds);
    }

    // Aplicar filtros de notas internas
    $this->applyInternalNoteFilters($queryInternalNoteWorkOrders, $filters);

    $internalNoteWorkOrders = $queryInternalNoteWorkOrders->get();

    // Transformar OTs con nota interna SIN factura (tomar repuestos)
    $reportDataInternalNotes = $internalNoteWorkOrders->flatMap(function ($workOrder) use ($amountsInSoles) {
      return $this->extractPartsFromWorkOrder($workOrder, $amountsInSoles);
    })->values();

    // Combinar documentos con OTs de nota interna sin factura
    return $reportData->concat($reportDataInternalNotes)->values();
  }

  /**
   * Extrae los repuestos de una Orden de Trabajo y retorna una fila por cada repuesto
   *
   * @param ApWorkOrder $workOrder
   * @param bool $amountsInSoles
   * @param ElectronicDocument|null $document
   * @return Collection
   */
  private function extractPartsFromWorkOrder(ApWorkOrder $workOrder, bool $amountsInSoles = false, ?ElectronicDocument $document = null): Collection
  {
    // Verificar si la OT tiene repuestos
    if (!$workOrder->parts || $workOrder->parts->count() === 0) {
      return collect([]);
    }

    // Verificar si es Nota de Crédito
    $multiplier = 1;
    if ($document && $document->sunat_concept_document_type_id === SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA) {
      $multiplier = -1;
    }

    // Retornar una fila por cada repuesto
    return $workOrder->parts->map(function ($part) use ($workOrder, $amountsInSoles, $multiplier, $document) {
      return $this->transformPartForReport($workOrder, $part, $amountsInSoles, $multiplier, $document);
    });
  }

  /**
   * Transforma un repuesto de una Orden de Trabajo en el formato del reporte
   *
   * @param ApWorkOrder $workOrder
   * @param $part
   * @param bool $amountsInSoles
   * @param float $multiplier
   * @param ElectronicDocument|null $document
   * @return array
   */
  private function transformPartForReport(ApWorkOrder $workOrder, $part, bool $amountsInSoles = false, float $multiplier = 1, ?ElectronicDocument $document = null): array
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
      ? $this->calculatePricesInSoles($workOrder, $part, $costPrice, $multiplier)
      : $this->calculatePricesInDollars($workOrder, $part, $costPrice, $multiplier);

    // Convertir estado SUNAT a SI/NO
    $estadoSunat = '';
    if ($document) {
      if ($document->aceptada_por_sunat === 1 || $document->aceptada_por_sunat === true || $document->aceptada_por_sunat === '1') {
        $estadoSunat = 'SI';
      } elseif ($document->aceptada_por_sunat === 0 || $document->aceptada_por_sunat === false || $document->aceptada_por_sunat === '0') {
        $estadoSunat = 'NO';
      }
    }

    return [
      'numero_ot' => $workOrder->correlative ?? '',
      'fecha_apertura_ot' => $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : '',
      'fecha_cierre_ot' => $workOrder->actual_delivery_date ? $workOrder->actual_delivery_date->format('d/m/Y') : '',
      'tipo_servicio' => $firstItem?->typePlanning?->description ?? '',
      'codigo_repuesto' => $part->product?->code ?? '',
      'nombre_repuesto' => $part->product?->name ?? '',
      'cantidad' => number_format((float)$part->quantity_used * $multiplier, 2, '.', ''),
      'pvp' => number_format($prices['pvp'], 2, '.', ''),
      'descuento' => number_format($prices['descuento_porcentaje'], 2, '.', ''),
      'neto' => number_format($prices['neto'], 2, '.', ''),
      'costo' => number_format($prices['costo'], 2, '.', ''),
      'beneficio' => number_format($prices['beneficio'], 2, '.', ''),
      'num_documento_electronico' => $document?->full_number ?? '',
      'fecha_documento_electronico' => $document && $document->fecha_de_emision ? $document->fecha_de_emision->format('d/m/Y') : '',
      'estado_sunat' => $estadoSunat,
    ];
  }

  /**
   * Calcula los precios en dólares
   *
   * @param ApWorkOrder $workOrder
   * @param $part
   * @param float $costPrice
   * @param float $multiplier
   * @return array
   */
  private function calculatePricesInDollars(ApWorkOrder $workOrder, $part, float $costPrice, float $multiplier = 1): array
  {
    // Si la OT ya está en dólares, no convertir
    if ($workOrder->currency_id == TypeCurrency::USD_ID) {
      $pvp = (float)$part->unit_price * $multiplier;
      $neto = (float)$part->net_amount * $multiplier;
      $costo = $costPrice * $multiplier;
    } else {
      // La OT está en soles, convertir a dólares
      $exchangeRate = $workOrder->getExchangeRateToUsd();
      $pvp = ((float)$part->unit_price / $exchangeRate) * $multiplier;
      $neto = ((float)$part->net_amount / $exchangeRate) * $multiplier;
      $costo = ($costPrice / $exchangeRate) * $multiplier;
    }

    // Descuento porcentaje (el porcentaje no se multiplica)
    $descuentoPorcentaje = (float)$part->discount_percentage;

    // Beneficio = Neto - (Costo * Cantidad) (ya con multiplicador aplicado)
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
   * @param float $multiplier
   * @return array
   */
  private function calculatePricesInSoles(ApWorkOrder $workOrder, $part, float $costPrice, float $multiplier = 1): array
  {
    // Si la OT ya está en soles, no convertir
    if ($workOrder->currency_id == TypeCurrency::PEN_ID) {
      $pvp = (float)$part->unit_price * $multiplier;
      $neto = (float)$part->net_amount * $multiplier;
      $costo = $costPrice * $multiplier;
    } else {
      // La OT está en dólares, convertir a soles
      $exchangeRate = $this->getRealExchangeRate($workOrder);
      $pvp = ((float)$part->unit_price * $exchangeRate) * $multiplier;
      $neto = ((float)$part->net_amount * $exchangeRate) * $multiplier;
      $costo = ($costPrice * $exchangeRate) * $multiplier;
    }

    // Descuento porcentaje (el porcentaje no se multiplica)
    $descuentoPorcentaje = (float)$part->discount_percentage;

    // Beneficio = Neto - (Costo * Cantidad) (ya con multiplicador aplicado)
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
        case 'workOrderDateFilter':
          // Filtro de fecha de cierre de OT en workOrder (simple) o internalNotes->workOrder (massive)
          if (is_array($value) && count($value) === 2) {
            $query->where(function ($q) use ($value) {
              $q->whereHas('workOrder', function ($subQ) use ($value) {
                $subQ->whereBetween('actual_delivery_date', [$value[0], $value[1]]);
              })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($value) {
                $subQ->whereBetween('actual_delivery_date', [$value[0], $value[1]]);
              });
            });
          }
          break;
        case '=':
          // Filtros en la tabla workOrder
          if (in_array($column, ['sede_id', 'currency_id'])) {
            $query->where(function ($q) use ($column, $value) {
              // Filtrar por sede/moneda desde workOrder (simple) o desde internalNotes->workOrder (massive)
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
        case 'workOrderDateFilter':
          // Filtro de fecha en actual_delivery_date de la OT
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
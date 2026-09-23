<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WorkShopReportService
{
  protected ClosedWorkOrdersService $closedWorkOrdersService;

  public function __construct(ClosedWorkOrdersService $closedWorkOrdersService)
  {
    $this->closedWorkOrdersService = $closedWorkOrdersService;
  }

  /**
   * Obtiene el reporte de Órdenes de Trabajo
   *
   * @param array $filters
   * @param bool $amountsInSoles
   * @return Collection
   */
  public function getWorkOrdersReport(array $filters = [], bool $amountsInSoles = false): Collection
  {
    // Extraer parámetros de los filtros
    $dateRange = null;
    $sedeIdFilter = null;

    foreach ($filters as $filter) {
      if (isset($filter['column']) && $filter['column'] === 'fecha_de_emision' && isset($filter['operator']) && $filter['operator'] === 'documentDateFilter') {
        $dateRange = $filter['value'];
      }
      if (isset($filter['column']) && $filter['column'] === 'sede_id' && isset($filter['value'])) {
        $sedeIdFilter = $filter['value'];
      }
    }

    // Validar que haya rango de fechas
    if (!$dateRange || count($dateRange) !== 2) {
      throw new \InvalidArgumentException('Se requiere un rango de fechas válido');
    }

    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->closedWorkOrdersService->getUserSedeIds();

    // 1. Obtener documentos electrónicos de OTs cerradas usando el servicio centralizado
    $documents = $this->closedWorkOrdersService
      ->getElectronicDocumentsOfClosedWorkOrders($dateRange, $sedeIdFilter, $userSedeIds)
      ->load([
        'workOrder.invoiceTo.documentType',
        'workOrder.invoiceTo.typePerson',
        'workOrder.vehicle.customer.documentType',
        'workOrder.vehicle.customer.typePerson',
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
        'internalNotes.workOrder.vehicle.customer.documentType',
        'internalNotes.workOrder.vehicle.customer.typePerson',
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
        'currency',
        'exchangeRate',
        'creditNote.currency',
        'creditNote.exchangeRate',
        'creditNote.internalNotes.workOrder.invoiceTo.documentType',
        'creditNote.internalNotes.workOrder.invoiceTo.typePerson',
        'creditNote.internalNotes.workOrder.vehicle.customer.documentType',
        'creditNote.internalNotes.workOrder.vehicle.customer.typePerson',
        'creditNote.internalNotes.workOrder.vehicle.model.family.brand',
        'creditNote.internalNotes.workOrder.vehicle.model.family',
        'creditNote.internalNotes.workOrder.sede',
        'creditNote.internalNotes.workOrder.advisor',
        'creditNote.internalNotes.workOrder.items.typePlanning',
        'creditNote.internalNotes.workOrder.plannings.worker',
        'creditNote.internalNotes.workOrder.labours',
        'creditNote.internalNotes.workOrder.parts.product',
        'creditNote.internalNotes.workOrder.typeCurrency',
        'creditNote.internalNotes.workOrder.exchangeRate',
        'creditNote.internalNotes.workOrder.internalNotes',
      ]);

    // Extraer el filtro de sede_id si existe
    $sedeIdFilter = null;
    foreach ($filters as $filter) {
      if (isset($filter['column']) && $filter['column'] === 'sede_id' && isset($filter['value'])) {
        $sedeIdFilter = $filter['value'];
        break;
      }
    }

    // Transformar documentos para el reporte (igual que InvoicingReport)
    $reportData = $documents->flatMap(function ($document) use ($amountsInSoles, $userSedeIds, $sedeIdFilter) {
      $rows = collect();

      // SIMPLE: tiene work_order_id directo → 1 documento = 1 fila
      if ($document->workOrder) {
        // Filtrar por sede si hay filtro específico de sede
        if ($sedeIdFilter !== null && $document->workOrder->sede_id != $sedeIdFilter) {
          // Esta OT no pertenece a la sede filtrada, omitirla
        } elseif (!empty($userSedeIds) && !in_array($document->workOrder->sede_id, $userSedeIds)) {
          // Esta OT no pertenece a las sedes del usuario, omitirla
        } else {
          $rows->push($this->transformWorkOrderForReport($document->workOrder, $amountsInSoles, $document));
        }
      } // MASSIVE: tiene notas internas → 1 documento = MÚLTIPLES filas (una por cada nota interna)
      elseif ($document->internalNotes && $document->internalNotes->count() > 0) {
        $document->internalNotes->each(function ($internalNote) use ($amountsInSoles, $document, $rows, $userSedeIds, $sedeIdFilter) {
          if ($internalNote->workOrder) {
            // Filtrar cada OT por sede individualmente
            if ($sedeIdFilter !== null && $internalNote->workOrder->sede_id != $sedeIdFilter) {
              // Esta OT no pertenece a la sede filtrada, omitirla
              return;
            }
            if (!empty($userSedeIds) && !in_array($internalNote->workOrder->sede_id, $userSedeIds)) {
              // Esta OT no pertenece a las sedes del usuario, omitirla
              return;
            }
            $rows->push($this->transformWorkOrderForReport($internalNote->workOrder, $amountsInSoles, $document));
          }
        });
      }

      // NOTA DE CRÉDITO ASOCIADA: Si el documento tiene credit_note_id, mapear también la nota de crédito
      // usando las MISMAS notas internas de la factura original, pero con montos en negativo
      if ($document->credit_note_id && $document->creditNote) {
        $creditNote = $document->creditNote;

        // Usar las notas internas del documento ORIGINAL (la factura), no de la nota de crédito
        // porque la NC referencia a la factura completa
        if ($document->internalNotes && $document->internalNotes->count() > 0) {
          $document->internalNotes->each(function ($internalNote) use ($amountsInSoles, $creditNote, $document, $rows, $userSedeIds, $sedeIdFilter) {
            if ($internalNote->workOrder) {
              // Filtrar cada OT por sede individualmente
              if ($sedeIdFilter !== null && $internalNote->workOrder->sede_id != $sedeIdFilter) {
                // Esta OT no pertenece a la sede filtrada, omitirla
                return;
              }
              if (!empty($userSedeIds) && !in_array($internalNote->workOrder->sede_id, $userSedeIds)) {
                // Esta OT no pertenece a las sedes del usuario, omitirla
                return;
              }
              // Pasar la nota de crédito como documento Y la factura original para usar su tipo de cambio
              $rows->push($this->transformWorkOrderForReport($internalNote->workOrder, $amountsInSoles, $creditNote, $document));
            }
          });
        }
      }

      return $rows;
    })->values();

    // 2. Obtener WorkOrders cerradas con nota interna SIN factura usando el servicio centralizado
    $internalNoteWorkOrders = $this->closedWorkOrdersService->getWorkOrdersWithInternalNoteOnly(
      $dateRange,
      $sedeIdFilter,
      $userSedeIds
    );

    // Transformar OTs con nota interna SIN factura
    $reportDataInternalNotes = $internalNoteWorkOrders->map(function ($workOrder) use ($amountsInSoles) {
      return $this->transformWorkOrderForReport($workOrder, $amountsInSoles);
    })->values();

    // Combinar documentos con OTs de nota interna sin factura
    return $reportData->concat($reportDataInternalNotes)->values();
  }

  /**
   * Transforma una Orden de Trabajo en el formato del reporte
   *
   * @param ApWorkOrder $workOrder
   * @param bool $amountsInSoles
   * @param ElectronicDocument|null $document
   * @param ElectronicDocument|null $originalDocument Documento original (factura) cuando $document es una NC
   * @return array
   */
  private function transformWorkOrderForReport(ApWorkOrder $workOrder, bool $amountsInSoles = false, ?ElectronicDocument $document = null, ?ElectronicDocument $originalDocument = null): array
  {
    $invoiceTo = $workOrder->invoiceTo;
    $vehicle = $workOrder->vehicle;
    $customer = $vehicle?->customer; // Propietario del vehículo
    $firstItem = $workOrder->items->first();

    // Obtener técnicos únicos consolidados
    $technicians = $this->getConsolidatedTechnicians($workOrder);

    // Verificar si es Nota de Crédito
    $multiplier = 1;
    $isCreditNote = false;
    if ($document && $document->sunat_concept_document_type_id === SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA) {
      $multiplier = -1;
      $isCreditNote = true;
    }

    // Determinar el documento a usar para obtener el tipo de cambio
    // Si es NC y tenemos documento original, usar el tipo de cambio del original
    $documentForExchangeRate = ($isCreditNote && $originalDocument) ? $originalDocument : $document;

    // Calcular precios según la moneda solicitada
    $prices = $amountsInSoles
      ? $this->calculatePricesInSoles($workOrder, $multiplier, $documentForExchangeRate)
      : $this->calculatePricesInDollars($workOrder, $multiplier, $documentForExchangeRate);

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
      'tipo_documento' => $customer?->documentType?->description ?? $invoiceTo?->documentType?->description ?? '',
      'numero_documento' => $customer?->num_doc ?? $invoiceTo?->num_doc ?? '',
      'nombre_completo_razon_social' => $customer?->full_name ?? $invoiceTo?->full_name ?? '',
      'tipo_cliente' => $this->getCustomerType($customer ?? $invoiceTo),
      'email' => $customer?->email ?? $invoiceTo?->email ?? '',
      'numero_telefonico' => $customer?->phone ?? $invoiceTo?->phone ?? '',
      'marca' => $vehicle?->model?->family?->brand?->name ?? '',
      'modelo_vehiculo' => $vehicle?->model?->family?->description ?? '',
      'kilometraje' => $vehicle?->mileage ?? '',
      'placa' => $workOrder->vehicle?->plate ?? '',
      'vin' => $workOrder->vehicle?->vin ?? '',
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
      'fecha_cierre_ot' => $workOrder->official_closing_date ? $workOrder->official_closing_date->format('d/m/Y') : '',
      'hora_cierre_ot' => $workOrder->official_closing_date ? $workOrder->official_closing_date->format('H:i') : '',
      'precio_mano_obra' => number_format($prices['mano_obra'], 2, '.', ''),
      'precio_repuesto' => number_format($prices['repuestos'], 2, '.', ''),
      'precio_lubricantes' => number_format($prices['lubricantes'], 2, '.', ''),
      'precio_trabajo_externo' => '', // En blanco según requerimiento
      'precio_insumo' => '', // En blanco según requerimiento
      'precio_total' => number_format($prices['total'], 2, '.', ''),
      'autorizacion_datos_personales' => '', // En blanco según requerimiento
      'num_documento_electronico' => $document?->full_number ?? '',
      'fecha_documento_electronico' => $document && $document->fecha_de_emision ? $document->fecha_de_emision->format('d/m/Y') : '',
      'estado_sunat' => $estadoSunat,
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
    if (!$invoiceTo || !$invoiceTo->document_type_id) {
      return '';
    }

    if ($invoiceTo->document_type_id == ApMasters::TYPE_DOCUMENT_DNI_ID || $invoiceTo->document_type_id == ApMasters::TYPE_DOCUMENT_CE_ID) {
      return 'NATURAL';
    } elseif ($invoiceTo->document_type_id == ApMasters::TYPE_DOCUMENT_RUC_ID) {
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
   * @param float $multiplier
   * @param ElectronicDocument|null $document Documento para obtener tipo de cambio (cuando es NC, será la factura original)
   * @return array
   */
  private function calculatePricesInDollars(ApWorkOrder $workOrder, float $multiplier = 1, ?ElectronicDocument $document = null): array
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
      $labourCostUSD = $labourCost * $multiplier;
      $partsCostUSD = $partsCost * $multiplier;
      $lubricantsCostUSD = $lubricantsCost * $multiplier;
    } else {
      // La OT está en soles, convertir a dólares
      $exchangeRate = $workOrder->getExchangeRateToUsd();
      $labourCostUSD = ($labourCost / $exchangeRate) * $multiplier;
      $partsCostUSD = ($partsCost / $exchangeRate) * $multiplier;
      $lubricantsCostUSD = ($lubricantsCost / $exchangeRate) * $multiplier;
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
   * @param float $multiplier
   * @param ElectronicDocument|null $document Documento para obtener tipo de cambio (cuando es NC, será la factura original)
   * @return array
   */
  private function calculatePricesInSoles(ApWorkOrder $workOrder, float $multiplier = 1, ?ElectronicDocument $document = null): array
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
      $labourCostPEN = $labourCost * $multiplier;
      $partsCostPEN = $partsCost * $multiplier;
      $lubricantsCostPEN = $lubricantsCost * $multiplier;
    } else {
      // La OT está en dólares, convertir a soles
      // Si tenemos documento y tiene tipo de cambio, usarlo; sino usar el de la OT
      $exchangeRate = null;
      if ($document && $document->sunat_concept_currency_id === SunatConcepts::CURRENCY_USD && $document->exchangeRate) {
        $exchangeRate = (float)$document->exchangeRate->rate;
      }
      if (!$exchangeRate) {
        $exchangeRate = $this->getRealExchangeRate($workOrder);
      }

      $labourCostPEN = ($labourCost * $exchangeRate) * $multiplier;
      $partsCostPEN = ($partsCost * $exchangeRate) * $multiplier;
      $lubricantsCostPEN = ($lubricantsCost * $exchangeRate) * $multiplier;
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
   * Obtiene el reporte de Órdenes de Trabajo Cerradas por Vehículo (última OT por VIN)
   *
   * @param array $filters
   * @return Collection
   */
  public function getClosedWorkOrdersByVehicleReport(array $filters = []): Collection
  {
    // Extraer el rango de fechas del filtro closingDateFilter
    $dateRange = null;
    $sedeIdFilter = null;
    $advisorIdFilter = null;

    foreach ($filters as $filter) {
      if (($filter['operator'] ?? null) === 'closingDateFilter' && is_array($filter['value'] ?? null)) {
        $dateRange = $filter['value'];
      }
      if (($filter['column'] ?? null) === 'sede_id') {
        $sedeIdFilter = $filter['value'] ?? null;
      }
      if (($filter['column'] ?? null) === 'advisor_id') {
        $advisorIdFilter = $filter['value'] ?? null;
      }
    }

    // Validar que existe el rango de fechas
    if (!$dateRange || count($dateRange) !== 2) {
      return collect();
    }

    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->getUserSedeIds();

    // Usar el servicio centralizado para obtener OTs cerradas
    $workOrders = $this->closedWorkOrdersService->getClosedWorkOrders(
      $dateRange,
      $sedeIdFilter,
      $userSedeIds
    );

    // Cargar relaciones adicionales necesarias para el reporte
    $workOrders->load([
      'vehicle.color',
      'vehicle.typeOperation',
    ]);

    // Filtrar por asesor si se especificó
    if ($advisorIdFilter !== null) {
      $workOrders = $workOrders->filter(function ($workOrder) use ($advisorIdFilter) {
        return $workOrder->advisor_id == $advisorIdFilter;
      });
    }

    // Calcular fecha de cierre real para cada OT y agregarla como atributo temporal
    $workOrders = $workOrders->map(function ($workOrder) {
      $workOrder->calculated_closing_date = $this->calculateClosingDate($workOrder);
      return $workOrder;
    });

    // Filtrar OTs que tienen vehículo asociado
    $workOrders = $workOrders->filter(function ($workOrder) {
      return $workOrder->vehicle_id !== null && $workOrder->vehicle !== null;
    });

    // Agrupar por VIN y obtener solo la última OT por vehículo
    $latestWorkOrdersByVin = $workOrders
      ->groupBy('vehicle.vin')
      ->map(function ($ordersGroup) {
        // Ordenar por fecha de cierre calculada descendente y tomar la primera (última OT)
        return $ordersGroup->sortByDesc('calculated_closing_date')->first();
      })
      ->values();

    // Transformar para el reporte
    return $latestWorkOrdersByVin->map(function ($workOrder) {
      return $this->transformClosedWorkOrderForReport($workOrder);
    })->values();
  }

  /**
   * Transforma una Orden de Trabajo Cerrada en el formato del reporte
   *
   * @param ApWorkOrder $workOrder
   * @return array
   */
  private function transformClosedWorkOrderForReport(ApWorkOrder $workOrder): array
  {
    $invoiceTo = $workOrder->invoiceTo;
    $vehicle = $workOrder->vehicle;
    $customer = $vehicle?->customer; // Propietario del vehículo
    $firstItem = $workOrder->items->first();

    // Obtener técnicos únicos consolidados
    $technicians = $this->getConsolidatedTechnicians($workOrder);

    // Usar la fecha de cierre calculada si existe, sino usar official_closing_date
    $closingDate = isset($workOrder->calculated_closing_date) && $workOrder->calculated_closing_date
      ? $workOrder->calculated_closing_date
      : $workOrder->official_closing_date;

    return [
      'taller' => $workOrder->sede?->abreviatura ?? '',
      'cliente_facturado' => $invoiceTo?->full_name ?? '',
      'nombre_cliente' => $invoiceTo?->full_name ?? '',
      'email_cliente' => $invoiceTo?->email ?? '',
      'movil_cliente' => $invoiceTo?->phone ?? '',
      'propietario_nombre' => $customer?->full_name ?? '',
      'propietario_email' => $customer?->email ?? '',
      'propietario_movil' => $customer?->phone ?? '',
      'marca' => $vehicle?->model?->family?->brand?->name ?? '',
      'modelo' => $vehicle?->model?->family?->description ?? '',
      'color' => $vehicle?->color?->description ?? '',
      'kilometraje' => $vehicle?->mileage ?? '',
      'placa' => $vehicle?->plate ?? '',
      'vin' => $vehicle?->vin ?? '',
      'tipo_ingreso' => $workOrder->appointment_planning_id ? 'CON CITA' : 'SIN CITA',
      'numero_ot' => $workOrder->correlative ?? '',
      'tipo_servicio' => $firstItem?->typePlanning?->description ?? '',
      'tipo_operacion' => $vehicle?->typeOperation?->description ?? '',
      'asesor_servicio' => $workOrder->advisor?->nombre_completo ?? '',
      'nombre_tecnico' => $technicians,
      'fecha_apertura_ot' => $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : '',
      'fecha_cierre_ot' => $closingDate ? (is_string($closingDate) ? date('d/m/Y', strtotime($closingDate)) : $closingDate->format('d/m/Y')) : '',
    ];
  }

  /**
   * Calcula la fecha de cierre real de una Orden de Trabajo
   * basándose en los documentos electrónicos o notas internas
   *
   * @param ApWorkOrder $workOrder
   * @return string|null Fecha en formato Y-m-d
   */
  private function calculateClosingDate(ApWorkOrder $workOrder): ?string
  {
    // Prioridad 1: Documento electrónico directo
    $electronicDocument = ElectronicDocument::query()
      ->where('work_order_id', $workOrder->id)
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false)
      ->orderBy('fecha_de_emision', 'desc')
      ->first();

    if ($electronicDocument && $electronicDocument->fecha_de_emision) {
      return is_string($electronicDocument->fecha_de_emision)
        ? $electronicDocument->fecha_de_emision
        : $electronicDocument->fecha_de_emision->format('Y-m-d');
    }

    // Prioridad 2: Nota interna con documento electrónico (MASIVA)
    $internalNoteWithDocument = $workOrder->internalNotes()
      ->whereNotNull('number')
      ->whereHas('electronicDocuments', function ($query) {
        $query->where('anulado', false)
          ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
          ->where('is_advance_payment', false);
      })
      ->with(['electronicDocuments' => function ($query) {
        $query->where('anulado', false)
          ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
          ->where('is_advance_payment', false)
          ->orderBy('fecha_de_emision', 'desc');
      }])
      ->first();

    if ($internalNoteWithDocument && $internalNoteWithDocument->electronicDocuments->isNotEmpty()) {
      $document = $internalNoteWithDocument->electronicDocuments->first();
      if ($document && $document->fecha_de_emision) {
        return is_string($document->fecha_de_emision)
          ? $document->fecha_de_emision
          : $document->fecha_de_emision->format('Y-m-d');
      }
    }

    // Prioridad 3: Nota interna SIN documento (INTERNA_SC, INTERNA_CC)
    $internalNoteWithoutDocument = $workOrder->internalNotes()
      ->whereNotNull('number')
      ->whereDoesntHave('electronicDocuments')
      ->orderBy('created_date', 'desc')
      ->first();

    if ($internalNoteWithoutDocument && $internalNoteWithoutDocument->created_date) {
      return is_string($internalNoteWithoutDocument->created_date)
        ? $internalNoteWithoutDocument->created_date
        : $internalNoteWithoutDocument->created_date->format('Y-m-d');
    }

    // Si no hay ninguna fecha de cierre, retornar null
    return null;
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

<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderResource;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Helpers;
use App\Http\Utils\PriceRounding;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use App\Models\ap\postventa\taller\ApVehicleInspection;
use App\Models\ap\postventa\taller\ApVehicleInspectionDamages;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderItem;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\GeneralMaster;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerSignature;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderService extends BaseService implements BaseServiceInterface
{
  protected WorkOrderLabourService $labourService;
  protected DigitalFileService $digitalFileService;
  protected ExportService $exportService;
  protected InventoryMovementService $inventoryMovementService;

  // Configuración de rutas para archivos
  private const FILE_PATH_DELIVERY_SIGNATURE = '/ap/postventa/taller/entregas/firmas/';
  private const FILE_PATH_DOCUMENTS = '/ap/postventa/taller/ordenes-trabajo/documentos/';

  public function __construct(
    WorkOrderLabourService   $labourService,
    DigitalFileService       $digitalFileService,
    ExportService            $exportService,
    InventoryMovementService $inventoryMovementService
  ) {
    $this->labourService = $labourService;
    $this->digitalFileService = $digitalFileService;
    $this->exportService = $exportService;
    $this->inventoryMovementService = $inventoryMovementService;
  }

  public function list(Request $request)
  {
    $query = ApWorkOrder::with(['items', 'internalNote']);
    return $this->getFilteredResults(
      $query,
      $request,
      ApWorkOrder::filters,
      ApWorkOrder::sorts,
      WorkOrderResource::class
    );
  }

  public function listWithInternalNotes(Request $request)
  {
    $query = ApWorkOrder::with(['items', 'internalNote'])
      ->whereHas('internalNote')
      ->where('final_amount', '>', 0);

    // Filtrar por estado de nota interna si se proporciona
    if ($request->has('internal_note_status')) {
      $status = $request->input('internal_note_status');
      $query->whereHas('internalNote', function ($q) use ($status) {
        $q->where('status', $status);
      });
    }

    return $this->getFilteredResults(
      $query,
      $request,
      ApWorkOrder::filters,
      ApWorkOrder::sorts,
      WorkOrderResource::class
    );
  }

  public function find($id)
  {
    $workOrder = ApWorkOrder::with([
      'appointmentPlanning',
      'vehicle',
      'status',
      'advisor',
      'sede',
      'creator',
      'vehicleInspection.damages',
      'items.typePlanning'
    ])->where('id', $id)->first();

    if (!$workOrder) {
      throw new Exception('Orden de trabajo no encontrada');
    }

    return $workOrder;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Generate correlative
      $data['correlative'] = $this->generateCorrelative();
      $data['status_id'] = ApMasters::OPENING_WORK_ORDER_ID;
      $data['opening_date'] = Carbon::now();
      $data['diagnosis_date'] = Carbon::now();

      //Plate, vin del vehiculo
      $vehicle = Vehicles::find($data['vehicle_id']);
      if ($vehicle) {
        $data['vehicle_plate'] = $vehicle->plate;
        $data['vehicle_vin'] = $vehicle->vin;
      }

      if (isset($data['vehicle_inspection_id']) && isset($data['appointment_planning_id'])) {
        $vehicleIdInspection = ApVehicleInspection::find($data['vehicle_inspection_id'])->createdByWorkOrder->vehicle_id ?? null;
        $vehicleIdAppintment = AppointmentPlanning::find($data['appointment_planning_id'])->ap_vehicle_id ?? null;

        if ($vehicleIdInspection && $vehicleIdAppintment) {
          if ($vehicleIdInspection !== $vehicleIdAppintment) {
            throw new Exception('El vehículo de la inspección no coincide con el vehículo de la cita');
          }
        }

        if ($vehicleIdInspection->createdByWorkOrder->is_delivery) {
          throw new Exception('No se puede crear una orden de trabajo con una recepción de un vehículo que ya fue entregado');
        }
      }

      // Solo validar y guardar el tipo de cambio si la moneda es USD
      if (isset($data['currency_id']) && $data['currency_id'] == TypeCurrency::USD_ID) {
        $exchangeRate = ExchangeRate::where('date', now()->format('Y-m-d'))->first();
        if (!$exchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
        }
        $data['exchange_rate'] = $exchangeRate->rate;
        $data['exchange_rate_id'] = $exchangeRate->id;
      } else {
        // Si es PEN u otra moneda, el tipo de cambio es null
        $data['exchange_rate'] = null;
        $data['exchange_rate_id'] = null;
      }

      // Extract date from estimated_delivery_time and set to estimated_delivery_date
      if (isset($data['estimated_delivery_time'])) {
        $estimatedDeliveryTime = Carbon::parse($data['estimated_delivery_time']);
        $data['estimated_delivery_date'] = $estimatedDeliveryTime->toDateTimeString();
        $data['estimated_delivery_time'] = $estimatedDeliveryTime->format('Y-m-d H:i:s');
      }

      if (!$data['is_recall']) {
        $data['description_recall'] = '';
        $data['type_recall'] = '';
      }

      // Set created_by
      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
        $data['advisor_id'] = auth()->user()->person->id;
      }

      // Set is_taken
      if (isset($data['appointment_planning_id'])) {
        $appointmentPlanning = AppointmentPlanning::find($data['appointment_planning_id']);

        if (!$appointmentPlanning) {
          throw new Exception('Cita no encontrada');
        }

        if ($appointmentPlanning->is_taken) {
          throw new Exception('La cita ya está tomada');
        }

        $appointmentPlanning->update(['is_taken' => true]);
      }

      // Extract items
      $items = $data['items'] ?? [];
      unset($data['items']);

      // Create work order
      $workOrder = ApWorkOrder::create($data);

      // If existe $data['vehicle_inspection_id']
      if (isset($data['vehicle_inspection_id'])) {
        $workOrder->update([
          'status_id' => ApMasters::RECEIVED_WORK_ORDER_ID
        ]);
      }

      // Create items
      $hasPDIService = false;
      if (!empty($items)) {
        foreach ($items as $item) {
          $item['work_order_id'] = $workOrder->id;
          ApWorkOrderItem::create($item);

          // Verificar si es un servicio de PDI
          if (isset($item['type_planning_id']) && $item['type_planning_id'] == TypePlanningWorkOrder::TYPE_PLANNING_PDI_ID) {
            $hasPDIService = true;
          }
        }
      }

      // Si es un servicio de PDI, copiar automáticamente la recepción del vehículo
      if ($hasPDIService) {
        $this->copyReceivingInspectionToVehicleInspection($workOrder, $vehicle);
      }

      // Recalcular totales después de crear los items
      $workOrder->calculateTotals();

      return new WorkOrderResource($workOrder);
    });
  }

  public function show($id)
  {
    $workOrder = $this->find($id);
    $workOrder->load('items', 'orderQuotation.details.product.unitMeasurement', 'labours', 'parts.product.unitMeasurement', 'advancesWorkOrder', 'deductibles.electronicDocument');
    $additionalData['includeCostManHours'] = true;
    return (new WorkOrderResource($workOrder))->additional($additionalData);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      $workOrder->ensureCanBeModified();

      // Extract date from estimated_delivery_time and set to estimated_delivery_date
      if (isset($data['estimated_delivery_time'])) {
        $estimatedDeliveryTime = Carbon::parse($data['estimated_delivery_time']);
        $data['estimated_delivery_date'] = $estimatedDeliveryTime->toDateTimeString();
        $data['estimated_delivery_time'] = $estimatedDeliveryTime->format('Y-m-d H:i:s');
      }

      if (isset($data['is_recall']) && !$data['is_recall']) {
        $data['description_recall'] = '';
        $data['type_recall'] = '';
      }

      // Detectar si cambió el tipo de moneda
      $oldCurrencyId = $workOrder->currency_id;
      $newCurrencyId = $data['currency_id'] ?? $oldCurrencyId;
      $currencyChanged = $oldCurrencyId !== null && $newCurrencyId !== null && $oldCurrencyId != $newCurrencyId;

      // Extract items si están presentes
      $items = null;
      if (isset($data['items'])) {
        $items = $data['items'];
        unset($data['items']);
      }

      // Si existe $data['order_quotation_id'], procesar la asociación de la cotización primero
      if (isset($data['order_quotation_id'])) {
        $quotation = ApOrderQuotations::find($data['order_quotation_id']);
        $vehicle = Vehicles::find($workOrder->vehicle_id);

        if (!$quotation) {
          throw new Exception('Cotización no encontrada');
        }

        if ($vehicle->customer_id === null) {
          throw new Exception('La cotización no tiene un "TITULAR" asociado al vehiculo');
        }

        if ($quotation->is_take_ot) {
          throw new Exception('La cotización ya está tomada por otra orden de trabajo');
        }

        $workOrderCurrencyId = $data['currency_id'] ?? $workOrder->currency_id;

        if ((int)$quotation->currency_id !== (int)$workOrderCurrencyId) {
          throw new Exception('La moneda de la OT y la cotización deben ser iguales');
        }

        // Cuando se asocia una cotización en USD, tomar el tipo de cambio de la cotización
        if ($workOrderCurrencyId == TypeCurrency::USD_ID) {
          $data['exchange_rate'] = $quotation->exchange_rate;
          $data['exchange_rate_id'] = $quotation->exchange_rate_id;
        } else {
          // Si es PEN u otra moneda, el tipo de cambio es null
          $data['exchange_rate'] = null;
          $data['exchange_rate_id'] = null;
        }

        if ($quotation) {
          $quotation->update(['is_take_ot' => 1]);
        }
      } else {
        // Si NO hay cotización asociada, usar el tipo de cambio del día actual
        $currencyId = $data['currency_id'] ?? $workOrder->currency_id;
        if ($currencyId == TypeCurrency::USD_ID) {
          $exchangeRate = ExchangeRate::where('date', now()->format('Y-m-d'))->first();
          if (!$exchangeRate) {
            throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
          }
          $data['exchange_rate'] = $exchangeRate->rate;
          $data['exchange_rate_id'] = $exchangeRate->id;
        } else {
          // Si es PEN u otra moneda, el tipo de cambio es null
          $data['exchange_rate'] = null;
          $data['exchange_rate_id'] = null;
        }
      }

      // Update work order
      $workOrder->update($data);

      // Cargar relaciones necesarias para el cálculo si se asoció una cotización
      if (isset($data['order_quotation_id'])) {
        $workOrder->load(['labours', 'parts', 'orderQuotation.details']);
      }

      // If existe $data['vehicle_inspection_id']
      if (isset($data['vehicle_inspection_id'])) {
        $workOrder->update([
          'status_id' => ApMasters::RECEIVED_WORK_ORDER_ID
        ]);
      }

      // Actualizar items si se enviaron y la orden no está recepcionada
      if ($items !== null) {
        // Eliminar items existentes
        ApWorkOrderItem::where('work_order_id', $workOrder->id)->delete();

        // Crear nuevos items
        if (!empty($items)) {
          foreach ($items as $item) {
            $item['work_order_id'] = $workOrder->id;
            ApWorkOrderItem::create($item);
          }
        }
      }

      // Recalcular totales según el escenario:
      // - Si cambió moneda: handleCurrencyChange recalcula items con factor y luego suma
      // - Si NO cambió moneda: performWorkOrderRecalculation recalcula items con factor=1.0 y luego suma
      if ($currencyChanged) {
        $this->handleCurrencyChange($workOrder, $oldCurrencyId, $newCurrencyId);
      } else {
        $this->performWorkOrderRecalculation($workOrder);
      }

      // Reload relations
      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function authorization(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureNotInStates([ApMasters::CLOSED_WORK_ORDER_ID], 'modificar');

      // Update work order
      $workOrder->update($data);

      // Reload relations
      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function destroy($id)
  {
    $workOrder = $this->find($id);

    $workOrder->ensureNotInStates([ApMasters::CLOSED_WORK_ORDER_ID], 'eliminar');

    if ($workOrder->appointment_planning_id !== null) {
      $appointmentPlanning = AppointmentPlanning::find($workOrder->appointment_planning_id);
      if ($appointmentPlanning) {
        $appointmentPlanning->update(['is_taken' => false]);
      }
    }

    // Liberar la cotización asociada si existe
    if ($workOrder->order_quotation_id !== null) {
      $quotation = ApOrderQuotations::find($workOrder->order_quotation_id);
      if ($quotation) {
        $quotation->update(['is_take_ot' => 0]);

        // Regresar todos los items del detalle de la cotización a status 'pending'
        $quotation->details()->update([
          'status' => ApOrderQuotationDetails::STATUS_PENDING
        ]);
      }
    }

    DB::transaction(function () use ($workOrder) {
      // Delete items first
      ApWorkOrderItem::where('work_order_id', $workOrder->id)->delete();

      // Delete work order
      $workOrder->delete();
    });

    return response()->json(['message' => 'Orden de trabajo eliminada correctamente']);
  }

  private function generateCorrelative(): string
  {
    $year = date('Y');
    $month = date('m');

    $lastWorkOrder = ApWorkOrder::withTrashed('correlative', 'like', "OT-{$year}-{$month}-%")
      ->orderBy('correlative', 'desc')
      ->first();

    if ($lastWorkOrder) {
      $lastNumber = (int)substr($lastWorkOrder->correlative, -4);
      $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
      $newNumber = '0001';
    }

    return "OT-{$year}-{$month}-{$newNumber}";
  }

  public function getPreLiquidationPdf($id)
  {
    $workOrder = $this->find($id);
    $workOrder->load([
      'vehicle.customer',
      'vehicle.model',
      'sede',
      'advisor',
      'labours.worker',
      'parts.product',
      'advancesWorkOrder',
      'vehicleInspection',
      'orderQuotation.details',
      'typeCurrency'
    ]);

    if ($workOrder->invoice_to === null) {
      throw new Exception('La orden de trabajo no tiene un destinatario de factura asignado.');
    }

    $client = $workOrder->invoiceTo;
    $vehicle = $workOrder->vehicle;
    $currencySymbol = $workOrder->typeCurrency->symbol ?? 'S/';

    // Usar el método del modelo para calcular totales
    $totals = $workOrder->getTotalsArray();

    // Obtener items dinámicos (usa el método centralizado del modelo)
    $dynamicItems = $workOrder->getDynamicItemsForInvoicing();
    $labours = $dynamicItems['labours'];
    $parts = $dynamicItems['parts'];

    // Calcular anticipos y saldo using centralized logic (only active advances)
    $activeAdvances = $workOrder->getActiveAdvances();
    $totalAdvances = $activeAdvances->sum('total') ?? 0;
    $remainingBalance = $totals['total_amount'] - $totalAdvances;

    $data = [
      'workOrder' => $workOrder,
      'client' => $client,
      'vehicle' => $vehicle,
      'labours' => $labours,
      'parts' => $parts,
      'advances' => $activeAdvances,
      'currencySymbol' => $currencySymbol,
      'totals' => array_merge($totals, [
        'total_advances' => $totalAdvances,
        'remaining_balance' => $remainingBalance,
      ])
    ];

    // Generar PDF
    $pdf = \PDF::loadView('reports.ap.postventa.taller.pre-liquidation-work-order', $data);
    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream("pre-liquidacion-{$workOrder->correlative}.pdf");
  }

  /**
   * Maneja el cambio de moneda recalculando labours y parts
   */
  private function handleCurrencyChange(ApWorkOrder $workOrder, int $oldCurrencyId, int $newCurrencyId): void
  {
    // Obtener el factor de conversión
    $factor = $this->getConversionFactor($workOrder, $oldCurrencyId, $newCurrencyId);

    // Recalcular labours y parts con el mismo método usado por recalculateTotals(),
    // única fuente de verdad para refrescar hijos de la OT (con o sin cambio de moneda).
    $this->recalculateLabourItems($workOrder, $factor);
    $this->recalculatePartItems($workOrder, $factor);

    // Refrescar modelo y recalcular totales del padre
    $workOrder->refresh();
    $workOrder->calculateTotals();
  }

  private function getConversionFactor(ApWorkOrder $workOrder, int $oldCurrencyId, int $newCurrencyId): float
  {
    // Obtener el tipo de cambio
    $exchangeRate = $this->getExchangeRate($workOrder);

    // De soles a dólares: dividir por tipo de cambio
    if ($oldCurrencyId === TypeCurrency::PEN_ID && $newCurrencyId === TypeCurrency::USD_ID) {
      return 1 / $exchangeRate;
    }

    // De dólares a soles: multiplicar por tipo de cambio
    if ($oldCurrencyId === TypeCurrency::USD_ID && $newCurrencyId === TypeCurrency::PEN_ID) {
      return $exchangeRate;
    }

    // Si son la misma moneda o no reconocida, no hay conversión
    return 1;
  }

  private function getExchangeRate(ApWorkOrder $workOrder): float
  {
    // Si tiene cotización asociada, usar su tipo de cambio
    if ($workOrder->order_quotation_id) {
      $quotation = $workOrder->orderQuotation;
      if ($quotation && $quotation->exchange_rate) {
        return (float)$quotation->exchange_rate;
      }
    }

    // Si no tiene cotización, usar el tipo de cambio actual
    $today = Carbon::now()->format('Y-m-d');
    $exchangeRate = ExchangeRate::where('date', $today)
      ->where('type', ExchangeRate::TYPE_VENTA)
      ->first();

    if (!$exchangeRate) {
      throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy: ' . $today);
    }

    // Actualizar la OT con el tipo de cambio del día que se está aplicando
    $workOrder->update([
      'exchange_rate_id' => $exchangeRate->id,
      'exchange_rate' => $exchangeRate->rate,
    ]);

    return (float)$exchangeRate->rate;
  }

  public function unlinkQuotation(int $id): WorkOrderResource
  {
    return DB::transaction(function () use ($id) {
      $workOrder = $this->find($id);

      $workOrder->ensureNotInStates([
        ApMasters::CANCELED_WORK_ORDER_ID,
        ApMasters::CLOSED_WORK_ORDER_ID,
      ], 'modificar');

      if ($workOrder->order_quotation_id === null) {
        throw new Exception('La orden de trabajo no tiene cotización asociada');
      }

      if ($workOrder->getActiveAdvances()->count() > 0) {
        throw new Exception("Esta cotización no puede ser desasociada. La orden de trabajo {$workOrder->correlative} al que se encuentra asociada ya tiene avances registrados.");
      }

      // Obtener la cotización antes de desasociar
      $quotation = ApOrderQuotations::find($workOrder->order_quotation_id);

      // Desasociar la cotización de la orden de trabajo
      $workOrder->update(['order_quotation_id' => null]);

      // Marcar la cotización como no tomada para que esté disponible
      if ($quotation) {
        $quotation->update([
          'is_take_ot' => 0
        ]);

        // Regresar todos los items del detalle de la cotización a status 'pending'
        $quotation->details()->update([
          'status' => ApOrderQuotationDetails::STATUS_PENDING
        ]);
      }

      // Desasociar la cotización de la orden de trabajo
      $workOrder->update(['order_quotation_id' => null]);

      // Actualizar allow_remove_associated_quote a false
      $workOrder->update([
        'allow_remove_associated_quote' => false,
      ]);

      // Recalcular totales usando el método del modelo (detecta automáticamente que ya no tiene cotización)
      $workOrder->load(['labours', 'parts']);
      $workOrder->calculateTotals();

      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function invoiceTo(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      if ($workOrder->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede modificar el destinatario de factura porque ya se han registrado anticipos para esta orden de trabajo');
      }

      // Validar estado CANCELADO siempre
      $workOrder->ensureNotInStates([
        ApMasters::CANCELED_WORK_ORDER_ID,
      ], 'modificar');

      // Validación especial para estado CERRADO
      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        // Cargar la relación con la nota interna si existe
        $workOrder->load('internalNote');

        // Si tiene nota interna relacionada
        if ($workOrder->internalNote) {
          // Si la nota interna ya fue facturada (status = 'invoiced'), no permitir
          if ($workOrder->internalNote->status === ApInternalNote::STATUS_INVOICED) {
            throw new Exception('No se puede modificar el destinatario de factura porque la factura de la nota interna ya fue emitida');
          }
          // Si status = 'pending', permitir continuar (excepción a la regla)
        } else {
          // Si NO tiene nota interna, aplicar validación normal (no permitir)
          throw new Exception('No se puede modificar en una orden de trabajo cerrada');
        }
      }

      // Update work order
      $workOrder->update($data);

      return new WorkOrderResource($workOrder);
    });
  }

  public function updatePickupPerson(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      // Validar que la orden pueda ser modificada
      $workOrder->ensureCanBeModified();

      // Actualizar solo los campos de la persona que recoge
      $workOrder->update([
        'num_doc_pickup' => $data['num_doc_pickup'],
        'full_pickup_name' => $data['full_pickup_name'],
        'phone_pickup' => $data['phone_pickup'],
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function changeAdvisor(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      // Validar que la orden pueda ser modificada
      $workOrder->ensureCanBeModified();

      // Actualizar el asesor
      $workOrder->update([
        'advisor_id' => $data['advisor_id'],
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function generateDelivery(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      if ($workOrder->is_delivery) {
        throw new Exception('La orden de trabajo ya tiene una entrega generada');
      }

      //$workOrder->ensureInStates([ApMasters::CLOSED_WORK_ORDER_ID], 'generar la entrega');

      // Extraer firma en base64 del array
      $deliverySignature = $data['signature_delivery'] ?? null;

      // Procesar los seguimientos: convertir días y horas a fechas absolutas
      $actualDeliveryDate = Carbon::parse($data['actual_delivery_date']);
      $followUps = [];

      foreach ($data['follow_ups'] as $followUp) {
        $days = (int)$followUp['days'];

        $followUpDateTimeStart = $actualDeliveryDate->copy()
          ->addDays($days)
          ->setTimeFromTimeString($followUp['time_start']);

        $followUpDateTimeEnd = $actualDeliveryDate->copy()
          ->addDays($days)
          ->setTimeFromTimeString($followUp['time_end']);

        $followUps[] = [
          'days' => $days,
          'time_start' => $followUp['time_start'],
          'time_end' => $followUp['time_end'],
          'scheduled_datetime_start' => $followUpDateTimeStart->format('Y-m-d H:i:s'),
          'scheduled_datetime_end' => $followUpDateTimeEnd->format('Y-m-d H:i:s'),
          'completed' => false,
        ];
      }

      // Actualizar la orden de trabajo
      $workOrder->update([
        'actual_delivery_date' => $actualDeliveryDate->format('Y-m-d H:i:s'),
        'is_delivery' => true,
        'delivery_by' => auth()->check() ? auth()->user()->id : null,
        'post_service_follow_up' => json_encode($followUps),
        'notes_delivery' => $data['notes_delivery'] ?? null,
      ]);

      // Procesar y guardar firma si existe
      if ($deliverySignature) {
        $this->processDeliverySignature($workOrder, $deliverySignature);
      }

      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  public function generateDeliveryReport($id)
  {
    // Obtener la orden de trabajo con todas las relaciones necesarias
    $workOrder = $this->find($id);
    $workOrder->load([
      'plannings.worker',
      'vehicle.model.family.brand',
      'vehicle.customer',
      'appointmentPlanning.advisor',
      'deliveryBy',
      'items.typePlanning',
      'items.typeOperation'
    ]);

    $inspection = $workOrder->vehicleInspection;

    if (!$inspection) {
      throw new Exception('No se encontró la inspección del vehículo');
    }

    $vehicle = $workOrder->vehicle;
    $customer = $vehicle->customer;
    $advisor = $workOrder->advisor;

    // Obtener firma del asesor desde WorkerSignature
    $advisorSignature = null;
    if ($advisor) {
      $workerSignature = WorkerSignature::where('worker_id', $advisor->id)->first();
      if ($workerSignature && $workerSignature->signature_url) {
        $advisorSignature = Helpers::convertUrlToBase64($workerSignature->signature_url);
      }
    }

    // Obtener firma del coordinador de taller de la sede de la OT
    $workshopCoordinator = Worker::where('sede_id', $workOrder->sede_id)
      ->whereIn('cargo_id', Position::WORKSHOP_COORDINATOR)
      ->where('status_id', 22)
      ->first();

    $workshopCoordinatorSignature = null;
    if ($workshopCoordinator) {
      $coordinatorSignature = WorkerSignature::where('worker_id', $workshopCoordinator->id)->first();
      if ($coordinatorSignature && $coordinatorSignature->signature_url) {
        $workshopCoordinatorSignature = Helpers::convertUrlToBase64($coordinatorSignature->signature_url);
      }
    }

    // Convertir firma de recepción del cliente a base64 si existe
    $customerSignatureReception = null;
    if ($inspection->customer_signature_url) {
      $customerSignatureReception = Helpers::convertUrlToBase64($inspection->customer_signature_url);
    }

    // Convertir firma de entrega del cliente a base64 si existe
    $customerSignatureDelivery = null;
    if ($workOrder->signature_delivery_url) {
      $customerSignatureDelivery = Helpers::convertUrlToBase64($workOrder->signature_delivery_url);
    }

    // Convertir fotos de daños a base64
    $damagesWithPhotos = $inspection->damages->map(function ($damage) {
      if ($damage->photo_url) {
        $damage->photo_base64 = Helpers::convertUrlToBase64($damage->photo_url);
      }
      return $damage;
    });

    // Preparar lista de checks del inventario
    $inventoryChecks = [
      'dirty_unit' => 'UNIDAD SUCIA',
      'unit_ok' => 'UNIDAD OK',
      'title_deed' => 'TARJETA DE PROPIEDAD',
      'soat' => 'SOAT',
      'moon_permits' => 'PERMISOS LUNETA',
      'service_card' => 'TARJETA DE SERVICIO',
      'owner_manual' => 'MANUAL DEL PROPIETARIO',
      'key_ring' => 'LLAVERO',
      'wheel_lock' => 'SEGURO DE RUEDA',
      'safe_glasses' => 'GAFAS DE SEGURIDAD',
      'radio_mask' => 'MÁSCARA DE RADIO',
      'lighter' => 'ENCENDEDOR',
      'floors' => 'PISOS',
      'seat_cover' => 'CUBRE ASIENTOS',
      'quills' => 'PLUMILLAS',
      'antenna' => 'ANTENA',
      'glasses_wheel' => 'VASOS RUEDA',
      'emblems' => 'EMBLEMAS',
      'spare_tire' => 'LLANTA DE REPUESTO',
      'fluid_caps' => 'TAPAS DE FLUIDOS',
      'tool_kit' => 'KIT DE HERRAMIENTAS',
      'jack_and_lever' => 'GATO Y PALANCA',
    ];

    // Preparar datos para la vista
    $data = [
      'inspection' => $inspection,
      'workOrder' => $workOrder,
      'vehicle' => $vehicle,
      'customer' => $customer,
      'advisor' => $advisor,
      'advisorPhone' => $advisor ? $advisor->cel_personal : '',
      'sede' => $workOrder->sede,
      'status' => $workOrder->status,
      'items' => $workOrder->items,
      'damages' => $damagesWithPhotos,
      'inventoryChecks' => $inventoryChecks,
      'customerSignatureReception' => $customerSignatureReception,
      'customerSignatureDelivery' => $customerSignatureDelivery,
      'advisorSignature' => $advisorSignature,
      'workshopCoordinator' => $workshopCoordinator,
      'workshopCoordinatorSignature' => $workshopCoordinatorSignature,
      'appointmentPlanning' => $workOrder->appointmentPlanning ?? null,
      'plannings' => $workOrder->plannings ?? collect(),
      'isGuarantee' => $workOrder->is_guarantee ?? false,
      'isRecall' => $workOrder->is_recall ?? false,
      'descriptionRecall' => $workOrder->description_recall ?? '',
      'typeRecall' => $workOrder->type_recall ?? '',
    ];

    // Generar PDF
    $pdf = \PDF::loadView('reports.ap.postventa.taller.delivery-report', $data);
    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream("reporte-entrega-{$workOrder->correlative}.pdf");
  }

  public function getVehicleHistory(int $vehicleId): array
  {
    // Verificar que el vehículo existe
    $vehicle = Vehicles::find($vehicleId);
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }

    // Obtener todas las órdenes de trabajo del vehículo ordenadas por fecha de apertura descendente
    $workOrders = ApWorkOrder::where('vehicle_id', $vehicleId)
      ->with([
        'status',
        'sede',
        'advisor',
        'plannings' => function ($query) {
          $query->with('worker')
            ->whereNotNull('actual_start_datetime')
            ->orderBy('actual_start_datetime', 'asc');
        },
        'parts.product'
      ])
      ->orderBy('opening_date', 'desc')
      ->get();

    // Formatear los datos para la respuesta
    $history = $workOrders->map(function ($workOrder) {
      return [
        'correlative' => $workOrder->correlative,
        'opening_date' => $workOrder->opening_date?->format('Y-m-d'),
        'estimated_delivery_date' => $workOrder->estimated_delivery_date?->format('Y-m-d'),
        'actual_delivery_date' => $workOrder->actual_delivery_date?->format('Y-m-d H:i:s'),
        'diagnosis_date' => $workOrder->diagnosis_date?->format('Y-m-d H:i:s'),
        'status' => $workOrder->status?->description,
        'sede' => $workOrder->sede?->abreviatura,
        'advisor' => $workOrder->advisor?->nombre_completo,
        'is_guarantee' => $workOrder->is_guarantee,
        'is_recall' => $workOrder->is_recall,
        'description_recall' => $workOrder->description_recall,
        'type_recall' => $workOrder->type_recall,
        'observations' => $workOrder->observations,
        'works_performed' => $workOrder->plannings->map(function ($planning) {
          return [
            'description' => $planning->description,
            'actual_hours' => $planning->actual_hours,
            'worker' => $planning->worker?->nombre_completo,
            'actual_start_datetime' => $planning->actual_start_datetime?->format('Y-m-d H:i:s'),
            'actual_end_datetime' => $planning->actual_end_datetime?->format('Y-m-d H:i:s'),
            'status' => $planning->status,
          ];
        })->values()->toArray(),
        'parts_used' => $workOrder->parts->map(function ($part) {
          return [
            'description' => $part->product?->description ?? $part->product?->name,
            'quantity' => $part->quantity_used,
          ];
        })->values()->toArray()
      ];
    });

    return [
      'vehicle_id' => $vehicleId,
      'vehicle_plate' => $vehicle->plate,
      'vehicle_vin' => $vehicle->vin,
      'data' => $history->values()->toArray()
    ];
  }

  public function generateInternalNote($id)
  {
    DB::beginTransaction();

    try {
      $workOrder = $this->find($id);
      $validateDocument = $workOrder->items->first()?->typePlanning->type_document;
      $validateReception = $workOrder->items->first()?->typePlanning->validate_receipt;
      $validateLabor = $workOrder->items->first()?->typePlanning->validate_labor;

      if ($validateReception && $workOrder->status_id === ApMasters::OPENING_WORK_ORDER_ID) {
        throw new Exception('La OT se encuentra en estado abierto, debe recepcionar la orden de trabajo para iniciar el trabajo y luego generar la nota interna');
      }

      if ($validateLabor) {
        if ($workOrder->status_id === ApMasters::RECEIVED_WORK_ORDER_ID) {
          throw new Exception('Debe asignar un técnico para iniciar el trabajo y luego generar la nota interna');
        }

        if ($workOrder->status_id === ApMasters::AT_WORK_WORK_ORDER_ID) {
          throw new Exception('La OT se encuentra en trabajo, debe esperar a que el técnico finalice su trabajo para generar una nota interna');
        }
      }

      if ($validateDocument !== TypePlanningWorkOrder::INTERNA) {
        throw new Exception('Solo se pueden generar notas internas para órdenes de trabajo con planificación de tipo "INTERNA"');
      }

      if ($workOrder->invoice_to === null) {
        throw new Exception('La orden de trabajo no tiene un destinatario de factura asignado.');
      }

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede generar una nota interna para una orden de trabajo cerrada');
      }

      //create internal note
      $internalNote = ApInternalNote::create([
        'work_order_id' => $workOrder->id,
        'created_date' => now(),
      ]);

      //Close work order with internal note
      $workOrder->update([
        'status_id' => ApMasters::CLOSED_WORK_ORDER_ID,
      ]);

      // Generar ajuste de salida de inventario si la OT tiene repuestos
      $this->processInventoryAdjustmentForInternalNote($workOrder, $internalNote);

      DB::commit();

      return response()->json([
        'message' => 'Nota interna generada y orden de trabajo cerrada correctamente',
        'internal_note_id' => $internalNote->id,
      ]);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function generatePDIForVehicle($id)
  {
    DB::beginTransaction();

    try {
      $vehicle = Vehicles::find($id);

      //1. Verificamos que exista el vehiculo
      if (!$vehicle) {
        throw new Exception('Vehículo no encontrado');
      }

      //2. Verificamos si ya existe un registro de PDI para este vehículo
      $existingPDI = ApWorkOrder::where('vehicle_id', $id)
        ->whereHas('items', function ($query) {
          $query->where('type_planning_id', TypePlanningWorkOrder::TYPE_PLANNING_PDI_ID);
        })
        ->exists();

      if ($existingPDI) {
        throw new Exception('Ya existe un registro de PDI para este vehículo');
      }

      $hasVehiclePdi = $vehicle->has_pdi;
      $typeCurrency = $hasVehiclePdi ? TypeCurrency::PEN_ID : TypeCurrency::USD_ID;

      // 2. Copiamos la inspección de recepción a la inspección del vehículo
      $shippingGuide = $vehicle->shippingGuideReceiving;

      //validamos que exista la recepcion
      if (!$shippingGuide?->receivingInspection) {
        throw new Exception('El vehículo no tiene una guía de remisión de recepción asociada');
      }

      // Preparar datos de tipo de cambio según la moneda
      $exchangeRateData = [];
      if ($typeCurrency == TypeCurrency::USD_ID) {
        $exchangeRate = ExchangeRate::where('date', now()->format('Y-m-d'))->first();
        if (!$exchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
        }
        $exchangeRateData = [
          'exchange_rate' => $exchangeRate->rate,
          'exchange_rate_id' => $exchangeRate->id,
        ];
      } else {
        $exchangeRateData = [
          'exchange_rate' => null,
          'exchange_rate_id' => null,
        ];
      }

      //3. Creamos la cabecera de la OT
      $apWorkOrder = ApWorkOrder::create(array_merge([
        'correlative' => $this->generateCorrelative(),
        'vehicle_id' => $vehicle->id,
        'currency_id' => $typeCurrency,
        'vehicle_plate' => $vehicle->plate,
        'vehicle_vin' => $vehicle->vin,
        'status_id' => ApMasters::OPENING_WORK_ORDER_ID,
        'advisor_id' => auth()->user()->person->id,
        'invoice_to' => $hasVehiclePdi ? BusinessPartners::AUTOMOTORES_PAKATNAMU_ID : $shippingGuide->transmitter_id,
        'sede_id' => $vehicle->warehouse ? $vehicle->warehouse->sede_id : null,
        'opening_date' => now()->format('Y-m-d'),
        'diagnosis_date' => now()->format('Y-m-d'),
        'is_delivery' => true,
        'delivery_by' => auth()->id(),
        'created_by' => auth()->id(),
      ], $exchangeRateData));

      //4. Generamos el detalle de la OT
      ApWorkOrderItem::create([
        'group_number' => 1,
        'work_order_id' => $apWorkOrder->id,
        'type_planning_id' => TypePlanningWorkOrder::TYPE_PLANNING_PDI_ID,
        'type_operation_id' => ApMasters::OP_TYPE_APPT_PDI_ID,
        'description' => 'SERVICIO DE PDI',
      ]);

      // Calculamos la tarifa
      if ($hasVehiclePdi) {
        if ($vehicle->is_heavy) {
          $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_VP_ID)->value;
        } else {
          $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_VL_ID)->value;
        }
      } else {
        $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_PDI_DERCO_ID)->value;
      }

      // 5. Generamos la mano de obra de la OT
      $labourData = [
        'description' => 'SERVICIO DE MANO DE OBRA PDI',
        'time_spent' => 1.0, // 1 hora por defecto
        'hourly_rate' => (float)$hourly_rate,
        'work_order_id' => $apWorkOrder->id,
        'worker_id' => auth()->user()->person->id,
        'group_number' => 1,
      ];

      // 6. Guardamos la mano de obra usando el servicio para que se actualicen los totales correctamente
      $this->labourService->store($labourData);

      if ($shippingGuide && $shippingGuide->receivingInspection) {
        $receivingInspection = $shippingGuide->receivingInspection;

        // Crear ApVehicleInspection copiando datos de ApReceivingInspection
        $vehicleInspection = ApVehicleInspection::create([
          'ap_work_order_id' => $apWorkOrder->id,
          'photo_front_url' => $receivingInspection->photo_front_url,
          'photo_back_url' => $receivingInspection->photo_back_url,
          'photo_left_url' => $receivingInspection->photo_left_url,
          'photo_right_url' => $receivingInspection->photo_right_url,
          'general_observations' => $receivingInspection->general_observations,
          'inspected_by' => $receivingInspection->inspected_by,
          'inspection_date' => now(),
          'mileage' => 0,
          'fuel_level' => '0',
          'oil_level' => '0',
        ]);

        // Copiar los damages de ApReceivingInspectionDamage a ApVehicleInspectionDamages
        foreach ($receivingInspection->damages as $damage) {
          ApVehicleInspectionDamages::create([
            'vehicle_inspection_id' => $vehicleInspection->id,
            'damage_type' => $damage->damage_type,
            'x_coordinate' => $damage->x_coordinate,
            'y_coordinate' => $damage->y_coordinate,
            'description' => $damage->description,
            'photo_url' => $damage->photo_url,
          ]);
        }

        // Actualizar la OT con el vehicle_inspection_id
        $apWorkOrder->update([
          'vehicle_inspection_id' => $vehicleInspection->id,
        ]);
      }

      $apWorkOrder->update([
        'status_id' => ApMasters::RECEIVED_WORK_ORDER_ID,
      ]);

      DB::commit();

      return response()->json([
        'message' => 'Orden de trabajo PDI generada correctamente',
        'vehicle_id' => $vehicle->id,
      ]);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function generateInstallationAccessories($id)
  {
    DB::beginTransaction();

    try {
      $vehicle = Vehicles::find($id);

      //1. Verificamos que exista el vehiculo
      if (!$vehicle) {
        throw new Exception('Vehículo no encontrado');
      }

      //2. Verificamos si ya existe una OT de instalación de accesorios ABIERTA para este vehículo
      $existingOpenWO = ApWorkOrder::where('vehicle_id', $id)
        ->whereHas('items', function ($query) {
          $query->where('type_planning_id', TypePlanningWorkOrder::TYPE_PLANNING_INST_ACCESORIOS_ID);
        })
        ->where('status_id', '!=', ApMasters::CLOSED_WORK_ORDER_ID)
        ->exists();

      if ($existingOpenWO) {
        throw new Exception('Ya existe una orden de trabajo de instalación de accesorios abierta para este vehículo');
      }

      $hasVehiclePdi = $vehicle->has_pdi;
      $typeCurrency = $hasVehiclePdi ? TypeCurrency::PEN_ID : TypeCurrency::USD_ID;

      // Obtenemos la guía de recepción del vehículo (puede no existir para accesorios de posventa)
      $shippingGuide = $vehicle->shippingGuideReceiving;

      // Preparar datos de tipo de cambio según la moneda
      $exchangeRateData = [];
      if ($typeCurrency == TypeCurrency::USD_ID) {
        $exchangeRate = ExchangeRate::where('date', now()->format('Y-m-d'))->first();
        if (!$exchangeRate) {
          throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
        }
        $exchangeRateData = [
          'exchange_rate' => $exchangeRate->rate,
          'exchange_rate_id' => $exchangeRate->id,
        ];
      } else {
        $exchangeRateData = [
          'exchange_rate' => null,
          'exchange_rate_id' => null,
        ];
      }

      //3. Creamos la cabecera de la OT
      $apWorkOrder = ApWorkOrder::create(array_merge([
        'correlative' => $this->generateCorrelative(),
        'vehicle_id' => $vehicle->id,
        'currency_id' => $typeCurrency,
        'vehicle_plate' => $vehicle->plate,
        'vehicle_vin' => $vehicle->vin,
        'status_id' => ApMasters::OPENING_WORK_ORDER_ID,
        'advisor_id' => auth()->user()->person->id,
        'invoice_to' => $hasVehiclePdi
          ? BusinessPartners::AUTOMOTORES_PAKATNAMU_ID
          : ($shippingGuide?->transmitter_id ?? BusinessPartners::AUTOMOTORES_PAKATNAMU_ID),
        'sede_id' => $vehicle->warehouse ? $vehicle->warehouse->sede_id : null,
        'opening_date' => now()->format('Y-m-d'),
        'diagnosis_date' => now()->format('Y-m-d'),
        'is_delivery' => true,
        'delivery_by' => auth()->id(),
        'created_by' => auth()->id(),
      ], $exchangeRateData));

      //4. Generamos el detalle de la OT
      ApWorkOrderItem::create([
        'group_number' => 1,
        'work_order_id' => $apWorkOrder->id,
        'type_planning_id' => TypePlanningWorkOrder::TYPE_PLANNING_INST_ACCESORIOS_ID,
        'type_operation_id' => ApMasters::OP_TYPE_APPT_ACC_INSTALL_ID,
        'description' => 'SERVICIO DE INSTALACIÓN DE ACCESORIOS',
      ]);

      // Calculamos la tarifa
      if ($hasVehiclePdi) {
        if ($vehicle->is_heavy) {
          $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_VP_ID)->value;
        } else {
          $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_VL_ID)->value;
        }
      } else {
        $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_PDI_DERCO_ID)->value;
      }

      // 5. Generamos la mano de obra de la OT
      $labourData = [
        'description' => 'SERVICIO DE MANO DE OBRA PDI',
        'time_spent' => 1.0, // 1 hora por defecto
        'hourly_rate' => (float)$hourly_rate,
        'work_order_id' => $apWorkOrder->id,
        'worker_id' => auth()->user()->person->id,
        'group_number' => 1,
      ];

      // 6. Guardamos la mano de obra usando el servicio para que se actualicen los totales correctamente
      $this->labourService->store($labourData);

      // Copiar inspección de recepción si existe (opcional para el caso de accesorios de posventa)
      if ($shippingGuide && $shippingGuide->receivingInspection) {
        $receivingInspection = $shippingGuide->receivingInspection;

        // Crear ApVehicleInspection copiando datos de ApReceivingInspection
        $vehicleInspection = ApVehicleInspection::create([
          'ap_work_order_id' => $apWorkOrder->id,
          'photo_front_url' => $receivingInspection->photo_front_url,
          'photo_back_url' => $receivingInspection->photo_back_url,
          'photo_left_url' => $receivingInspection->photo_left_url,
          'photo_right_url' => $receivingInspection->photo_right_url,
          'general_observations' => $receivingInspection->general_observations,
          'inspected_by' => $receivingInspection->inspected_by,
          'inspection_date' => now(),
          'mileage' => 0,
          'fuel_level' => '0',
          'oil_level' => '0',
        ]);

        // Copiar los damages de ApReceivingInspectionDamage a ApVehicleInspectionDamages
        foreach ($receivingInspection->damages as $damage) {
          ApVehicleInspectionDamages::create([
            'vehicle_inspection_id' => $vehicleInspection->id,
            'damage_type' => $damage->damage_type,
            'x_coordinate' => $damage->x_coordinate,
            'y_coordinate' => $damage->y_coordinate,
            'description' => $damage->description,
            'photo_url' => $damage->photo_url,
          ]);
        }

        // Actualizar la OT con el vehicle_inspection_id
        $apWorkOrder->update([
          'vehicle_inspection_id' => $vehicleInspection->id,
        ]);
      }

      $apWorkOrder->update([
        'status_id' => ApMasters::RECEIVED_WORK_ORDER_ID,
      ]);

      DB::commit();

      return response()->json([
        'message' => 'Orden de trabajo de instalación de accesorios generada correctamente',
        'vehicle_id' => $vehicle->id,
        'work_order_id' => $apWorkOrder->id,
      ]);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function getByIds(array $ids)
  {
    $workOrders = ApWorkOrder::with(['internalNote', 'items', 'labours', 'parts'])
      ->whereIn('id', $ids)
      ->get();

    $uniqueCurrencies = $workOrders->pluck('currency_id')->unique();

    if ($uniqueCurrencies->count() > 1) {
      throw new \Exception('Todas las órdenes deben tener la misma moneda');
    }

    $uniqueSuppliers = $workOrders->pluck('invoice_to')->unique();

    if ($uniqueSuppliers->count() > 1) {
      throw new \Exception('Todas las órdenes deben tener el mismo destinatario de factura');
    }

    return WorkOrderResource::collection($workOrders);
  }

  /**
   * Cambiar el tipo de moneda de una orden de trabajo
   */
  public function changeCurrency(mixed $data): WorkOrderResource
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::with(['internalNote', 'advancesWorkOrder'])->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      // Validar estado CANCELADO siempre
      $workOrder->ensureNotInStates([
        ApMasters::CANCELED_WORK_ORDER_ID,
      ], 'modificar');

      // Validación especial para estado CERRADO
      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        // Cargar la relación con la nota interna si existe
        $workOrder->load('internalNote');

        // Si tiene nota interna relacionada
        if ($workOrder->internalNote) {
          // Si la nota interna ya fue facturada (status = 'invoiced'), no permitir
          if ($workOrder->internalNote->status === ApInternalNote::STATUS_INVOICED) {
            throw new Exception('No se puede cambiar la moneda porque la factura de la nota interna ya fue emitida');
          }
          // Si status = 'pending', permitir continuar (excepción a la regla)
        } else {
          // Si NO tiene nota interna, aplicar validación normal (no permitir)
          throw new Exception('No se puede modificar en una orden de trabajo cerrada');
        }
      }

      // Validar que no tenga cotización asociada
      if ($workOrder->order_quotation_id !== null) {
        throw new Exception('No se puede cambiar la moneda de una orden de trabajo que tiene cotización asociada');
      }

      // Validar que no tenga anticipos activos
      if ($workOrder->getActiveAdvances()->count() > 0) {
        throw new Exception("Esta orden de trabajo no puede cambiar de moneda. Ya se han registrado anticipos para esta orden de trabajo.");
      }

      // Verificar que la moneda sea diferente
      $oldCurrencyId = $workOrder->currency_id;
      $newCurrencyId = $data['currency_id'];

      if ($oldCurrencyId == $newCurrencyId) {
        throw new Exception('La moneda seleccionada es la misma que la actual');
      }

      // Actualizar la moneda
      $workOrder->update(['currency_id' => $newCurrencyId]);

      // Recalcular labours y parts con el nuevo tipo de cambio
      $this->handleCurrencyChange($workOrder, $oldCurrencyId, $newCurrencyId);

      // Recalcular totales de la orden de trabajo
      $workOrder->calculateTotals();

      // Recargar relaciones
      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning',
        'typeCurrency'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Enviar a facturar OT
   */
  public function sendToFinished(mixed $data): WorkOrderResource
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::with(['labours', 'parts.deliveries', 'items.typePlanning', 'discountRequests'])->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $validateLabor = $workOrder->shouldValidateLabor();
      $validateReceipt = $workOrder->shouldValidateReceipt();

      if ($validateReceipt && $workOrder->vehicleInspection === null) {
        throw new Exception('La orden de trabajo debe tener una recepción');
      }

      // Validar que no haya descuentos pendientes en repuestos o mano de obra
      $pendingDiscounts = $workOrder->discountRequests()
        ->where('status', DiscountRequestsWorkOrder::STATUS_PENDING)
        ->whereIn('part_labour_model', [ApWorkOrderParts::class, WorkOrderLabour::class])
        ->exists();

      if ($pendingDiscounts) {
        throw new Exception('No se puede finalizar la orden de trabajo. Hay solicitudes de descuento pendientes de aprobación. Por favor, apruebe o rechace las solicitudes antes de continuar.');
      }

      $laboursWithWorker = $workOrder->plannings->filter(function ($labour) {
        return $labour->worker_id !== null && $labour->deleted_at === null;
      });

      if ($validateLabor && $laboursWithWorker->count() === 0) {
        throw new Exception('La orden de trabajo debe tener un operario asignado.');
      }

      // Verificar si hay planificaciones activas (no canceladas ni eliminadas)
      $activePlannings = $workOrder->plannings()
        ->where('status', '!=', 'canceled')
        ->whereNull('deleted_at')
        ->count();

      // Validate that all parts are fully delivered and received by technician if work order has parts
      // SOLO si hay planificaciones activas
      if ($workOrder->parts->count() > 0 && $validateLabor && $activePlannings > 0) {
        $partsNotFullyDelivered = [];
        $partsNotReceivedByTechnician = [];

        foreach ($workOrder->parts as $part) {
          // Calculate total delivered quantity for this part (excluding soft deleted deliveries)
          $totalDelivered = $part->deliveries
            ->whereNull('deleted_at')
            ->sum('delivered_quantity');

          // Calculate total received quantity by technician (only deliveries confirmed as received)
          $totalReceived = $part->deliveries
            ->whereNull('deleted_at')
            ->where('is_received', true)
            ->sum('delivered_quantity');

          // Get pending deliveries (not yet received by technician)
          $pendingReceipt = $part->deliveries
            ->whereNull('deleted_at')
            ->where('is_received', false)
            ->sum('delivered_quantity');

          // Compare with quantity_used
          $quantityUsed = (float)$part->quantity_used;
          $totalDelivered = (float)$totalDelivered;
          $totalReceived = (float)$totalReceived;
          $pendingReceipt = (float)$pendingReceipt;

          $productName = $part->product->name ?? "Producto ID: {$part->product_id}";

          // If not fully delivered, add to list
          if ($totalDelivered < $quantityUsed) {
            $partsNotFullyDelivered[] = sprintf(
              '%s (Usado: %.2f, Entregado: %.2f, Pendiente de entrega: %.2f)',
              $productName,
              $quantityUsed,
              $totalDelivered,
              $quantityUsed - $totalDelivered
            );
          }

          // If not fully received by technician, add to list
          if ($totalReceived < $quantityUsed) {
            $partsNotReceivedByTechnician[] = sprintf(
              '%s (Usado: %.2f, Recibido: %.2f, Pendiente de confirmar recepción: %.2f)',
              $productName,
              $quantityUsed,
              $totalReceived,
              $pendingReceipt
            );
          }
        }

        if (count($partsNotFullyDelivered) > 0) {
          throw new Exception(
            'No se puede finalizar la orden de trabajo. Los siguientes repuestos no han sido entregados en su totalidad: ' .
              implode('; ', $partsNotFullyDelivered)
          );
        }

        if (count($partsNotReceivedByTechnician) > 0) {
          throw new Exception(
            'No se puede finalizar la orden de trabajo. Los siguientes repuestos no han sido confirmados como recibidos por el técnico: ' .
              implode('; ', $partsNotReceivedByTechnician)
          );
        }
      }

      // Validación de estados
      $workOrder->ensureCanBeModified();

      // Recalcular totales de la OT antes de finalizar para asegurar
      // que los montos lleguen correctamente a caja
      $this->performWorkOrderRecalculation($workOrder);

      $workOrder->update([
        'status_id' => ApMasters::FINISHED_WORK_ORDER_ID,
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Anular/Cancelar una orden de trabajo
   */
  public function cancel($data): WorkOrderResource
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::with(['advancesWorkOrder'])->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureNotInStates([
        ApMasters::CANCELED_WORK_ORDER_ID,
        ApMasters::CLOSED_WORK_ORDER_ID,
      ], 'cancelar');

      if ($workOrder->getActiveAdvances()->count() > 0) {
        throw new Exception('No se puede anular una orden de trabajo que tiene anticipos registrados');
      }

      if ($workOrder->status_id === ApMasters::AT_WORK_WORK_ORDER_ID) {
        throw new Exception('No se puede anular una orden de trabajo que se encuentra en trabajo');
      } else {
        $hasPlanning = $workOrder->plannings()->where('status', '!=', 'canceled')->count();
        if ($hasPlanning > 0) {
          throw new Exception("No se puede anular una orden de trabajo que tiene planificación de trabajo registrada. Encontrados: {$hasPlanning}");
        }
      }

      $workOrder->update([
        'status_id' => ApMasters::CANCELED_WORK_ORDER_ID,
        'discarded_at' => now(),
        'discarded_by' => auth()->id(),
        'discard_reason_id' => $data['discard_reason_id'],
        'discarded_note' => $data['discarded_note'] ?? null,
      ]);

      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Revertir el estado de la orden de trabajo desde finalizada
   */
  public function revertir(mixed $data): WorkOrderResource
  {
    return DB::transaction(function () use ($data) {
      $workOrder = ApWorkOrder::with(['items.typePlanning'])->find($data['id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      if ($workOrder->is_delivery) {
        throw new Exception('No se puede revertir una orden de trabajo que ya ha sido entregada al cliente');
      }

      if ($workOrder->status_id !== ApMasters::FINISHED_WORK_ORDER_ID) {
        throw new Exception('Solo se puede revertir una orden de trabajo que está en estado finalizada');
      }

      $validateReception = $workOrder->shouldValidateReceipt();
      $validateLabor = $workOrder->shouldValidateLabor();

      // Determinar el estado al que debe retroceder
      if ($validateLabor) {
        $newStatusId = ApMasters::END_WORK_WORK_ORDER_ID;
      } elseif ($validateReception) {
        $newStatusId = ApMasters::RECEIVED_WORK_ORDER_ID;
      } else {
        $newStatusId = ApMasters::OPENING_WORK_ORDER_ID;
      }

      $workOrder->update([
        'status_id' => $newStatusId,
      ]);

      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Export work orders to Excel
   */
  public function exportWorkOrders(Request $request)
  {
    $filters = [];

    // Apply filters from request
    if ($request->filled('advisor_id')) {
      $filters[] = [
        'column' => 'advisor_id',
        'operator' => '=',
        'value' => $request->advisor_id
      ];
    }

    if ($request->filled('sede_id')) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $request->sede_id
      ];
    }

    if ($request->filled('status_id')) {
      $filters[] = [
        'column' => 'status_id',
        'operator' => 'in_or_equal',
        'value' => $request->status_id
      ];
    }

    if ($request->filled('opening_date')) {
      $filters[] = [
        'column' => 'opening_date',
        'operator' => 'date_between',
        'value' => $request->opening_date
      ];
    }

    if ($request->filled('estimated_delivery_date')) {
      $filters[] = [
        'column' => 'estimated_delivery_date',
        'operator' => 'date_between',
        'value' => $request->estimated_delivery_date
      ];
    }

    if ($request->filled('actual_delivery_date')) {
      $filters[] = [
        'column' => 'actual_delivery_date',
        'operator' => 'between',
        'value' => $request->actual_delivery_date
      ];
    }

    if ($request->filled('is_invoiced')) {
      $filters[] = [
        'column' => 'is_invoiced',
        'operator' => '=',
        'value' => $request->is_invoiced
      ];
    }

    if ($request->filled('currency_id')) {
      $filters[] = [
        'column' => 'currency_id',
        'operator' => '=',
        'value' => $request->currency_id
      ];
    }

    if ($request->filled('vehicle_plate')) {
      $filters[] = [
        'column' => 'vehicle_plate',
        'operator' => 'like',
        'value' => $request->vehicle_plate
      ];
    }

    $title = $request->get('title', 'Reporte de Órdenes de Trabajo');

    $options = [
      'title' => $title,
      'filters' => $filters,
      'format' => $request->get('format', 'excel'),
    ];

    return $this->exportService->exportToExcel(ApWorkOrder::class, $options);
  }

  /**
   * Procesa una firma de entrega en base64 y la guarda en Digital Ocean
   */
  private function processDeliverySignature(ApWorkOrder $workOrder, string $base64Signature): void
  {
    // Convertir base64 a UploadedFile con recorte automático
    $signatureFile = Helpers::base64ToUploadedFile($base64Signature, 'delivery_signature.png', true);

    // Subir archivo usando DigitalFileService
    $digitalFile = $this->digitalFileService->store(
      $signatureFile,
      self::FILE_PATH_DELIVERY_SIGNATURE,
      'public',
      $workOrder->getTable()
    );

    // Actualizar la orden de trabajo con la URL de la firma
    $workOrder->signature_delivery_url = $digitalFile->url;
    $workOrder->save();
  }

  /**
   * Procesa el ajuste de inventario por salida al generar una nota interna.
   * Libera el stock reservado y crea un movimiento de tipo ADJUSTMENT_OUT para los repuestos utilizados en la OT.
   *
   * @param ApWorkOrder $workOrder
   * @param ApInternalNote $internalNote
   * @return void
   * @throws Exception
   */
  private function processInventoryAdjustmentForInternalNote(ApWorkOrder $workOrder, ApInternalNote $internalNote): void
  {
    // Verificar si ya se procesó el ajuste de inventario
    if ($workOrder->output_generation_warehouse) {
      return; // Ya se procesó, salir
    }

    // Obtener solo los repuestos que tienen product_id (excluir mano de obra/servicios)
    $productParts = $workOrder->parts->filter(function ($part) {
      return $part->product_id !== null;
    });

    if ($productParts->isEmpty()) {
      return; // No hay repuestos para procesar
    }

    // Obtener el almacén físico de la sede de la orden de trabajo
    $warehouse = Warehouse::where('sede_id', $workOrder->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      throw new Exception('No se encontró almacén físico activo para la sede de la orden de trabajo');
    }

    // PASO 1: Liberar las reservas de stock de cada repuesto
    foreach ($productParts as $part) {
      $stock = ProductWarehouseStock::where('product_id', $part->product_id)
        ->where('warehouse_id', $part->warehouse_id)
        ->first();

      if ($stock) {
        // Liberar la cantidad reservada
        $stock->releaseReservedStock($part->quantity_used);
      }
    }

    // PASO 2: Crear el ajuste de salida (esto reducirá el stock real)
    $movementData = [
      'movement_type' => InventoryMovement::TYPE_ADJUSTMENT_OUT,
      'warehouse_id' => $warehouse->id,
      'notes' => "Ajuste de salida por generación de Nota Interna {$internalNote->number} - OT {$workOrder->correlative}",
      'movement_date' => now(),
    ];

    // Preparar detalles (uno por cada repuesto)
    $details = [];
    foreach ($productParts as $part) {
      $details[] = [
        'product_id' => $part->product_id,
        'quantity' => $part->quantity_used,
        'unit_cost' => $part->unit_price,
        'notes' => "Consumo NI {$internalNote->number} - {$part->product->name}",
      ];
    }

    // Crear el ajuste usando el servicio de inventario
    $this->inventoryMovementService->createAdjustment($movementData, $details);

    // Marcar como procesado para evitar duplicados
    $workOrder->update(['output_generation_warehouse' => true]);
  }

  public function updateItems(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['work_order_id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureCanBeModified();

      // Buscar el item del grupo 1
      $item = ApWorkOrderItem::where('id', $data['id'])
        ->where('work_order_id', $workOrder->id)
        ->where('group_number', 1)
        ->first();

      if (!$item) {
        throw new Exception('Item con ID ' . $data['id'] . ' no encontrado en esta orden de trabajo o no pertenece al grupo 1');
      }

      // Actualizar el item
      $item->update([
        'type_planning_id' => $data['type_planning_id'],
        'type_operation_id' => $data['type_operation_id'],
        'description' => $data['description'],
      ]);

      // Recalcular totales después de actualizar el item
      $workOrder->calculateTotals();

      // Reload relations
      $workOrder->load([
        'appointmentPlanning',
        'vehicle',
        'status',
        'advisor',
        'sede',
        'creator',
        'items.typePlanning'
      ]);

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Copia automáticamente los datos de la inspección de recepción (ap_receiving_inspection)
   * a la inspección del vehículo (ap_vehicle_inspection) cuando es un servicio de PDI
   */
  private function copyReceivingInspectionToVehicleInspection(ApWorkOrder $workOrder, Vehicles $vehicle): void
  {
    // Obtener la guía de remisión de recepción del vehículo
    $shippingGuide = $vehicle->shippingGuideReceiving;

    // Si no existe la guía o su inspección de recepción, dejarlo pasar normal
    if (!$shippingGuide || !$shippingGuide->receivingInspection) {
      return;
    }

    $receivingInspection = $shippingGuide->receivingInspection;

    // Crear ApVehicleInspection copiando datos de ApReceivingInspection
    $vehicleInspection = ApVehicleInspection::create([
      'ap_work_order_id' => $workOrder->id,
      'photo_front_url' => $receivingInspection->photo_front_url,
      'photo_back_url' => $receivingInspection->photo_back_url,
      'photo_left_url' => $receivingInspection->photo_left_url,
      'photo_right_url' => $receivingInspection->photo_right_url,
      'general_observations' => $receivingInspection->general_observations,
      'inspected_by' => $receivingInspection->inspected_by,
      'inspection_date' => now(),
      'mileage' => 0,
      'fuel_level' => '0',
      'oil_level' => '0',
    ]);

    // Copiar los damages de ApReceivingInspectionDamage a ApVehicleInspectionDamages
    foreach ($receivingInspection->damages as $damage) {
      ApVehicleInspectionDamages::create([
        'vehicle_inspection_id' => $vehicleInspection->id,
        'damage_type' => $damage->damage_type,
        'x_coordinate' => $damage->x_coordinate,
        'y_coordinate' => $damage->y_coordinate,
        'description' => $damage->description,
        'photo_url' => $damage->photo_url,
      ]);
    }

    // Actualizar la OT con el vehicle_inspection_id y cambiar el estado a RECEIVED
    $workOrder->update([
      'vehicle_inspection_id' => $vehicleInspection->id,
      'status_id' => ApMasters::RECEIVED_WORK_ORDER_ID,
    ]);
  }

  /**
   * Realiza el recálculo completo de una OT: recalcula items hijos (labours y parts)
   * y luego los totales del padre. Método reutilizable que puede ser llamado desde
   * cualquier contexto (con o sin transacción activa).
   */
  public function performWorkOrderRecalculation(ApWorkOrder $workOrder): void
  {
    // Cargar relaciones necesarias para el cálculo
    $workOrder->load(['labours', 'parts', 'orderQuotation.details']);

    // Recalcular primero los ítems hijos (mano de obra y repuestos) desde sus
    // campos base, con el mismo redondeo en cadena a 2 decimales usado al crearlos
    // (PriceRounding), para que total_labor_cost/total_parts_cost del padre sean
    // siempre una suma consistente de hijos ya redondeados y no arrastren
    // valores desalineados (ej. de una conversión de moneda o de datos antiguos).
    $this->recalculateLabourItems($workOrder);
    $this->recalculatePartItems($workOrder);
    $workOrder->refresh();
    $workOrder->load(['labours', 'parts', 'orderQuotation.details']);

    // Recalcular totales del padre a partir de los hijos ya recalculados
    $workOrder->calculateTotals();
  }

  /**
   * Recalcula hourly_rate (si $factor != 1, ej. cambio de moneda) y
   * total_cost/net_amount/tax_amount de cada mano de obra a partir de sus campos
   * base (hourly_rate, time_spent, discount_percentage), usando la misma fuente
   * de verdad (PriceRounding) que se usa al crear/editar el ítem. Única
   * implementación para este cálculo: la usan tanto recalculateTotals() (factor
   * por defecto 1.0, solo refresca totales) como handleCurrencyChange() (factor
   * real de conversión).
   */
  private function recalculateLabourItems(ApWorkOrder $workOrder, float $factor = 1.0): void
  {
    foreach ($workOrder->labours as $labour) {
      $result = PriceRounding::calculateLine(
        $labour->hourly_rate,
        $labour->time_spent_decimal,
        (float)($labour->discount_percentage ?? 0),
        $factor
      );

      $labour->update([
        'hourly_rate' => $result['unit_price'],
        'total_cost' => $result['total_cost'],
        'net_amount' => $result['net_amount'],
        'tax_amount' => $result['tax_amount'],
      ]);
    }
  }

  /**
   * Recalcula unit_price (si $factor != 1, ej. cambio de moneda) y
   * total_cost/net_amount/tax_amount de cada repuesto a partir de sus campos
   * base (unit_price, quantity_used, discount_percentage), usando la misma
   * fuente de verdad (PriceRounding) que se usa al crear/editar el ítem. Única
   * implementación para este cálculo: la usan tanto recalculateTotals() (factor
   * por defecto 1.0, solo refresca totales) como handleCurrencyChange() (factor
   * real de conversión).
   */
  private function recalculatePartItems(ApWorkOrder $workOrder, float $factor = 1.0): void
  {
    foreach ($workOrder->parts as $part) {
      $result = PriceRounding::calculateLine(
        $part->unit_price,
        (float)$part->quantity_used,
        (float)($part->discount_percentage ?? 0),
        $factor
      );

      $part->update([
        'unit_price' => $result['unit_price'],
        'total_cost' => $result['total_cost'],
        'net_amount' => $result['net_amount'],
        'tax_amount' => $result['tax_amount'],
      ]);
    }
  }

  public function recalculateTotals($id)
  {
    return DB::transaction(function () use ($id) {
      $workOrder = $this->find($id);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      // Usar el método centralizado de recálculo
      $this->performWorkOrderRecalculation($workOrder);

      // Recargar la orden de trabajo con todas las relaciones para mostrar
      $workOrder->load([
        'items',
        'orderQuotation.details.product.unitMeasurement',
        'labours',
        'parts.product.unitMeasurement',
        'advancesWorkOrder'
      ]);

      $additionalData['includeCostManHours'] = true;

      return (new WorkOrderResource($workOrder))->additional($additionalData);
    });
  }

  /**
   * Almacena un deducible para una orden de trabajo
   * - Relaciona un comprobante electrónico con la orden de trabajo
   * - Guarda el registro en ap_deductible_work_order
   * - Actualiza el campo deductible_amount en ap_work_orders (sumando el total del comprobante)
   * - Valida que la orden no esté cerrada/finalizada/anulada
   */
  public function storeDeductible(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['work_order_id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      // Validar que la orden no esté cerrada, finalizada o anulada
      $forbiddenStatuses = [
        ApMasters::CANCELED_WORK_ORDER_ID,
        ApMasters::FINISHED_WORK_ORDER_ID,
        ApMasters::CLOSED_WORK_ORDER_ID,
      ];

      if (in_array($workOrder->status_id, $forbiddenStatuses)) {
        throw new Exception('No se puede agregar un deducible a una orden de trabajo cerrada, finalizada o anulada');
      }

      // Obtener el comprobante electrónico
      $electronicDocument = \App\Models\ap\facturacion\ElectronicDocument::find($data['electronic_document_id']);

      if (!$electronicDocument) {
        throw new Exception('Comprobante electrónico no encontrado');
      }

      // Validar que la moneda de la orden de trabajo coincida con la moneda del comprobante
      $workOrderCurrencyId = $workOrder->currency_id;
      $documentCurrencyId = $electronicDocument->sunat_concept_currency_id;

      // Traducir moneda del comprobante a sistema de TypeCurrency
      $documentCurrencyInWorkOrderSystem = null;
      if ($documentCurrencyId == SunatConcepts::CURRENCY_PEN) {
        $documentCurrencyInWorkOrderSystem = TypeCurrency::PEN_ID;
      } elseif ($documentCurrencyId == SunatConcepts::CURRENCY_USD) {
        $documentCurrencyInWorkOrderSystem = TypeCurrency::USD_ID;
      }

      if ($workOrderCurrencyId !== $documentCurrencyInWorkOrderSystem) {
        $workOrderCurrencyName = $workOrderCurrencyId == TypeCurrency::PEN_ID ? 'PEN' : 'USD';
        $documentCurrencyName = $documentCurrencyId == SunatConcepts::CURRENCY_PEN ? 'PEN' : 'USD';
        throw new Exception("La moneda del comprobante electrónico ({$documentCurrencyName}) no coincide con la moneda de la orden de trabajo ({$workOrderCurrencyName})");
      }

      // Crear el registro del deducible
      $deductible = \App\Models\ap\postventa\taller\ApDeductibleWorkOrder::create([
        'work_order_id' => $data['work_order_id'],
        'electronic_document_id' => $data['electronic_document_id'],
        'created_by' => auth()->id(),
      ]);

      // Actualizar el campo deductible_amount en ap_work_orders (sumando el total del comprobante)
      $currentDeductibleAmount = $workOrder->deductible_amount ?? 0;
      $newDeductibleAmount = $currentDeductibleAmount + $electronicDocument->total;

      $workOrder->update([
        'deductible_amount' => $newDeductibleAmount,
      ]);

      // Recargar la orden de trabajo con los deducibles y su comprobante electrónico
      $workOrder->load('deductibles.electronicDocument', 'deductibles.creator');

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Elimina un deducible de una orden de trabajo
   * - Valida que la orden pueda ser modificada
   * - Resta el monto del comprobante del deductible_amount
   * - Elimina el registro usando soft delete
   */
  public function deleteDeductible(int $deductibleId)
  {
    return DB::transaction(function () use ($deductibleId) {
      // Buscar el deducible con sus relaciones
      $deductible = \App\Models\ap\postventa\taller\ApDeductibleWorkOrder::with('electronicDocument')
        ->find($deductibleId);

      if (!$deductible) {
        throw new Exception('Deducible no encontrado');
      }

      // Validar que la orden de trabajo pueda ser modificada
      $workOrder = $this->find($deductible->work_order_id);

      $forbiddenStatuses = [
        ApMasters::CANCELED_WORK_ORDER_ID,
        ApMasters::FINISHED_WORK_ORDER_ID,
        ApMasters::CLOSED_WORK_ORDER_ID,
      ];

      if (in_array($workOrder->status_id, $forbiddenStatuses)) {
        throw new Exception('No se puede eliminar un deducible de una orden de trabajo cerrada, finalizada o anulada');
      }

      // Restar el monto del comprobante del deductible_amount
      $currentDeductibleAmount = $workOrder->deductible_amount ?? 0;
      $newDeductibleAmount = max(0, $currentDeductibleAmount - $deductible->electronicDocument->total);

      $workOrder->update([
        'deductible_amount' => $newDeductibleAmount,
      ]);

      // Eliminar el deducible (soft delete)
      $deductible->delete();

      // Recargar la orden de trabajo con los deducibles activos
      $workOrder->load('deductibles.electronicDocument', 'deductibles.creator');

      return new WorkOrderResource($workOrder);
    });
  }

  /**
   * Sube documentos (PDF) y los asocia a una orden de trabajo en gp_digital_files.
   * Máximo 3 archivos por solicitud.
   *
   * @param int $id ID de la orden de trabajo
   * @param array $files Archivos subidos (UploadedFile[])
   */
  public function uploadDocuments(int $id, array $files)
  {
    $workOrder = $this->find($id);

    if (count($files) > 3) {
      throw new Exception('Solo se pueden adjuntar hasta 3 archivos por solicitud');
    }

    return DB::transaction(function () use ($workOrder, $files) {
      $uploadedFiles = [];

      foreach ($files as $file) {
        $uploadedFiles[] = $this->digitalFileService->storeFromContent(
          file_get_contents($file->getRealPath()),
          $file->getClientOriginalName(),
          self::FILE_PATH_DOCUMENTS,
          'public',
          $file->getClientMimeType(),
          $workOrder->getTable(),
          $workOrder->id
        );
      }

      return $uploadedFiles;
    });
  }

  /**
   * Lista los documentos asociados a una orden de trabajo.
   */
  public function listDocuments(int $id)
  {
    $workOrder = $this->find($id);

    return \App\Models\gp\gestionsistema\DigitalFile::where('model', $workOrder->getTable())
      ->where('id_model', $workOrder->id)
      ->orderByDesc('id')
      ->get();
  }
}

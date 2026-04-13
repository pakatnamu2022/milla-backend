<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Helpers;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use App\Models\ap\postventa\taller\ApVehicleInspection;
use App\Models\ap\postventa\taller\ApVehicleInspectionDamages;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderItem;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\GeneralMaster;
use App\Models\gp\gestionhumana\personal\WorkerSignature;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderService extends BaseService implements BaseServiceInterface
{
  protected WorkOrderLabourService $labourService;
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos
  private const FILE_PATH_DELIVERY_SIGNATURE = '/ap/postventa/taller/entregas/firmas/';

  public function __construct(WorkOrderLabourService $labourService, DigitalFileService $digitalFileService)
  {
    $this->labourService = $labourService;
    $this->digitalFileService = $digitalFileService;
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
      ->whereHas('internalNote');

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
      $vehicle = Vehicles::find($data['vehicle_id']);

      //Plate, vin del vehiculo
      $vehicle = Vehicles::find($data['vehicle_id']);
      if ($vehicle) {
        $data['vehicle_plate'] = $vehicle->plate;
        $data['vehicle_vin'] = $vehicle->vin;
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
      if (!empty($items)) {
        foreach ($items as $item) {
          $item['work_order_id'] = $workOrder->id;
          ApWorkOrderItem::create($item);
        }
      }

      return new WorkOrderResource($workOrder);
    });
  }

  public function show($id)
  {
    $workOrder = $this->find($id);
    $workOrder->load('items', 'orderQuotation', 'labours', 'parts', 'advancesWorkOrder');
    $additionalData['includeCostManHours'] = true;
    return (new WorkOrderResource($workOrder))->additional($additionalData);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrder = $this->find($data['id']);

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede modificar una orden de trabajo cerrada');
      }

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

      // Update work order
      $workOrder->update($data);

      // Si cambió el tipo de moneda, recalcular labours y parts
      if ($currencyChanged) {
        $this->recalculateCurrencyChange($workOrder, $oldCurrencyId, $newCurrencyId);
      }

      // Si existe $data['order_quotation_id']
      if (isset($data['order_quotation_id'])) {
        $quotation = ApOrderQuotations::find($data['order_quotation_id']);

        if (!$quotation) {
          throw new Exception('Cotización no encontrada');
        }

        if ($quotation->is_take) {
          throw new Exception('La cotización ya está tomada por otra orden de trabajo');
        }

        $workOrderCurrencyId = $data['currency_id'] ?? $workOrder->currency_id;

        if ((int)$quotation->currency_id !== (int)$workOrderCurrencyId) {
          throw new Exception('La moneda de la OT y la cotización deben ser iguales');
        }

        if ($quotation) {
          $quotation->update(['is_take' => 1]);
        }

        // Recalcular totales usando el método del modelo (detecta automáticamente si tiene cotización)
        $workOrder->load(['labours', 'parts', 'orderQuotation.details']);
        $workOrder->calculateTotals();
      }

      // If existe $data['vehicle_inspection_id']
      if (isset($data['vehicle_inspection_id'])) {
        $workOrder->update([
          'status_id' => ApMasters::RECEIVED_WORK_ORDER_ID
        ]);
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

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede modificar una orden de trabajo cerrada');
      }
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

    if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
      throw new Exception('No se puede eliminar una orden de trabajo cerrada');
    }

    if ($workOrder->appointment_planning_id !== null) {
      $appointmentPlanning = AppointmentPlanning::find($workOrder->appointment_planning_id);
      if ($appointmentPlanning) {
        $appointmentPlanning->update(['is_taken' => false]);
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

  public function getPaymentSummary($workOrderId, $groupNumber = 1)
  {
    $workOrder = ApWorkOrder::with(['labours', 'advancesWorkOrder', 'parts', 'orderQuotation.details'])
      ->findOrFail($workOrderId);

    // Usar el método del modelo para calcular totales
    $totals = $workOrder->getTotalsArray($groupNumber);

    // Calculate total advances
    $totalAdvances = $workOrder->advancesWorkOrder->sum('total') ?? 0;

    // Calculate remaining balance (total - advances)
    $remainingBalance = $totals['total_amount'] - $totalAdvances;

    return response()->json([
      'work_order_id' => $workOrder->id,
      'correlative' => $workOrder->correlative,
      'group_number' => $groupNumber,
      'payment_summary' => array_merge($totals, [
        'total_advances' => (float)$totalAdvances,
        'remaining_balance' => (float)$remainingBalance,
      ])
    ]);
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

    // Calcular anticipos y saldo
    $totalAdvances = $workOrder->advancesWorkOrder->sum('total') ?? 0;
    $remainingBalance = $totals['total_amount'] - $totalAdvances;

    $data = [
      'workOrder' => $workOrder,
      'client' => $client,
      'vehicle' => $vehicle,
      'labours' => $labours,
      'parts' => $parts,
      'advances' => $workOrder->advancesWorkOrder,
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

  private function recalculateCurrencyChange(ApWorkOrder $workOrder, int $oldCurrencyId, int $newCurrencyId): void
  {
    // Obtener el factor de conversión
    $factor = $this->getConversionFactor($workOrder, $oldCurrencyId, $newCurrencyId);

    // Recalcular labours
    $this->recalculateLabours($workOrder->id, $factor);

    // Recalcular parts
    $this->recalculateParts($workOrder->id, $factor);
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

    return (float)$exchangeRate->rate;
  }

  private function recalculateLabours(int $workOrderId, float $factor): void
  {
    $labours = WorkOrderLabour::where('work_order_id', $workOrderId)->get();

    foreach ($labours as $labour) {
      $newHourlyRate = $labour->hourly_rate * $factor;
      $newTotalCost = $labour->total_cost * $factor;
      $newNetAmount = $labour->net_amount * $factor;

      $labour->update([
        'hourly_rate' => round($newHourlyRate, 2),
        'total_cost' => round($newTotalCost, 2),
        'net_amount' => round($newNetAmount, 2),
      ]);
    }
  }

  private function recalculateParts(int $workOrderId, float $factor): void
  {
    $parts = ApWorkOrderParts::where('work_order_id', $workOrderId)->get();

    foreach ($parts as $part) {
      $newUnitPrice = $part->unit_price * $factor;
      $newTotalCost = $part->total_cost * $factor;
      $newNetAmount = $part->net_amount * $factor;
      $newTaxAmount = $part->tax_amount * $factor;

      $part->update([
        'unit_price' => round($newUnitPrice, 2),
        'total_cost' => round($newTotalCost, 2),
        'net_amount' => round($newNetAmount, 2),
        'tax_amount' => round($newTaxAmount, 2),
      ]);
    }
  }

  public function unlinkQuotation(int $id): WorkOrderResource
  {
    return DB::transaction(function () use ($id) {
      $workOrder = $this->find($id);

      if ($workOrder->order_quotation_id === null) {
        throw new Exception('La orden de trabajo no tiene cotización asociada');
      }

      // Obtener la cotización antes de desasociar
      $quotation = ApOrderQuotations::find($workOrder->order_quotation_id);

      // Desasociar la cotización de la orden de trabajo
      $workOrder->update(['order_quotation_id' => null]);

      // Marcar la cotización como no tomada para que esté disponible
      if ($quotation) {
        $quotation->update([
          'is_take' => 0
        ]);
      }

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

      if ($workOrder->status_id === ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede modificar una orden de trabajo cerrada');
      }
      // Update work order
      $workOrder->update($data);

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

      if ($workOrder->status_id !== ApMasters::CLOSED_WORK_ORDER_ID) {
        throw new Exception('No se puede generar entrega para una que no ha sido facturada');
      }

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

      $hasVehiclePdi = $vehicle->has_pdi;
      $typeCurrency = $hasVehiclePdi ? TypeCurrency::USD_ID : TypeCurrency::PEN_ID;

      //2. Creamos la cabecera de la OT
      $apWorkOrder = ApWorkOrder::create([
        'correlative' => $this->generateCorrelative(),
        'vehicle_id' => $vehicle->id,
        'currency_id' => $typeCurrency,
        'vehicle_plate' => $vehicle->plate,
        'vehicle_vin' => $vehicle->vin,
        'status_id' => ApMasters::OPENING_WORK_ORDER_ID,
        'advisor_id' => auth()->id(),
        'invoice_to' => null,
        'sede_id' => $vehicle->warehouse ? $vehicle->warehouse->sede_id : null,
        'opening_date' => now()->format('Y-m-d'),
        'diagnosis_date' => now()->format('Y-m-d'),
        'is_delivery' => true,
        'delivery_by' => auth()->id(),
        'created_by' => auth()->id(),
      ]);

      //3. Generamos el detalle de la OT
      ApWorkOrderItem::create([
        'group_number' => 1,
        'work_order_id' => $apWorkOrder->id,
        'type_planning_id' => TypePlanningWorkOrder::TYPE_PLANNING_PDI_ID,
        'type_operation_id' => ApMasters::TIPO_OPERACION_CITA_PDI_ID,
        'description' => 'SERVICIO DE PDI',
      ]);

      // Calculamos la tarifa
      if ($hasVehiclePdi) {
        $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_PDI_DERCO_ID)->value;
      } else {
        if ($vehicle->is_heavy) {
          $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_VP_ID)->value;
        } else {
          $hourly_rate = GeneralMaster::findOrFail(GeneralMaster::COST_PER_MAN_HOUR_VL_ID)->value;
        }
      }

      // 4. Generamos la mano de obra de la OT
      $labourData = [
        'description' => 'SERVICIO DE MANO DE OBRA PDI',
        'time_spent' => 1.0, // 1 hora por defecto
        'hourly_rate' => (float)$hourly_rate,
        'work_order_id' => $apWorkOrder->id,
        'worker_id' => auth()->id(),
        'group_number' => 1,
      ];

      // 5. Guardamos la mano de obra usando el servicio para que se actualicen los totales correctamente
      $this->labourService->store($labourData);

      // 6. Copiamos la inspección de recepción a la inspección del vehículo
      $shippingGuide = $vehicle->shippingGuideReceiving;

      //validamos que exista la recepcion
      if ($shippingGuide) {
        throw new Exception('El vehículo no tiene una guía de remisión de recepción asociada');
      }

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

      $vehicle->update([
        'generated_pdi' => true,
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

  public function getByIds(array $ids)
  {
    $workOrders = ApWorkOrder::with(['internalNote', 'items', 'labours', 'parts'])
      ->whereIn('id', $ids)
      ->get();

    return WorkOrderResource::collection($workOrders);
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
}

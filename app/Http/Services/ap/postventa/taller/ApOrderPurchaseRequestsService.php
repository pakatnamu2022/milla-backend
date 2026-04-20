<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderPurchaseRequestsResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\EmailService;
use App\Http\Utils\Constants;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequestDetails;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderPurchaseRequestsService extends BaseService implements BaseServiceInterface
{
  protected EmailService $emailService;

  public function __construct(EmailService $emailService)
  {
    $this->emailService = $emailService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApOrderPurchaseRequests::class,
      $request,
      ApOrderPurchaseRequests::filters,
      ApOrderPurchaseRequests::sorts,
      ApOrderPurchaseRequestsResource::class
    );
  }

  public function find($id)
  {
    $purchaseRequest = ApOrderPurchaseRequests::with([
      'purchaseOrder',
      'warehouse',
      'details.product'
    ])->where('id', $id)->first();

    if (!$purchaseRequest) {
      throw new Exception('Solicitud de compra no encontrada');
    }

    return $purchaseRequest;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Generate unique request number
      $data['request_number'] = $this->generateRequestNumber();
      $date = Carbon::parse($data['requested_date'])->format('Y-m-d');

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }

      // Set requested_by
      if (auth()->check()) {
        $data['requested_by'] = auth()->user()->id;
      }
      $data['exchange_rate'] = $exchangeRate->rate;

      // Extract details from data
      $details = $data['details'] ?? [];
      unset($data['details']);

      // Validate that all products exist in the warehouse
      if (!empty($details) && isset($data['warehouse_id'])) {
        $this->validateProductsInWarehouse($details, $data['warehouse_id']);
      }

      // Mark quotation as taken if provided
      if (isset($data['ap_order_quotation_id'])) {
        ApOrderQuotations::find($data['ap_order_quotation_id'])
          ?->markAsTaken();
      }

      // Create purchase request
      $purchaseRequest = ApOrderPurchaseRequests::create($data);

      // Create details
      if (!empty($details)) {
        foreach ($details as $detail) {
          $detail['order_purchase_request_id'] = $purchaseRequest->id;
          ApOrderPurchaseRequestDetails::create($detail);
        }
      }

      // Enviar notificación al encargado de almacén
      try {
        $this->sendPurchaseRequestNotificationEmail($purchaseRequest->id);
      } catch (Exception $e) {
        \Log::error('Error al enviar notificación de solicitud de compra: ' . $e->getMessage());
      }

      return new ApOrderPurchaseRequestsResource($purchaseRequest->load([
        'purchaseOrder',
        'warehouse',
        'details.product'
      ]));
    });
  }

  public function show($id)
  {
    $orderPurchaseRequest = $this->find($id);
    $orderPurchaseRequest->load([
      'purchaseOrder',
      'warehouse',
      'details.product'
    ]);
    return new ApOrderPurchaseRequestsResource($orderPurchaseRequest);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $purchaseRequest = $this->find($data['id']);
      $date = Carbon::parse($data['requested_date'])->format('Y-m-d');

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }

      if ($purchaseRequest->ap_order_quotation_id) {
        throw new Exception("No se puede modificar una solicitud de compra asociada a una cotización.");
      }

      if ($purchaseRequest->approved) {
        throw new Exception("No se puede modificar una solicitud de compra que ha sido aprobada.");
      }

      if ($purchaseRequest->status === ApOrderPurchaseRequests::CANCELLED) {
        throw new Exception("No se puede modificar una solicitud de compra que ha sido cancelada.");
      }

      if ($purchaseRequest->status === ApOrderPurchaseRequests::ORDERED) {
        throw new Exception("No se puede modificar una solicitud de compra que ha sido ordenada.");
      }

      if ($purchaseRequest->status === ApOrderPurchaseRequests::RECEIVED) {
        throw new Exception("No se puede modificar una solicitud de compra que ha sido recibida.");
      }

      $data['exchange_rate'] = $exchangeRate->rate;

      // Extract details from data
      $details = $data['details'] ?? null;
      unset($data['details']);

      // Validate that all products exist in the warehouse if details are being updated
      if ($details !== null && isset($data['warehouse_id'])) {
        $this->validateProductsInWarehouse($details, $data['warehouse_id']);
      } elseif ($details !== null) {
        // If warehouse_id is not in data, use the current warehouse_id
        $this->validateProductsInWarehouse($details, $purchaseRequest->warehouse_id);
      }

      // Update purchase request
      $purchaseRequest->update($data);

      // Update details if provided
      if ($details !== null) {
        // Delete existing details
        ApOrderPurchaseRequestDetails::where('order_purchase_request_id', $purchaseRequest->id)->delete();

        // Create new details
        foreach ($details as $detail) {
          $detail['order_purchase_request_id'] = $purchaseRequest->id;
          ApOrderPurchaseRequestDetails::create($detail);
        }
      }

      // Reload relations
      $purchaseRequest->load([
        'purchaseOrder',
        'warehouse',
        'details.product'
      ]);

      return new ApOrderPurchaseRequestsResource($purchaseRequest);
    });
  }

  public function destroy($id)
  {
    $purchaseRequest = $this->find($id);

    if ($purchaseRequest->approved) {
      throw new Exception("No se puede eliminar una solicitud de compra que ha sido aprobada.");
    }

    if ($purchaseRequest->status === ApOrderPurchaseRequests::CANCELLED) {
      throw new Exception("No se puede eliminar una solicitud de compra que ha sido cancelada.");
    }

    if ($purchaseRequest->status === ApOrderPurchaseRequests::ORDERED) {
      throw new Exception("No se puede eliminar una solicitud de compra que ya ha sido ordenada.");
    }

    if ($purchaseRequest->status === ApOrderPurchaseRequests::RECEIVED) {
      throw new Exception("No se puede eliminar una solicitud de compra que ya ha sido recibida.");
    }

    // Verificar si tiene pedidos de proveedor asociados
    $supplierOrderNumbers = $purchaseRequest->details()
      ->with('supplierOrders')
      ->get()
      ->pluck('supplierOrders')
      ->flatten()
      ->unique('id')
      ->pluck('order_number')
      ->values()
      ->toArray();

    if (!empty($supplierOrderNumbers)) {
      throw new Exception(
        "No se puede eliminar la solicitud de compra porque está asociada a los siguientes pedidos de proveedor: " .
        implode(', ', $supplierOrderNumbers)
      );
    }

    DB::transaction(function () use ($purchaseRequest) {
      //verificamos si ap_order_quotation_id esta seteado para liberar la cotizacion
      if ($purchaseRequest->ap_order_quotation_id) {
        $quotation = ApOrderQuotations::find($purchaseRequest->ap_order_quotation_id);
        if ($quotation) {
          $quotation->is_take = false;
          $quotation->save();
        }
      }

      // Delete purchase request
      $purchaseRequest->delete();
    });

    return response()->json(['message' => 'Solicitud de compra eliminada correctamente.']);
  }

  /**
   * Validate that all products exist in the specified warehouse
   *
   * @param array $details Array of product details
   * @param int $warehouseId Warehouse ID
   * @throws Exception if any product is not registered in the warehouse
   */
  private function validateProductsInWarehouse(array $details, int $warehouseId): void
  {
    $warehouse = Warehouse::find($warehouseId);
    $warehouseName = $warehouse ? $warehouse->description : "ID: {$warehouseId}";

    foreach ($details as $detail) {
      $productId = $detail['product_id'] ?? null;

      if (!$productId) {
        continue;
      }

      // Check if product exists in warehouse stock (regardless of quantity)
      $productStock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->first();

      if (!$productStock) {
        // Get product name for better error message
        $product = Products::find($productId);
        $productName = $product ? $product->name : "ID: {$productId}";

        throw new Exception(
          "El producto '{$productName}' no está asignado al almacén '{$warehouseName}'. " .
          "Por favor, registre el producto en el almacén antes de crear la solicitud de compra."
        );
      }
    }
  }

  /**
   * Generate unique request number
   * Format: PR-YYYYMMDD-XXXX
   */
  private function generateRequestNumber(): string
  {
    $date = now()->format('Ymd');
    $prefix = "PR-{$date}-";

    // Get last request number for today
    $lastRequest = ApOrderPurchaseRequests::where('request_number', 'LIKE', "{$prefix}%")
      ->orderBy('request_number', 'desc')
      ->first();

    if ($lastRequest) {
      // Extract sequence number and increment
      $lastSequence = (int)substr($lastRequest->request_number, -4);
      $newSequence = $lastSequence + 1;
    } else {
      $newSequence = 1;
    }

    return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
  }

  /**
   * Obtener detalles de solicitudes pendientes para crear órdenes de compra
   * Solo devuelve detalles con status 'pending'
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getPendingDetails(Request $request)
  {
    $query = ApOrderPurchaseRequestDetails::with([
      'orderPurchaseRequest.warehouse',
      'product'
    ])
      ->where('status', 'pending')
      ->whereHas('orderPurchaseRequest', function ($q) {
        $q->where(function ($q2) {
          $q2->where('approved', true)
            ->orWhereNotNull('ap_order_quotation_id');
        });
      })
      ->orderBy('created_at', 'desc');


    // Filtro opcional por warehouse_id
    if ($request->has('warehouse_id')) {
      $query->whereHas('orderPurchaseRequest', function ($q) use ($request) {
        $q->where('warehouse_id', $request->warehouse_id);
      });
    }

    // Filtro opcional por producto
    if ($request->has('product_id')) {
      $query->where('product_id', $request->product_id);
    }

    // Filtro opcional por tipo de suministro
    if ($request->has('supply_type')) {
      $query->where('supply_type', $request->supply_type);
    }

    $details = $query->get();

    return response()->json([
      'data' => $details->map(function ($detail) {
        return [
          'id' => $detail->id,
          'ap_purchase_request_id' => $detail->order_purchase_request_id,
          'request_number' => $detail->orderPurchaseRequest->request_number,
          'product_id' => $detail->product_id,
          'product_name' => $detail->product->name ?? null,
          'product_code' => $detail->product->code ?? null,
          'unit_measurement_id' => $detail->product->unit_measurement_id ?? null,
          'quantity' => $detail->quantity,
          'notes' => $detail->notes,
          'requested_delivery_date' => $detail->requested_delivery_date,
          'warehouse_id' => $detail->orderPurchaseRequest->warehouse_id,
          'warehouse_name' => $detail->orderPurchaseRequest->warehouse->description ?? null,
          'created_at' => $detail->created_at,
          'requested_name' => $detail->orderPurchaseRequest->requestedBy->name ?? null,
        ];
      })
    ]);
  }

  /**
   * Rechazar/descartar un detalle de solicitud
   * Cambia el status a 'rejected'
   * @param int $detailId
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function rejectDetail(int $detailId)
  {
    $detail = ApOrderPurchaseRequestDetails::find($detailId);

    if (!$detail) {
      throw new Exception('Detalle de solicitud no encontrado');
    }

    // Solo se puede rechazar si está en pending
    if ($detail->status !== 'pending') {
      throw new Exception("No se puede rechazar un detalle con status '{$detail->status}'. Solo se pueden rechazar detalles pendientes.");
    }

    $detail->update(['status' => 'rejected']);

    return response()->json([
      'message' => 'Detalle de solicitud rechazado correctamente',
      'data' => $detail
    ]);
  }

  public function generatePurchaseRequestPDF(int $id)
  {
    $purchaseRequest = ApOrderPurchaseRequests::with([
      'apOrderQuotation.client.district',
      'apOrderQuotation.details.product',
      'apOrderQuotation.typeCurrency',
      'apOrderQuotation.createdBy.person',
      'apOrderQuotation.vehicle.model.family.brand',
      'warehouse',
      'warehouse.sede',
      'requestedBy.person',
      'details.product'
    ])->find($id);

    if (!$purchaseRequest) {
      throw new Exception('solicitud de compra no encontrada');
    }

    $quotation = $purchaseRequest->apOrderQuotation;
    $hasQuotation = $quotation !== null;

    // Datos base de la solicitud
    $data = [
      'request_number' => $purchaseRequest->request_number,
      'requested_date' => $purchaseRequest->requested_date ?? $purchaseRequest->created_at,
      'delivery_date' => '-',
      'work_order_number' => '-',
      'has_quotation' => $hasQuotation,
      'quotation_number' => $hasQuotation ? $quotation->quotation_number : null,
      'sede' => $purchaseRequest->warehouse->sede,
    ];

    // Datos del proveedor/cliente
    if ($hasQuotation && $quotation->client) {
      $client = $quotation->client;
    } else {
      $client = BusinessPartners::find(BusinessPartners::AUTOMOTORES_PAKATNAMU_ID);
    }

    $data['supplier_name'] = $client->full_name ?? '-';
    $data['supplier_ruc'] = $client->num_doc ?? '-';
    $data['supplier_address'] = $client->direction ?? '-';
    $data['supplier_ubigeo'] = $client->ubigeo ?? '-';
    $data['supplier_city'] = $client->district ? $client->district->name . ' - ' . ($client->district->province->name ?? '') : '-';
    $data['supplier_phone'] = $client->phone ?? '-';
    $data['supplier_email'] = $client->email ?? '-';

    // Datos del vendedor/asesor
    if ($purchaseRequest->requestedBy && $purchaseRequest->requestedBy->person) {
      $data['advisor_name'] = ($purchaseRequest->requestedBy->person->nombre_completo ?? '-');
    } else {
      $data['advisor_name'] = '-';
    }

    // Datos del almacén
    $data['warehouse_name'] = $purchaseRequest->warehouse
      ? $purchaseRequest->warehouse->id . ' - ' . $purchaseRequest->warehouse->description
      : '-';

    // Datos del vehículo
    if ($hasQuotation && $quotation->vehicle) {
      $vehicle = $quotation->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? '-';
      $data['vehicle_vin'] = $vehicle->vin ?? '-';
      $data['vehicle_model'] = $vehicle->model
        ? ($vehicle->model->family->brand->name ?? '') . ' ' . ($vehicle->model->version ?? '')
        : '-';
    } else {
      $data['vehicle_plate'] = '-';
      $data['vehicle_vin'] = '-';
      $data['vehicle_model'] = '-';
    }

    // Forma de pago (si hay cotización)
    $data['payment_method'] = '-';

    // Preparar detalles con precios de la cotización
    $details = [];
    $total = 0;

    foreach ($purchaseRequest->details as $detail) {
      $product = $detail->product;
      $code = $product ? $product->code : '-';
      $description = $product ? $product->name : '-';
      $quantity = $detail->quantity;
      $supply_type = $detail->supply_type;
      $notes = $detail->notes ?? '-';

      // Buscar precio en la cotización si existe
      $price = '-';
      $discount = '-';
      $lineTotal = '-';

      if ($hasQuotation) {
        $quotationDetail = $quotation->details
          ->where('product_id', $detail->product_id)
          ->first();

        if ($quotationDetail) {
          $price = $quotationDetail->unit_price;
          $discount = $quotationDetail->discount_percentage > 0 ? $quotationDetail->discount_percentage : '0';
          $lineTotal = $quotationDetail->total_amount;
          $total += $lineTotal;
        }
      }

      $details[] = [
        'code' => $code,
        'description' => $description,
        'supply_type' => $supply_type,
        'notes' => $notes,
        'quantity' => number_format($quantity, 2),
        'price' => is_numeric($price) ? number_format($price, 2) : $price,
        'discount' => is_numeric($discount) ? number_format($discount, 2) : $discount,
        'total' => is_numeric($lineTotal) ? number_format($lineTotal, 2) : $lineTotal,
      ];
    }

    $data['details'] = $details;
    $data['observations'] = $purchaseRequest->observations ?? '';

    // Obtener símbolo de moneda
    $currencySymbol = '';
    if ($hasQuotation && $quotation->typeCurrency) {
      $currencySymbol = $quotation->typeCurrency->symbol ?? '';
    }
    $data['currency_symbol'] = $currencySymbol;

    // Calcular subtotal, IGV y total
    if ($hasQuotation && $total > 0) {
      $igvRate = Constants::VAT_TAX / 100;
      $igv = $total * $igvRate;
      $totalWithIgv = $total + $igv;

      $data['subtotal'] = number_format($total, 2);
      $data['igv'] = number_format($igv, 2);
      $data['total'] = number_format($totalWithIgv, 2);
    } else {
      $data['subtotal'] = '-';
      $data['igv'] = '-';
      $data['total'] = '-';
    }

    // Obtener anticipos y facturas si existe cotización
    $electronicDocuments = [];
    if ($hasQuotation) {
      $documents = ElectronicDocument::where('order_quotation_id', $quotation->id)
        ->where('anulado', false)
        ->whereIn('status', [
          ElectronicDocument::STATUS_SENT,
          ElectronicDocument::STATUS_ACCEPTED
        ])
        ->orderBy('fecha_de_emision', 'asc')
        ->get();

      foreach ($documents as $doc) {
        $electronicDocuments[] = [
          'number' => $doc->full_number,
          'date' => $doc->fecha_de_emision ? $doc->fecha_de_emision->format('d/m/Y') : '-',
          'amount' => number_format($doc->total, 2),
          'status' => $this->getStatusLabel($doc->status),
          'is_advance' => $doc->is_advance_payment ? 'Sí' : 'No',
          'type' => $doc->is_advance_payment ? 'Anticipo' : 'Factura'
        ];
      }
    }
    $data['electronic_documents'] = $electronicDocuments;

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-purchase-request', [
      'purchaseRequest' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Solicitud_Compra_' . $purchaseRequest->request_number . '.pdf';

    return $pdf->download($fileName);
  }

  private function getStatusLabel(string $status): string
  {
    return match ($status) {
      ElectronicDocument::STATUS_DRAFT => 'Borrador',
      ElectronicDocument::STATUS_SENT => 'Enviado',
      ElectronicDocument::STATUS_ACCEPTED => 'Aceptado',
      ElectronicDocument::STATUS_REJECTED => 'Rechazado',
      ElectronicDocument::STATUS_CANCELLED => 'Anulado',
      default => $status,
    };
  }

  /**
   * Aprueba una cotización según el cargo del usuario autenticado:
   * - Jefe de Taller (143) → chief_approval_by
   * - Gerente de Taller (142) → manager_approval_by
   */
  public function approve($data)
  {
    return DB::transaction(function () use ($data) {
      $id = $data['id'];
      $quotation = $this->find($id);
      $user = auth()->user();

      if ($quotation->status === ApOrderPurchaseRequests::CANCELLED) {
        throw new Exception('No se puede aprobar una solicitud de compra que ha sido cancelada.');
      }

      $positionId = (int)($user->person?->position?->id ?? 0);

      $isJefe = in_array($positionId, Position::POSITION_JEFE_PVT_IDS, true);
      $isGerente = in_array($positionId, Position::POSITION_GERENTE_PV_IDS, true);

      if (!($isJefe || $isGerente)) {
        throw new Exception('Solo Jefe o Gerente de Postventa pueden aprobar esta solicitud de compra.');
      }

      $quotation->update([
        'reviewed_by' => $user->id,
        'reviewed_at' => now(),
        'approved' => true
      ]);

      return new ApOrderPurchaseRequestsResource($quotation);
    });
  }

  /**
   * Envía notificación por correo al encargado o jefe de almacén sobre una nueva solicitud de compra
   * @param int $id
   * @return void
   * @throws Exception
   */
  private function sendPurchaseRequestNotificationEmail(int $id): void
  {
    $purchaseRequest = ApOrderPurchaseRequests::with([
      'warehouse.sede',
      'requestedBy.person',
      'details.product'
    ])->find($id);

    if (!$purchaseRequest) {
      throw new Exception('Solicitud de compra no encontrada');
    }

    // Obtener la sede del almacén
    $warehouse = $purchaseRequest->warehouse;
    if (!$warehouse || !$warehouse->sede_id) {
      \Log::warning("Solicitud de compra {$purchaseRequest->request_number}: No se pudo obtener la sede del almacén.");
      return;
    }

    $sedeId = $warehouse->sede_id;

    // Combinar los IDs de cargos de almacén (asistente y jefe)
    $warehousePositionIds = array_merge(
      Position::WAREHOUSE_ASSISTANT,
      Position::WAREHOUSE_MANAGER
    );

    // Obtener los usuarios con cargo de almacén asignados a la sede
    $warehouseUsers = User::whereHas('person', function ($query) use ($warehousePositionIds) {
      $query->whereIn('cargo_id', $warehousePositionIds)
        ->where('status_deleted', 1)
        ->where('status_id', 22);
    })
      ->whereHas('sedes', function ($query) use ($sedeId) {
        $query->where('config_sede.id', $sedeId)
          ->where('assigment_user_sede.status', true);
      })
      ->with('person')
      ->get();

    if ($warehouseUsers->isEmpty()) {
      \Log::warning("Solicitud de compra {$purchaseRequest->request_number}: No se encontraron encargados de almacén para la sede {$sedeId}.");
      return;
    }

    // Preparar datos para el correo
    $emailData = [
      'request_number' => $purchaseRequest->request_number,
      'requested_date' => $purchaseRequest->requested_date
        ? $purchaseRequest->requested_date->format('d/m/Y')
        : $purchaseRequest->created_at->format('d/m/Y'),
      'observations' => $purchaseRequest->observations ?? '',

      // Almacén y Sede
      'warehouse_name' => $warehouse->description ?? 'N/A',
      'sede_name' => $warehouse->sede?->abreviatura ?? 'N/A',

      // Solicitante
      'requested_by_name' => $purchaseRequest->requestedBy?->person?->nombre_completo ?? $purchaseRequest->requestedBy?->name ?? 'N/A',
      'requested_by_email' => $purchaseRequest->requestedBy?->person?->email2 ?? 'N/A',

      // Detalles de productos
      'details' => $purchaseRequest->details->map(function ($detail) {
        return [
          'product_code' => $detail->product?->code ?? 'N/A',
          'product_name' => $detail->product?->name ?? 'N/A',
          'quantity' => $detail->quantity,
          'supply_type' => $detail->supply_type ?? 'N/A',
          'notes' => $detail->notes ?? '',
        ];
      }),

      // URL del frontend
      'button_url' => config('app.frontend_url') . '/ap/post-venta/gestion-de-almacen/solicitud-compra-almacen',
    ];

    $subject = 'Nueva Solicitud de Compra - ' . $purchaseRequest->request_number;

    // Enviar correo a los encargados de almacén
    foreach ($warehouseUsers as $user) {
      $workerEmail = $user->person?->email2;

      if ($workerEmail) {
        try {
          $this->emailService->queue([
            'to' => $workerEmail,
            'subject' => $subject,
            'template' => 'emails.purchase-request-notification',
            'data' => array_merge($emailData, [
              'recipient_name' => $user->person->nombre_completo ?? 'Encargado de Almacén',
              'recipient_role' => in_array($user->person->cargo_id, Position::WAREHOUSE_MANAGER)
                ? 'Jefe de Almacén'
                : 'Asistente de Almacén',
            ]),
          ]);
        } catch (Exception $e) {
          \Log::error("Error al enviar correo al encargado de almacén (User ID: {$user->id}): " . $e->getMessage());
        }
      }
    }
  }

  /**
   * Cancela una solicitud de compra
   */
  public function cancel($data)
  {
    return DB::transaction(function () use ($data) {
      $id = $data['id'];
      $purchaseRequest = $this->find($id);
      $user = auth()->user();

      if ($purchaseRequest->status === ApOrderPurchaseRequests::CANCELLED) {
        throw new Exception('La solicitud de compra ya ha sido cancelada.');
      }

      if ($purchaseRequest->approved) {
        throw new Exception('No se puede cancelar una solicitud de compra que ya ha sido aprobada.');
      }

      $positionId = (int)($user->person?->position?->id ?? 0);

      $isJefe = in_array($positionId, Position::POSITION_JEFE_PVT_IDS, true);
      $isGerente = in_array($positionId, Position::POSITION_GERENTE_PV_IDS, true);

      if (!($isJefe || $isGerente)) {
        throw new Exception('Solo Jefe o Gerente de Postventa pueden cancelar esta solicitud de compra.');
      }

      $purchaseRequest->update([
        'reviewed_by' => $user->id,
        'status' => ApOrderPurchaseRequests::CANCELLED,
        'reviewed_at' => now(),
      ]);

      return new ApOrderPurchaseRequestsResource($purchaseRequest);
    });
  }

  public function notifyManagersForApproval(int $id)
  {
    return DB::transaction(function () use ($id) {
      $purchaseRequest = ApOrderPurchaseRequests::with([
        'warehouse.sede',
        'requestedBy.person',
        'details.product'
      ])->find($id);

      if (!$purchaseRequest) {
        throw new Exception('Solicitud de compra no encontrada');
      }

      if ($purchaseRequest->notified_at) {
        throw new Exception('Ya se ha enviado la notificación de aprobación para esta solicitud.');
      }

      // Obtener usuarios con cargo de Jefe y Gerente de Postventa
      $managerPositionIds = array_merge(
        Position::POSITION_JEFE_PVT_IDS,
        Position::POSITION_GERENTE_PV_IDS
      );

      $managers = User::whereHas('person', function ($query) use ($managerPositionIds) {
        $query->whereIn('cargo_id', $managerPositionIds)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })
        ->with('person.position')
        ->get();

      if ($managers->isEmpty()) {
        throw new Exception('No se encontraron jefes o gerentes de postventa para enviar la notificación.');
      }

      // Preparar datos para el correo
      $emailData = [
        'request_number' => $purchaseRequest->request_number,
        'requested_date' => $purchaseRequest->requested_date
          ? $purchaseRequest->requested_date->format('d/m/Y')
          : $purchaseRequest->created_at->format('d/m/Y'),
        'observations' => $purchaseRequest->observations ?? '',

        // Almacén y Sede
        'warehouse_name' => $purchaseRequest->warehouse->description ?? 'N/A',
        'sede_name' => $purchaseRequest->warehouse->sede?->abreviatura ?? 'N/A',

        // Solicitante
        'requested_by_name' => $purchaseRequest->requestedBy?->person?->nombre_completo ?? $purchaseRequest->requestedBy?->name ?? 'N/A',
        'requested_by_email' => $purchaseRequest->requestedBy?->person?->email2 ?? 'N/A',

        // Detalles de productos
        'details' => $purchaseRequest->details->map(function ($detail) {
          return [
            'product_code' => $detail->product?->code ?? 'N/A',
            'product_name' => $detail->product?->name ?? 'N/A',
            'quantity' => $detail->quantity,
            'supply_type' => $detail->supply_type ?? 'N/A',
            'notes' => $detail->notes ?? '',
          ];
        }),

        // URL del frontend
        'button_url' => config('app.frontend_url') . '/ap/post-venta/gestion-de-almacen/solicitud-compra-almacen',
      ];

      $subject = 'Solicitud de Aprobación de Compra - ' . $purchaseRequest->request_number;

      // Enviar correo a cada jefe y gerente
      foreach ($managers as $manager) {
        $managerEmail = $manager->person?->email2;

        if ($managerEmail) {
          try {
            $isGerente = in_array($manager->person->cargo_id, Position::POSITION_GERENTE_PV_IDS);

            $this->emailService->queue([
              'to' => $managerEmail,
              'subject' => $subject,
              'template' => 'emails.purchase-request-notification',
              'data' => array_merge($emailData, [
                'recipient_name' => $manager->person->nombre_completo ?? 'Jefatura',
                'recipient_role' => $isGerente ? 'Gerente de Postventa' : 'Jefe de Postventa',
              ]),
            ]);
          } catch (Exception $e) {
            \Log::error("Error al enviar correo al manager (User ID: {$manager->id}): " . $e->getMessage());
          }
        }
      }

      // Actualizar el campo notified_at con la fecha actual
      $purchaseRequest->update([
        'notified_at' => now(),
      ]);

      return response()->json([
        'message' => 'Notificación enviada correctamente a jefatura y gerencia.',
        'notified_at' => $purchaseRequest->notified_at,
      ]);
    });
  }
}

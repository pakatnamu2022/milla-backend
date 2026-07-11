<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use App\Http\Resources\ap\postventa\taller\ApSupplierOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Utils\Constants;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequestDetails;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use App\Models\ap\postventa\taller\ApSupplierOrder;
use App\Models\ap\postventa\taller\ApSupplierOrderDetails;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApSupplierOrderService extends BaseService implements BaseServiceInterface
{
  protected ?EmailService $emailService;

  public function __construct(?EmailService $emailService = null)
  {
    $this->emailService = $emailService ?? new EmailService();
  }

  public function list(Request $request)
  {
    $query = ApSupplierOrder::query()->with([
      'receptions',
      'supplier',
      'sede',
      'warehouse',
      'typeCurrency'
    ]);

    return $this->getFilteredResults(
      $query,
      $request,
      ApSupplierOrder::filters,
      ApSupplierOrder::sorts,
      ApSupplierOrderResource::class
    );
  }

  public function find($id)
  {
    $supplierOrder = ApSupplierOrder::with([
      'supplier',
      'sede',
      'warehouse',
      'typeCurrency',
      'createdBy',
      'details.product',
      'details.unitMeasurement'
    ])->where('id', $id)->first();

    if (!$supplierOrder) {
      throw new Exception('Orden de proveedor no encontrada');
    }

    return $supplierOrder;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Get exchange rate for order date
      $date = Carbon::parse($data['order_date'])->format('Y-m-d');
      $warehouse = Warehouse::find($data['warehouse_id']);

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }
      $data['exchange_rate'] = $exchangeRate->rate;
      $data['sede_id'] = $warehouse->sede_id;

      // Set created_by
      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      // Extract details from data
      $details = $data['details'] ?? [];
      unset($data['details']);

      // Validar que no haya product_id duplicados en los detalles
      if (!empty($details)) {
        $productIds = array_column($details, 'product_id');
        $duplicates = array_unique(array_diff_assoc($productIds, array_unique($productIds)));
        if (!empty($duplicates)) {
          throw new Exception('No se permite registrar el mismo producto más de una vez en la orden. Productos duplicados: ' . implode(', ', $duplicates));
        }
      }

      // Validar decimales según la unidad de medida de cada producto
      if (!empty($details)) {
        foreach ($details as $detail) {
          if (isset($detail['product_id']) && isset($detail['quantity'])) {
            $product = Products::find($detail['product_id']);
            if ($product) {
              $product->validateDecimals($detail['quantity']);
            }
          }
        }
      }

      // Calculate net_amount from details
      $netAmount = 0;
      if (!empty($details)) {
        foreach ($details as $detail) {
          $netAmount += $detail['total'] ?? 0;
        }
      }

      if ($netAmount <= 0) {
        throw new Exception('El monto neto de la orden de compra no puede ser cero.');
      }

      $data['net_amount'] = $netAmount;

      // Calculate tax_amount based on supplier's tax class IGV
      $supplier = BusinessPartners::with('supplierTaxClassType')->find($data['supplier_id']);

      if (!$supplier) {
        throw new Exception('Proveedor no encontrado.');
      }

      $igvRate = 0;
      if ($supplier->supplierTaxClassType && $supplier->supplierTaxClassType->igv != 0) {
        $igvRate = $supplier->supplierTaxClassType->igv;
      }

      $data['tax_amount'] = $igvRate > 0 ? round($netAmount * ($igvRate / 100), 2) : 0;

      // Calculate total_amount (net_amount + tax_amount)
      $data['total_amount'] = round($netAmount + $data['tax_amount'], 2);

      // Generate automatic order_number
      $data['order_number'] = ApSupplierOrder::generateOrderNumber();

      // Create supplier order
      $supplierOrder = ApSupplierOrder::create($data);

      // OPCIONAL: Vincular solicitudes si se proporcionan
      if (isset($data['request_detail_ids']) && !empty($data['request_detail_ids'])) {
        // Validar que las fechas de solicitud no sean mayores a la fecha del pedido
        $this->validateRequestDates($data['request_detail_ids'], $data['order_date']);
        $this->linkPurchaseRequests($supplierOrder, $data['request_detail_ids']);

        // Si hay alguna solicitud aprobada, tomar el reviewed_by de la primera
        $approvedRequestUserId = DB::table('ap_order_purchase_request_details as details')
          ->join('ap_order_purchase_requests as requests', 'details.order_purchase_request_id', '=', 'requests.id')
          ->whereIn('details.id', $data['request_detail_ids'])
          ->where('requests.approved', 1)
          ->whereNotNull('requests.reviewed_by')
          ->value('requests.reviewed_by');

        if ($approvedRequestUserId) {
          $supplierOrder->update(['approved_by' => $approvedRequestUserId]);
        }
      }

      // Create details
      if (!empty($details)) {
        foreach ($details as $detail) {
          $detail['ap_supplier_order_id'] = $supplierOrder->id;

          // Obtener unit_measurement_id desde el producto si no viene en el request
          if (!isset($detail['unit_measurement_id'])) {
            $product = Products::find($detail['product_id']);
            if ($product && $product->unit_measurement_id) {
              $detail['unit_measurement_id'] = $product->unit_measurement_id;
            }
          }

          ApSupplierOrderDetails::create($detail);
        }
      }

      // Enviamos notificación a gerencia solo si no fue aprobada automáticamente
      $supplierOrder->refresh();
      if (!$supplierOrder->approved_by) {
        $this->notifyManagersForApproval($supplierOrder->id);
      }

      return new ApSupplierOrderResource($supplierOrder->load([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]));
    });
  }

  public function show($id)
  {
    $apSupplierOrder = $this->find($id);
    $apSupplierOrder->load([
      'supplier',
      'sede',
      'warehouse',
      'typeCurrency',
      'createdBy',
      'details.product',
      'requestDetails.orderPurchaseRequest.requestedBy.person',
      'receptions.purchaseOrder',
    ]);
    return new ApSupplierOrderResource($apSupplierOrder);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $supplierOrder = $this->find($data['id']);
      $warehouse = Warehouse::find($data['warehouse_id']);

      if ($supplierOrder->hasAnnulledReceptions()) {
        throw new Exception('No se puede editar un pedido a proveedor que tiene recepciones anuladas asociadas.');
      }

      if ($supplierOrder->discarded_by) {
        throw new Exception('No se puede editar un pedido a proveedor que ha sido descartado.');
      }

      $data['sede_id'] = $warehouse->sede_id;

      // Extract details from data
      $details = $data['details'] ?? null;
      unset($data['details']);

      // Update details if provided and recalculate amounts
      if ($details !== null) {
        // Validar que no haya product_id duplicados en los detalles
        $productIds = array_column($details, 'product_id');
        $duplicates = array_unique(array_diff_assoc($productIds, array_unique($productIds)));
        if (!empty($duplicates)) {
          throw new Exception('No se permite registrar el mismo producto más de una vez en la orden. Productos duplicados: ' . implode(', ', $duplicates));
        }

        // Validar decimales según la unidad de medida de cada producto
        foreach ($details as $detail) {
          if (isset($detail['product_id']) && isset($detail['quantity'])) {
            $product = Products::find($detail['product_id']);
            if ($product) {
              $product->validateDecimals($detail['quantity']);
            }
          }
        }

        // Calculate new net_amount from details
        $netAmount = 0;
        foreach ($details as $detail) {
          $netAmount += $detail['total'] ?? 0;
        }
        $data['net_amount'] = $netAmount;

        // Calculate tax_amount based on supplier's tax class IGV
        $supplierId = $data['supplier_id'] ?? $supplierOrder->supplier_id;
        $supplier = BusinessPartners::with('supplierTaxClassType')->find($supplierId);

        if (!$supplier) {
          throw new Exception('Proveedor no encontrado.');
        }

        $igvRate = 0;
        if ($supplier->supplierTaxClassType && $supplier->supplierTaxClassType->igv != 0) {
          $igvRate = $supplier->supplierTaxClassType->igv;
        }

        $data['tax_amount'] = $igvRate > 0 ? round($netAmount * ($igvRate / 100), 2) : 0;

        // Calculate total_amount (net_amount + tax_amount)
        $data['total_amount'] = round($netAmount + $data['tax_amount'], 2);

        // Delete existing details
        ApSupplierOrderDetails::where('ap_supplier_order_id', $supplierOrder->id)->delete();

        // Create new details
        foreach ($details as $detail) {
          $detail['ap_supplier_order_id'] = $supplierOrder->id;

          // Obtener unit_measurement_id desde el producto si no viene en el request
          if (!isset($detail['unit_measurement_id'])) {
            $product = Products::find($detail['product_id']);
            if ($product && $product->unit_measurement_id) {
              $detail['unit_measurement_id'] = $product->unit_measurement_id;
            }
          }

          ApSupplierOrderDetails::create($detail);
        }
      }

      // Validar fechas de solicitudes vinculadas si se cambia order_date
      if (isset($data['order_date']) && $data['order_date'] !== $supplierOrder->order_date) {
        // Obtener IDs de solicitudes ya vinculadas
        $linkedRequestDetailIds = $supplierOrder->requestDetails()->pluck('ap_order_purchase_request_details.id')->toArray();

        if (!empty($linkedRequestDetailIds)) {
          // Validar que las solicitudes vinculadas sigan siendo válidas con la nueva fecha
          $this->validateRequestDates($linkedRequestDetailIds, $data['order_date']);
        }
      }

      // OPCIONAL: Validar nuevas solicitudes si se proporcionan (caso raro en update)
      if (isset($data['request_detail_ids']) && !empty($data['request_detail_ids'])) {
        $orderDate = $data['order_date'] ?? $supplierOrder->order_date;
        $this->validateRequestDates($data['request_detail_ids'], $orderDate);
      }

      // Update supplier order
      $supplierOrder->update($data);

      // Reload relations
      $supplierOrder->load([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]);

      return new ApSupplierOrderResource($supplierOrder);
    });
  }

  public function destroy($id)
  {
    $supplierOrder = $this->find($id);

    if ($supplierOrder->hasAnnulledReceptions()) {
      throw new Exception('No se puede eliminar un pedido a proveedor que tiene recepciones anuladas asociadas.');
    }

    if ($supplierOrder->discarded_by) {
      throw new Exception('No se puede eliminar un pedido a proveedor que ha sido descartado.');
    }

    DB::transaction(function () use ($supplierOrder) {
      // Liberar items de solicitudes de compra vinculadas
      $this->releaseLinkedPurchaseRequests($supplierOrder);

      // Delete details (cascade will handle this, but explicit deletion is clearer)
      ApSupplierOrderDetails::where('ap_supplier_order_id', $supplierOrder->id)->delete();

      // Delete supplier order
      $supplierOrder->delete();
    });

    return response()->json(['message' => 'Orden de proveedor eliminada correctamente.']);
  }

  public function updateStatus(int $id, string $status)
  {
    $supplierOrder = $this->find($id);

    $allowedStatuses = ['pending', 'approved', 'rejected', 'completed'];

    if (!in_array($status, $allowedStatuses)) {
      throw new Exception("Estado no válido. Los estados permitidos son: " . implode(', ', $allowedStatuses));
    }

    $supplierOrder->update(['status' => $status]);

    return response()->json([
      'message' => 'Estado actualizado correctamente',
      'data' => new ApSupplierOrderResource($supplierOrder->fresh([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]))
    ]);
  }

  /**
   * Valida que las fechas de solicitud de compra no sean mayores a la fecha del pedido
   *
   * @param array $requestDetailIds
   * @param string $orderDate
   * @return void
   * @throws Exception
   */
  protected function validateRequestDates(array $requestDetailIds, string $orderDate): void
  {
    if (empty($requestDetailIds)) {
      return;
    }

    // Obtener las solicitudes de compra relacionadas con los detalles
    $requestHeaders = DB::table('ap_order_purchase_request_details as details')
      ->join('ap_order_purchase_requests as requests', 'details.order_purchase_request_id', '=', 'requests.id')
      ->whereIn('details.id', $requestDetailIds)
      ->select('requests.id', 'requests.request_number', 'requests.requested_date')
      ->distinct()
      ->get();

    $orderDateCarbon = Carbon::parse($orderDate)->startOfDay();
    $invalidRequests = [];

    foreach ($requestHeaders as $request) {
      $requestedDateCarbon = Carbon::parse($request->requested_date)->startOfDay();

      // Validar que la fecha de solicitud no sea mayor a la fecha del pedido
      if ($requestedDateCarbon->greaterThan($orderDateCarbon)) {
        $invalidRequests[] = sprintf(
          'Solicitud %s (Fecha: %s)',
          $request->request_number,
          $requestedDateCarbon->format('d/m/Y')
        );
      }
    }

    if (!empty($invalidRequests)) {
      throw new Exception(
        'Las siguientes solicitudes de compra tienen fechas posteriores a la fecha del pedido (' .
        $orderDateCarbon->format('d/m/Y') . '): ' .
        implode(', ', $invalidRequests) .
        '. La fecha de solicitud debe ser igual o anterior a la fecha del pedido.'
      );
    }
  }

  protected function linkPurchaseRequests(ApSupplierOrder $apSupplierOrder, array $requestDetailIds): void
  {
    // Vincular los detalles de solicitud a la orden de compra
    $apSupplierOrder->requestDetails()->attach($requestDetailIds);

    // Actualizar el status de los detalles a 'ordered'
    DB::table('ap_order_purchase_request_details')
      ->whereIn('id', $requestDetailIds)
      ->update(['status' => ApOrderPurchaseRequestDetails::STATUS_ORDERED]);

    // Actualizar el status de las cabeceras a 'ordered'
    $headerIds = DB::table('ap_order_purchase_request_details')
      ->whereIn('id', $requestDetailIds)
      ->pluck('order_purchase_request_id')
      ->unique()
      ->values()
      ->toArray();

    if (!empty($headerIds)) {
      DB::table('ap_order_purchase_requests')
        ->whereIn('id', $headerIds)
        ->update(['status' => ApOrderPurchaseRequests::ORDERED]);
    }
  }

  public function getPendingProducts($id)
  {
    $supplierOrder = $this->find($id);

    // 1. Obtener productos pedidos con relación de producto
    $orderedProducts = $supplierOrder->details()->with('product')->get()->mapWithKeys(function ($detail) {
      return [
        $detail->product_id => [
          'id' => $detail->id,
          'product_id' => $detail->product_id,
          'product' => $detail->product,
          'unit_measurement_id' => $detail->unit_measurement_id,
          'quantity_ordered' => $detail->quantity,
        ]
      ];
    });

    // 2. Consolidar recepciones por product_id sumando quantity_received
    // Si is_credit_note = true, también sumar observed_quantity porque ya no está pendiente
    // (se manejará con nota de crédito en la factura)
    $receivedProducts = DB::table('purchase_reception_details as prd')
      ->join('purchase_receptions as pr', 'prd.purchase_reception_id', '=', 'pr.id')
      ->where('pr.ap_supplier_order_id', $id)
      ->whereNull('pr.deleted_at')
      ->where('pr.status', '!=', 'ANNULLED')
      ->whereNull('prd.deleted_at')
      ->select(
        'prd.product_id',
        DB::raw('SUM(prd.quantity_received + IF(prd.is_credit_note = 1 OR prd.is_credit_note = true, prd.observed_quantity, 0)) as total_received')
      )
      ->groupBy('prd.product_id')
      ->get()
      ->pluck('total_received', 'product_id');

    // 3. Calcular pendientes
    $pendingProducts = [];
    foreach ($orderedProducts as $productId => $orderData) {
      $received = $receivedProducts[$productId] ?? 0;
      $pending = $orderData['quantity_ordered'] - $received;

      if ($pending > 0) {
        $pendingProducts[] = [
          'id' => $orderData['id'],
          'product_id' => $productId,
          'product' => new ProductsResource($orderData['product']),
          'unit_measurement_id' => $orderData['unit_measurement_id'],
          'quantity' => $pending,
        ];
      }
    }

    return $pendingProducts;
  }

  public function approve(int $id)
  {
    return DB::transaction(function () use ($id) {
      $apSupplierOrder = $this->find($id);
      $user = auth()->user();

      if ($apSupplierOrder->approved_by) {
        throw new Exception('No se puede aprobar una orden de compra que ya ha sido aprobada.');
      }

      $positionId = $user->person?->position?->id;

      $isAfterSalesCoordinator = in_array($positionId, Position::AFTER_SALES_COORDINATOR, true);
      $isGerente = in_array($positionId, Position::POSITION_GERENTE_PV_IDS, true);

      if (!($isAfterSalesCoordinator || $isGerente)) {
        throw new Exception('Solo Gerente o Coordinadora de Postventa pueden aprobar este pedido.');
      }

      $apSupplierOrder->update(['approved_by' => $user->id]);

      return new ApSupplierOrderResource($apSupplierOrder);
    });
  }

  public function generateSupplierOrderPDF($id)
  {
    $supplierOrder = ApSupplierOrder::with([
      'supplier',
      'sede.company',
      'warehouse',
      'typeCurrency',
      'approvedBy.person',
      'createdBy.person.position',
      'details.product',
      'details.unitMeasurement',
      'requestDetails.orderPurchaseRequest'
    ])->find($id);

    if (!$supplierOrder) {
      throw new Exception('Orden de compra no encontrada');
    }

    // Preparar datos para la vista
    $data = [
      'order_number' => $supplierOrder->order_number,
      'order_number_external' => $supplierOrder->order_number_external ?? 'N/A',
      'order_date' => $supplierOrder->order_date,
      'supply_type' => $supplierOrder->supply_type ?? 'N/A',
      'reception_type' => $supplierOrder->reception_type ?? 'N/A',
      'status' => $supplierOrder->status ?? 'N/A',
      'exchange_rate' => $supplierOrder->exchange_rate,
      'sede' => $supplierOrder->sede ?? 'N/A',
    ];

    // Datos del proveedor
    if ($supplierOrder->supplier) {
      $data['supplier_name'] = $supplierOrder->supplier->full_name ?? 'N/A';
      $data['supplier_document'] = $supplierOrder->supplier->num_doc ?? 'N/A';
      $data['supplier_address'] = $supplierOrder->supplier->direction ?? 'N/A';
      $data['supplier_phone'] = $supplierOrder->supplier->phone ?? 'N/A';
      $data['supplier_email'] = $supplierOrder->supplier->email ?? 'N/A';
    } else {
      $data['supplier_name'] = 'N/A';
      $data['supplier_document'] = 'N/A';
      $data['supplier_address'] = 'N/A';
      $data['supplier_phone'] = 'N/A';
      $data['supplier_email'] = 'N/A';
    }

    // Datos de almacén
    $data['warehouse_name'] = $supplierOrder->warehouse ? $supplierOrder->warehouse->description : 'N/A';

    // Datos de moneda
    $data['currency'] = $supplierOrder->typeCurrency ? $supplierOrder->typeCurrency->code : 'PEN';
    $data['currency_symbol'] = $supplierOrder->typeCurrency ? $supplierOrder->typeCurrency->symbol : 'S/';

    // Datos del usuario que creó la orden
    if ($supplierOrder->createdBy && $supplierOrder->createdBy->person) {
      $data['created_by_name'] = $supplierOrder->createdBy->person->nombre_completo ?? 'N/A';
      $data['created_by_email'] = $supplierOrder->createdBy->person->email2 ?? 'N/A';
      $data['created_by_phone'] = $supplierOrder->createdBy->person->celular ?? 'N/A';
      $data['created_by_position'] = $supplierOrder->createdBy->person->position->name ?? 'N/A';
    } else {
      $data['created_by_name'] = 'N/A';
      $data['created_by_email'] = 'N/A';
      $data['created_by_phone'] = 'N/A';
      $data['created_by_position'] = 'N/A';
    }

    // Datos del usuario que aprobó la orden
    if ($supplierOrder->approvedBy && $supplierOrder->approvedBy->person) {
      $data['approved_by_name'] = $supplierOrder->approvedBy->person->nombre_completo ?? 'N/A';
    } else {
      $data['approved_by_name'] = 'N/A';
    }

    // ID del proveedor
    $data['supplier_id'] = $supplierOrder->supplier_id ?? 'N/A';

    // Números de solicitudes de compra asociadas
    $purchaseRequestNumbers = $supplierOrder->requestDetails
      ->pluck('orderPurchaseRequest.request_number')
      ->unique()
      ->filter()
      ->values()
      ->toArray();
    $data['purchase_request_numbers'] = !empty($purchaseRequestNumbers)
      ? implode(', ', $purchaseRequestNumbers)
      : 'N/A';

    // Detalles de la orden
    $data['details'] = $supplierOrder->details->map(function ($detail) {
      return [
        'code' => $detail->product ? $detail->product->code : 'N/A',
        'description' => $detail->product ? $detail->product->name : 'N/A',
        'note' => $detail->note ?? '',
        'quantity' => $detail->quantity,
        'unit_measure' => $detail->unitMeasurement ? $detail->unitMeasurement->description : 'N/A',
        'unit_price' => $detail->unit_price,
        'total' => $detail->total,
      ];
    });

    // Totales
    $data['net_amount'] = $supplierOrder->net_amount;
    $data['tax_amount'] = $supplierOrder->tax_amount;
    $data['total_amount'] = $supplierOrder->total_amount;

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.supplier-order', [
      'order' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'OC_' . $supplierOrder->order_number . '.pdf';

    return $pdf->download($fileName);
  }

  public function notifyManagersForApproval(int $id)
  {
    return DB::transaction(function () use ($id) {
      $supplierOrder = ApSupplierOrder::with([
        'supplier',
        'warehouse.sede',
        'createdBy.person',
        'details.product',
        'typeCurrency'
      ])->find($id);

      if (!$supplierOrder) {
        throw new Exception('Pedido a proveedor no encontrado');
      }

      if ($supplierOrder->approved_by) {
        throw new Exception('Este pedido ya ha sido aprobado.');
      }

      // Obtener usuarios con cargo de Coordinadora de Postventa
      $managerPositionIds = Position::AFTER_SALES_COORDINATOR;

      $managers = User::whereHas('person', function ($query) use ($managerPositionIds) {
        $query->whereIn('cargo_id', $managerPositionIds)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })
        ->with('person.position')
        ->get();

      if ($managers->isEmpty()) {
        throw new Exception('No se encontraron coordinadoras de postventa para enviar la notificación.');
      }

      // Preparar datos para el correo
      $emailData = [
        'order_number' => $supplierOrder->order_number,
        'order_date' => $supplierOrder->order_date
          ? Carbon::parse($supplierOrder->order_date)->format('d/m/Y')
          : $supplierOrder->created_at->format('d/m/Y'),

        // Proveedor
        'supplier_name' => $supplierOrder->supplier->full_name ?? 'N/A',
        'supplier_document' => $supplierOrder->supplier->num_doc ?? 'N/A',

        // Almacén y Sede
        'warehouse_name' => $supplierOrder->warehouse->description ?? 'N/A',
        'sede_name' => $supplierOrder->warehouse->sede?->abreviatura ?? 'N/A',

        // Moneda y montos
        'currency_symbol' => $supplierOrder->typeCurrency->symbol ?? 'S/',
        'net_amount' => number_format($supplierOrder->net_amount, 2),
        'tax_amount' => number_format($supplierOrder->tax_amount, 2),
        'total_amount' => number_format($supplierOrder->total_amount, 2),

        // Creador
        'created_by_name' => $supplierOrder->createdBy?->person?->nombre_completo ?? $supplierOrder->createdBy?->name ?? 'N/A',
        'created_by_email' => $supplierOrder->createdBy?->person?->email2 ?? 'N/A',

        // Detalles de productos
        'details' => $supplierOrder->details->map(function ($detail) {
          return [
            'product_code' => $detail->product?->code ?? 'N/A',
            'product_name' => $detail->product?->name ?? 'N/A',
            'quantity' => $detail->quantity,
            'unit_price' => $detail->unit_price,
            'total' => $detail->total,
            'note' => $detail->note ?? '',
          ];
        }),

        // URL del frontend
        'button_url' => config('app.frontend_url') . '/ap/post-venta/gestion-de-almacen/compra-proveedor',
      ];

      $subject = 'Solicitud de Aprobación de Orden de Compra - ' . $supplierOrder->order_number;

      // Enviar correo a cada coordinadora
      foreach ($managers as $manager) {
        $managerEmail = $manager->person?->email2;

        if ($managerEmail) {
          try {
            $this->emailService->queue([
              'to' => $managerEmail,
              'subject' => $subject,
              'template' => 'emails.supplier-order-approval-notification',
              'data' => array_merge($emailData, [
                'recipient_name' => $manager->person->nombre_completo ?? 'Coordinadora de Postventa',
                'recipient_role' => 'Coordinadora de Postventa',
              ]),
            ]);
          } catch (Exception $e) {
            \Log::error("Error al enviar correo al manager (User ID: {$manager->id}): " . $e->getMessage());
          }
        }
      }

      return response()->json([
        'message' => 'Notificación enviada correctamente a coordinadora de postventa para aprobación del pedido.',
      ]);
    });
  }

  /**
   * Actualizar el reception_type del ApSupplierOrder basado en productos pendientes
   *
   * Esta es la única fuente de verdad para calcular el reception_type:
   * - COMPLETE: Si no hay productos pendientes (todo recibido)
   * - PARTIAL: Si hay productos pendientes Y existen recepciones activas (algo recibido)
   * - PENDING: Si hay productos pendientes Y NO existen recepciones activas (nada recibido)
   *
   * @param ApSupplierOrder $supplierOrder
   * @return void
   */
  public function updateReceptionType(ApSupplierOrder $supplierOrder): void
  {
    $pendingProducts = $this->getPendingProducts($supplierOrder->id);

    // Verificar si existen recepciones activas (no anuladas, no eliminadas)
    $hasActiveReceptions = PurchaseReception::where('ap_supplier_order_id', $supplierOrder->id)
      ->where('status', '!=', 'ANNULLED')
      ->whereNull('deleted_at')
      ->exists();

    // Determinar el reception_type
    if (empty($pendingProducts)) {
      $receptionType = ApSupplierOrder::COMPLETE;
    } else {
      $receptionType = $hasActiveReceptions ? ApSupplierOrder::PARTIAL : ApSupplierOrder::PENDING;
    }

    $supplierOrder->update(['reception_type' => $receptionType]);
  }

  public function discard(int $id, string $reasonCancellation)
  {
    return DB::transaction(function () use ($id, $reasonCancellation) {
      $supplierOrder = $this->find($id);

      if ($supplierOrder->discarded_by) {
        throw new Exception('Este pedido a proveedor ya ha sido anulado.');
      }

      if ($supplierOrder->hasActiveReceptions()) {
        throw new Exception('No se puede anular un pedido a proveedor que tiene recepciones activas asociadas. Por favor, anule primero las recepciones vinculadas.');
      }

      if (!$supplierOrder->hasAnnulledReceptions()) {
        throw new Exception('No se puede anular un pedido a proveedor que no tiene recepciones anuladas asociadas.');
      }

      if (!is_null($supplierOrder->ap_purchase_order_id)) {
        throw new Exception('No se puede anular una orden al proveedor que ya se le ha registrado una factura.');
      }

      // Liberar items de solicitudes de compra vinculadas
      $this->releaseLinkedPurchaseRequests($supplierOrder);

      $supplierOrder->update([
        'discarded_by' => auth()->user()->id,
        'reason_cancellation' => $reasonCancellation,
        'discarded_at' => now(),
        'status' => false,
      ]);

      return new ApSupplierOrderResource($supplierOrder);
    });
  }

  /**
   * Libera los items de las solicitudes de compra vinculadas a un pedido a proveedor.
   *
   * Esta es la única fuente de verdad para liberar items cuando se elimina o descarta
   * un pedido a proveedor. Revierte el status de los detalles y encabezados de las
   * solicitudes de compra a 'pending' y desvincula la relación.
   *
   * @param ApSupplierOrder $supplierOrder
   * @return void
   */
  protected function releaseLinkedPurchaseRequests(ApSupplierOrder $supplierOrder): void
  {
    // Get linked request detail IDs before detaching
    $requestDetailIds = $supplierOrder->requestDetails()->pluck('ap_order_purchase_request_details.id')->toArray();

    // Revert status of linked purchase request details back to 'pending'
    if (!empty($requestDetailIds)) {
      DB::table('ap_order_purchase_request_details')
        ->whereIn('id', $requestDetailIds)
        ->update(['status' => ApOrderPurchaseRequestDetails::STATUS_PENDING]);

      // Revert status of parent purchase request headers back to 'pending'
      $headerIds = DB::table('ap_order_purchase_request_details')
        ->whereIn('id', $requestDetailIds)
        ->pluck('order_purchase_request_id')
        ->unique()
        ->values()
        ->toArray();

      if (!empty($headerIds)) {
        DB::table('ap_order_purchase_requests')
          ->whereIn('id', $headerIds)
          ->update(['status' => ApOrderPurchaseRequests::PENDING]);
      }

      // Detach the relationship
      $supplierOrder->requestDetails()->detach();
    }
  }
}

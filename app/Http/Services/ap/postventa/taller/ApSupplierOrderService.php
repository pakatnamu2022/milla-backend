<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use App\Http\Resources\ap\postventa\taller\ApSupplierOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Utils\Constants;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\maestroGeneral\Warehouse;
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
  protected EmailService $emailService;

  public function __construct(EmailService $emailService)
  {
    $this->emailService = $emailService;
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
        $this->linkPurchaseRequests($supplierOrder, $data['request_detail_ids']);
      }

      // Create details
      if (!empty($details)) {
        foreach ($details as $detail) {
          $detail['ap_supplier_order_id'] = $supplierOrder->id;
          ApSupplierOrderDetails::create($detail);
        }
      }

      //Enviamos notificación a gerencia
      $this->notifyManagersForApproval($supplierOrder->id);

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

      if (!is_null($supplierOrder->ap_purchase_order_id)) {
        throw new Exception('No se puede editar una orden al proveedor que ya se le ha registrado una factura.');
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
          ApSupplierOrderDetails::create($detail);
        }
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

    if (!is_null($supplierOrder->ap_purchase_order_id)) {
      throw new Exception('No se puede eliminar una orden al proveedor que ya se le ha registrado una factura.');
    }

    DB::transaction(function () use ($supplierOrder) {
      // Get linked request detail IDs before deleting
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

    // 2. Consolidar recepciones por product_id sumando solo quantity_received
    $receivedProducts = DB::table('purchase_reception_details as prd')
      ->join('purchase_receptions as pr', 'prd.purchase_reception_id', '=', 'pr.id')
      ->where('pr.ap_supplier_order_id', $id)
      ->whereNull('pr.deleted_at')
      ->whereNull('prd.deleted_at')
      ->select('prd.product_id', DB::raw('SUM(prd.quantity_received) as total_received'))
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

      // Validar aprobación de gerente o coordinador de post venta
      if (!in_array($positionId, array_merge(Position::POSITION_GERENTE_PV_IDS, Position::AFTER_SALES_COORDINATOR))) {
        throw new Exception('Solo puede ser aprobado por gerente o coordinador de post venta.');
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

      // Obtener usuarios con cargo de Jefe y Gerente de Postventa
      $managerPositionIds = array_merge(
        Position::AFTER_SALES_COORDINATOR,
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

      // Enviar correo a cada jefe y gerente
      foreach ($managers as $manager) {
        $managerEmail = $manager->person?->email2;

        if ($managerEmail) {
          try {
            $isGerente = in_array($manager->person->cargo_id, Position::POSITION_GERENTE_PV_IDS);

            $this->emailService->queue([
              'to' => $managerEmail,
              'subject' => $subject,
              'template' => 'emails.supplier-order-approval-notification',
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

      return response()->json([
        'message' => 'Notificación enviada correctamente a jefatura y gerencia para aprobación del pedido.',
      ]);
    });
  }
}

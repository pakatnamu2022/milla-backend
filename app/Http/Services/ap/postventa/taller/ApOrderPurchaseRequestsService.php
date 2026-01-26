<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderPurchaseRequestsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequestDetails;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderPurchaseRequestsService extends BaseService implements BaseServiceInterface
{

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
      'apOrderQuotation',
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

      // Set requested_by
      if (auth()->check()) {
        $data['requested_by'] = auth()->user()->id;
      }

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

      return new ApOrderPurchaseRequestsResource($purchaseRequest->load([
        'apOrderQuotation',
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
      'apOrderQuotation',
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

      if ($purchaseRequest->ap_order_quotation_id) {
        throw new Exception("No se puede modificar una solicitud de compra asociada a una cotización.");
      }

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
        'apOrderQuotation',
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
          "El producto '{$productName}' no está registrado en este almacén. " .
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

  /**
   * Genera el PDF de la solicitud de compra
   * Si está asociada a una cotización, toma los precios de ella
   * Si no, muestra guiones "-"
   * @param int $id
   * @return \Illuminate\Http\Response
   * @throws Exception
   */
  public function generatePurchaseRequestPDF(int $id)
  {
    $purchaseRequest = ApOrderPurchaseRequests::with([
      'apOrderQuotation.client.district',
      'apOrderQuotation.details.product',
      'apOrderQuotation.typeCurrency',
      'apOrderQuotation.createdBy.person',
      'apOrderQuotation.vehicle.model.family.brand',
      'warehouse',
      'requestedBy.person',
      'details.product'
    ])->find($id);

    if (!$purchaseRequest) {
      throw new Exception('Solicitud de compra no encontrada');
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
    ];

    // Datos del proveedor/cliente
    if ($hasQuotation && $quotation->client) {
      $client = $quotation->client;
      $data['supplier_name'] = $client->full_name ?? '-';
      $data['supplier_ruc'] = $client->num_doc ?? '-';
      $data['supplier_address'] = $client->direction ?? '-';
      $data['supplier_ubigeo'] = $client->ubigeo ?? '-';
      $data['supplier_city'] = $client->district ? $client->district->name . ' - ' . ($client->district->province->name ?? '') : '-';
      $data['supplier_phone'] = $client->phone ?? '-';
      $data['supplier_email'] = $client->email ?? '-';
    } else {
      $data['supplier_name'] = '-';
      $data['supplier_ruc'] = '-';
      $data['supplier_address'] = '-';
      $data['supplier_ubigeo'] = '-';
      $data['supplier_city'] = '-';
      $data['supplier_phone'] = '-';
      $data['supplier_email'] = '-';
    }

    // Datos del vendedor/asesor
    if ($hasQuotation && $quotation->createdBy && $quotation->createdBy->person) {
      $data['advisor_name'] = $quotation->createdBy->id . ' - ' . ($quotation->createdBy->person->nombre_completo ?? '-');
    } elseif ($purchaseRequest->requestedBy && $purchaseRequest->requestedBy->person) {
      $data['advisor_name'] = $purchaseRequest->requestedBy->id . ' - ' . ($purchaseRequest->requestedBy->person->nombre_completo ?? '-');
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

      // Buscar precio en la cotización si existe
      $price = '-';
      $discount = '-';
      $lineTotal = '-';
      $procedure = 'CENTRAL';

      if ($hasQuotation) {
        $quotationDetail = $quotation->details
          ->where('product_id', $detail->product_id)
          ->first();

        if ($quotationDetail) {
          $price = $quotationDetail->unit_price;
          $discount = $quotationDetail->discount_percentage > 0 ? $quotationDetail->discount_percentage : '';
          $lineTotal = $quotationDetail->total_amount;
          $total += $lineTotal;
        }
      }

      $details[] = [
        'code' => $code,
        'description' => $description,
        'procedure' => $procedure,
        'quantity' => number_format($quantity, 2),
        'price' => is_numeric($price) ? number_format($price, 2) : $price,
        'discount' => is_numeric($discount) ? number_format($discount, 2) : $discount,
        'total' => is_numeric($lineTotal) ? number_format($lineTotal, 2) : $lineTotal,
      ];
    }

    $data['details'] = $details;
    $data['total'] = $hasQuotation ? number_format($total, 2) : '-';
    $data['observations'] = $purchaseRequest->observations ?? '';

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-purchase-request', [
      'purchaseRequest' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Solicitud_Compra_' . $purchaseRequest->request_number . '.pdf';

    return $pdf->download($fileName);
  }
}

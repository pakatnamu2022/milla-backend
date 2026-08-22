<?php

namespace App\Http\Services\ap\compras;

use App\Http\Services\ap\postventa\taller\ApOrderPurchaseRequestsService;
use App\Http\Services\common\EmailService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\gp\gestionsistema\Position;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio centralizado para gestionar las notificaciones cuando un comprobante
 * es recepcionado y contabilizado en Dynamics.
 *
 * Notifica a 3 grupos de stakeholders:
 * 1. Gerencia de Postventa
 * 2. Jefes de Almacén
 * 3. Usuarios que solicitaron los productos
 */
class InvoiceAccountedNotificationService
{
  protected EmailService $emailService;
  protected PurchaseReceptionService $receptionService;
  protected ApOrderPurchaseRequestsService $purchaseRequestsService;

  public function __construct(
    EmailService $emailService,
    PurchaseReceptionService $receptionService,
    ApOrderPurchaseRequestsService $purchaseRequestsService
  ) {
    $this->emailService = $emailService;
    $this->receptionService = $receptionService;
    $this->purchaseRequestsService = $purchaseRequestsService;
  }

  /**
   * Notifica a todos los stakeholders cuando el comprobante está recepcionado
   *
   * @param PurchaseOrder $purchaseOrder
   * @return void
   */
  public function notifyAll(PurchaseOrder $purchaseOrder): void
  {
    // 1. Notificar a gerencia
    try {
      $this->notifyManagers($purchaseOrder);
    } catch (Throwable $e) {
      Log::error("Error al notificar a gerencia para OC #{$purchaseOrder->id}: {$e->getMessage()}");
    }

    // 2. Notificar a jefes de almacén
    try {
      $this->notifyWarehouseManagers($purchaseOrder);
    } catch (Throwable $e) {
      Log::error("Error al notificar a jefes de almacén para OC #{$purchaseOrder->id}: {$e->getMessage()}");
    }

    // 3. Notificar a usuarios que pidieron
    if ($purchaseOrder->reception?->supplierOrder) {
      try {
        $this->receptionService->notifyRequestUsers($purchaseOrder->reception->supplierOrder);
      } catch (Throwable $e) {
        Log::error("Error al notificar a usuarios solicitantes para OC #{$purchaseOrder->id}: {$e->getMessage()}");
      }
    }
  }

  /**
   * Notifica a gerencia cuando el comprobante está recepcionado, con el detalle
   * de los repuestos recibidos en la recepción (purchase_receptions / purchase_reception_details)
   *
   * @param PurchaseOrder $purchaseOrder
   * @return void
   */
  public function notifyManagers(PurchaseOrder $purchaseOrder): void
  {
    // Cargar relaciones necesarias
    $purchaseOrder->load([
      'sede.province',
      'sede.district',
      'sede.company',
      'supplier',
      'creator.person',
      'currency',
      'reception.warehouse',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.requestedBy.person',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.apOrderQuotation.vehicle',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.apOrderQuotation.client',
      'reception.supplierOrder.requestDetails.orderPurchaseRequest.apOrderQuotation.createdBy.person',
      'reception.details.product.brand',
      'reception.details.product.model',
      'reception.details.purchaseOrderItem',
    ]);

    // Verificar que tenga sede
    if (!$purchaseOrder->sede_id) {
      Log::warning("OC #{$purchaseOrder->number}: No se pudo obtener la sede de la orden de compra.");
      return;
    }

    $sedeId = $purchaseOrder->sede_id;

    // Obtener solo usuarios con cargo de Gerente de Postventa asignados a la sede
    $managers = User::whereHas('person', function ($query) {
      $query->whereIn('cargo_id', Position::POSITION_GERENTE_PV_IDS)
        ->where('status_deleted', 1)
        ->where('status_id', 22);
    })
      ->whereHas('sedes', function ($query) use ($sedeId) {
        $query->where('config_sede.id', $sedeId)
          ->where('assigment_user_sede.status', true);
      })
      ->with('person')
      ->get();

    if ($managers->isEmpty()) {
      Log::warning("OC #{$purchaseOrder->number}: No se encontraron gerentes de postventa para la sede {$sedeId}.");
      return;
    }

    // Preparar datos para el email y el PDF
    $sedeAbbreviation = $purchaseOrder->sede?->abreviatura ?? 'N/A';
    $subject = 'Informe de llegada de repuestos en Almacén PAKATNAMU ' . $sedeAbbreviation;

    $emailData = [
      'purchase_order_number' => $purchaseOrder->number,
      'sede_name' => $purchaseOrder->sede?->name ?? 'N/A',
      'sede_abbreviation' => $sedeAbbreviation,
      'supplier_name' => $purchaseOrder->supplier?->full_name ?? 'N/A',
      'responsible_name' => $purchaseOrder->creator?->person?->nombre_completo ?? 'N/A',
    ];

    // Preparar datos para el PDF
    $pdfData = $this->preparePdfData($purchaseOrder);

    // Generar el PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.purchase-reception-detail', $pdfData);
    $pdf->setPaper('a4', 'landscape');

    $pdfPath = tempnam(sys_get_temp_dir(), 'purchase_reception_') . '.pdf';
    file_put_contents($pdfPath, $pdf->output());
    $pdfFileName = 'Recepcion_OC_' . $purchaseOrder->number . '_' . now()->format('Ymd') . '.pdf';

    // Enviar correo a cada gerente
    foreach ($managers as $manager) {
      $managerEmail = $manager->person?->email2;

      if ($managerEmail) {
        try {
          $this->emailService->queue([
            'to' => $managerEmail,
            'subject' => $subject,
            'template' => 'emails.purchase-order-warehouse-notification',
            'data' => array_merge($emailData, [
              'recipient_name' => $manager->person->nombre_completo ?? 'Gerente',
            ]),
            'attachments' => [
              ['path' => $pdfPath, 'name' => $pdfFileName, 'mime' => 'application/pdf']
            ],
          ]);
        } catch (\Exception $e) {
          Log::error("Error al enviar correo al gerente (User ID: {$manager->id}): " . $e->getMessage());
        }
      }
    }

    // Limpiar archivo temporal después de enviar todos los correos
    if (file_exists($pdfPath)) {
      unlink($pdfPath);
    }
  }

  /**
   * Notifica a los jefes de almacén cuando el comprobante está recepcionado
   * Delega al servicio ApOrderPurchaseRequestsService para centralizar la lógica
   *
   * @param PurchaseOrder $purchaseOrder
   * @return void
   */
  public function notifyWarehouseManagers(PurchaseOrder $purchaseOrder): void
  {
    $this->purchaseRequestsService->notifyWarehouseManagersInvoiceAccounted($purchaseOrder);
  }

  /**
   * Prepara los datos comunes para los correos electrónicos
   *
   * @param PurchaseOrder $purchaseOrder
   * @return array
   */
  protected function prepareEmailData(PurchaseOrder $purchaseOrder): array
  {
    return [
      'purchase_order_number' => $purchaseOrder->number,
      'invoice_dynamics' => $purchaseOrder->invoice_dynamics,
      'receipt_dynamics' => $purchaseOrder->receipt_dynamics,
      'invoice_date' => $purchaseOrder->invoice_date_dyn
        ? $purchaseOrder->invoice_date_dyn->format('d/m/Y')
        : 'N/A',
      'emission_date' => $purchaseOrder->emission_date
        ? $purchaseOrder->emission_date->format('d/m/Y')
        : 'N/A',

      // Datos de la sede
      'sede_name' => $purchaseOrder->sede?->abreviatura ?? 'N/A',

      // Datos del proveedor
      'supplier_name' => $purchaseOrder->supplier?->full_name ?? 'N/A',
      'supplier_ruc' => $purchaseOrder->supplier?->num_doc ?? 'N/A',

      // Datos del vehículo (si existe)
      'vehicle_plate' => $purchaseOrder->vehicle?->plate ?? 'N/A',
      'vehicle_vin' => $purchaseOrder->vehicle?->vin ?? 'N/A',

      // Totales
      'currency_symbol' => $purchaseOrder->currency?->symbol ?? '',
      'total' => number_format($purchaseOrder->total, 2),

      // Datos de la recepción
      'reception_number' => $purchaseOrder->reception?->reception_number ?? 'N/A',
      'reception_date' => $purchaseOrder->reception?->reception_date
        ? $purchaseOrder->reception->reception_date->format('d/m/Y')
        : 'N/A',
      'shipping_guide_number' => $purchaseOrder->reception?->shipping_guide_number ?? 'N/A',
      'warehouse_name' => $purchaseOrder->reception?->warehouse?->dyn_code ?? 'N/A',

      // Detalle de repuestos recepcionados
      'reception_items' => $purchaseOrder->reception?->details->map(function ($detail) {
        return [
          'product_code' => $detail->product?->code ?? 'N/A',
          'product_name' => $detail->product?->name ?? 'N/A',
          'quantity_received' => $detail->quantity_received,
          'observed_quantity' => $detail->observed_quantity,
          'reception_type' => PurchaseReceptionDetail::getReceptionTypeLabel($detail->reception_type),
        ];
      })->all() ?? [],

      // URL del frontend
      'button_url' => config('app.frontend_url') . '/ap/compras/ordenes-de-compra',
    ];
  }

  /**
   * Prepara los datos para el PDF de la orden de compra recepcionada
   *
   * @param PurchaseOrder $purchaseOrder
   * @return array
   */
  protected function preparePdfData(PurchaseOrder $purchaseOrder): array
  {
    // Obtener solicitudes de compra únicas con sus responsables
    $purchaseRequests = [];
    if ($purchaseOrder->reception?->supplierOrder) {
      $requestDetails = $purchaseOrder->reception->supplierOrder->requestDetails;

      $uniqueRequests = $requestDetails
        ->pluck('orderPurchaseRequest')
        ->unique('id')
        ->filter();

      foreach ($uniqueRequests as $request) {
        $purchaseRequests[] = [
          'request_number' => $request->request_number ?? 'N/A',
          'responsible_name' => $request->requestedBy?->person?->nombre_completo ?? 'N/A',
        ];
      }
    }

    // Preparar items de la recepción con toda la información
    $items = [];
    if ($purchaseOrder->reception?->details) {
      foreach ($purchaseOrder->reception->details as $detail) {
        // Obtener precio unitario del purchase order item
        $unitPrice = $detail->purchaseOrderItem?->unit_price ?? 0;
        $quantity = $detail->quantity_received ?? 0;
        $total = $unitPrice * $quantity;

        // Buscar la solicitud de compra asociada a este producto
        $plate = '';
        $client = '';
        $advisor = '';

        if ($purchaseOrder->reception->supplierOrder) {
          // Buscar en los detalles de la orden de proveedor si hay una solicitud asociada a este producto
          $requestDetail = $purchaseOrder->reception->supplierOrder->requestDetails
            ->where('product_id', $detail->product_id)
            ->first();

          if ($requestDetail && $requestDetail->orderPurchaseRequest) {
            $quotation = $requestDetail->orderPurchaseRequest->apOrderQuotation;

            if ($quotation) {
              $plate = $quotation->vehicle?->plate ?? '';
              $client = $quotation->client?->full_name ?? '';
              $advisor = $quotation->createdBy?->person?->nombre_completo ?? '';
            }
          }
        }

        $items[] = [
          'code' => $detail->product?->code ?? 'N/A',
          'description' => $detail->product?->name ?? 'N/A',
          'brand' => $detail->product?->brand?->name ?? '',
          'model' => $detail->product?->model?->name ?? '',
          'quantity' => number_format($quantity, 2),
          'unit_price' => $unitPrice,
          'total' => $total,
          'plate' => $plate,
          'client' => $client,
          'advisor' => $advisor,
        ];
      }
    }

    return [
      'sede' => $purchaseOrder->sede,
      'purchase_order_number' => $purchaseOrder->number,
      'supplier_name' => $purchaseOrder->supplier?->full_name ?? 'N/A',
      'sede_abbreviation' => $purchaseOrder->sede?->abreviatura ?? 'N/A',
      'responsible_name' => $purchaseOrder->creator?->person?->nombre_completo ?? 'N/A',
      'total_without_tax' => number_format($purchaseOrder->subtotal ?? 0, 2),
      'currency_symbol' => $purchaseOrder->currency?->symbol ?? 'S/',
      'reception_date' => $purchaseOrder->reception?->reception_date
        ? $purchaseOrder->reception->reception_date->format('d/m/Y')
        : 'N/A',
      'purchase_requests' => $purchaseRequests,
      'items' => $items,
    ];
  }
}
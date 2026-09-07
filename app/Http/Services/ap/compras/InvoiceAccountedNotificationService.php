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
 * 1. Toda la plana de Postventa (según reglas de sede y cargo)
 * 2. Jefes de Almacén
 * 3. Usuarios que solicitaron los productos
 */
class InvoiceAccountedNotificationService
{
  protected EmailService $emailService;
  protected PurchaseReceptionService $receptionService;
  protected ApOrderPurchaseRequestsService $purchaseRequestsService;

  public function __construct(
    EmailService                   $emailService,
    PurchaseReceptionService       $receptionService,
    ApOrderPurchaseRequestsService $purchaseRequestsService
  )
  {
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
    // 1. Notificar a toda la plana de postventa
    try {
      $this->notifyPostventaTeam($purchaseOrder);
    } catch (Throwable $e) {
      Log::error("Error al notificar a plana de postventa para OC #{$purchaseOrder->id}: {$e->getMessage()}");
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
   * Notifica a toda la plana de postventa cuando el comprobante está recepcionado,
   * con el detalle de los repuestos recibidos en la recepción
   *
   * Envía correos a:
   * - Todas las sedes: Gerente PV, Coordinador PV, Jefe Almacén, Jefe Repuesto
   * - Según sede: Asesor Servicio, Auxiliar Servicio, Asesor Repuestos, Jefe Taller,
   *   Coordinador Taller, Asistente PV, Asistente Almacén, Codificador
   *
   * @param PurchaseOrder $purchaseOrder
   * @return void
   */
  public function notifyPostventaTeam(PurchaseOrder $purchaseOrder): void
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
      'reception.details.purchaseOrderItem',
    ]);

    // Verificar que tenga sede
    if (!$purchaseOrder->sede_id) {
      Log::warning("#{$purchaseOrder->number}: No se pudo obtener la sede de la orden de compra.");
      return;
    }

    $sedeId = $purchaseOrder->sede_id;

    // IDs de cargos que reciben notificaciones de TODAS LAS SEDES (sin filtro de sede)
    $allSedesPositionIds = array_merge(
      Position::GERENTE_PV_IDS,
      Position::COORDINADOR_PV_IDS,
      Position::JEFE_ALMACEN_PV_IDS,
      Position::JEFE_REPUESTO_PV_IDS
    );

    // IDs de cargos que reciben notificaciones SEGÚN LA SEDE (con filtro de sede)
    $sedeSpecificPositionIds = array_merge(
      Position::ASESOR_SERVICIO_PV_IDS,
      Position::AUXILIAR_SERVICIO_PV_IDS,
      Position::ASESOR_REPUESTOS_PV_IDS,
      Position::JEFE_TALLER_PV_IDS,
      Position::COORDINADOR_TALLER_IDS,
      Position::ASISTENTE_PV_IDS,
      Position::ASISTENTE_ALMACEN_PV_IDS,
      Position::CODIFICADOR_PV_IDS
    );

    // Obtener usuarios con cargos que reciben de TODAS LAS SEDES (sin filtro de sede)
    $allSedesUsers = User::whereHas('person', function ($query) use ($allSedesPositionIds) {
      $query->whereIn('cargo_id', $allSedesPositionIds)
        ->where('status_deleted', 1)
        ->where('status_id', 22);
    })
      ->with('person.position')
      ->get();

    // Obtener usuarios con cargos específicos de la SEDE (con filtro de sede)
    $sedeSpecificUsers = User::whereHas('person', function ($query) use ($sedeSpecificPositionIds) {
      $query->whereIn('cargo_id', $sedeSpecificPositionIds)
        ->where('status_deleted', 1)
        ->where('status_id', 22);
    })
      ->whereHas('sedes', function ($query) use ($sedeId) {
        $query->where('config_sede.id', $sedeId)
          ->where('assigment_user_sede.status', true);
      })
      ->with('person.position')
      ->get();

    // Combinar ambos grupos y eliminar duplicados por ID de usuario
    $allUsers = $allSedesUsers->merge($sedeSpecificUsers)->unique('id');

    if ($allUsers->isEmpty()) {
      Log::warning("#{$purchaseOrder->number}: No se encontraron usuarios de la plana de postventa para notificar.");
      return;
    }

    // Preparar datos para el email y el PDF
    $sedeAbbreviation = $purchaseOrder->sede?->abreviatura ?? 'N/A';
    $subject = 'Informe de llegada de repuestos en Almacén PAKATNAMU ' . $sedeAbbreviation;

    $emailData = [
      'purchase_order_number' => $purchaseOrder->number,
      'sede_name' => $purchaseOrder->sede?->abreviatura ?? 'N/A',
      'sede_abbreviation' => $sedeAbbreviation,
      'supplier_name' => $purchaseOrder->supplier?->full_name ?? 'N/A',
      'responsible_name' => $purchaseOrder->creator?->person?->nombre_completo ?? 'N/A',
    ];

    // Preparar datos para el PDF
    $pdfData = $this->preparePdfData($purchaseOrder);

    // Generar el PDF y guardarlo en archivo temporal
    $pdf = Pdf::loadView('reports.ap.postventa.taller.purchase-reception-detail', $pdfData);
    $pdf->setPaper('a4', 'landscape');

    $pdfPath = storage_path('app/temp/purchase_reception_' . $purchaseOrder->number . '_' . now()->format('YmdHis') . '.pdf');

    // Crear directorio si no existe
    $dir = dirname($pdfPath);
    if (!file_exists($dir)) {
      mkdir($dir, 0755, true);
    }

    file_put_contents($pdfPath, $pdf->output());
    $pdfFileName = 'Recepcion_OC_' . $purchaseOrder->number . '_' . now()->format('Ymd') . '.pdf';

    // Enviar correo a cada usuario de la plana de postventa
    foreach ($allUsers as $user) {
      $userEmail = $user->person?->email2;

      if ($userEmail) {
        try {
          $this->emailService->queue([
            'to' => $userEmail,
            'subject' => $subject,
            'template' => 'emails.purchase-order-warehouse-notification',
            'data' => array_merge($emailData, [
              'recipient_name' => $user->person->nombre_completo ?? 'Usuario',
            ]),
            'attachments' => [
              ['path' => $pdfPath, 'name' => $pdfFileName, 'mime' => 'application/pdf']
            ],
          ]);
        } catch (\Exception $e) {
          Log::error("Error al enviar correo a usuario de plana PV (User ID: {$user->id}): " . $e->getMessage());
        }
      }
    }

    // NO eliminamos el archivo aquí porque el email está en cola
    // El archivo se limpiará automáticamente del directorio temp más tarde
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
  public function preparePdfData(PurchaseOrder $purchaseOrder): array
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
        $model = '';
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
              $model = $quotation->vehicle?->model->version ?? '';
              $client = $quotation->client?->full_name ?? '';
              $advisor = $quotation->createdBy?->person?->nombre_completo ?? '';
            }
          }
        }

        $items[] = [
          'code' => $detail->product?->code ?? 'N/A',
          'description' => $detail->product?->name ?? 'N/A',
          'brand' => $detail->product?->brand?->name ?? '',
          'model' => $model,
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

<?php

namespace App\Http\Services\ap\compras;

use App\Http\Services\ap\postventa\taller\ApOrderPurchaseRequestsService;
use App\Http\Services\common\EmailService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\gp\gestionsistema\Position;
use App\Models\User;
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
      'sede',
      'supplier',
      'vehicle',
      'currency',
      'reception.warehouse',
      'reception.details.product',
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

    // Preparar datos comunes para el correo
    $emailData = $this->prepareEmailData($purchaseOrder);
    $subject = 'Comprobante Recepcionado - OC ' . $purchaseOrder->number;

    // Enviar correo a cada gerente
    foreach ($managers as $manager) {
      $managerEmail = $manager->person?->email2;

      if ($managerEmail) {
        try {
          $this->emailService->queue([
            'to' => $managerEmail,
            'subject' => $subject,
            'template' => 'emails.invoice-accounted-notification',
            'data' => array_merge($emailData, [
              'recipient_name' => $manager->person->nombre_completo ?? 'Gerente',
              'recipient_role' => 'Gerente de Postventa',
            ]),
          ]);
        } catch (\Exception $e) {
          Log::error("Error al enviar correo al gerente (User ID: {$manager->id}): " . $e->getMessage());
        }
      }
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
}
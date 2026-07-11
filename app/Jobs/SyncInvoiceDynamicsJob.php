<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\PurchaseRequestQuoteService;
use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Http\Services\ap\compras\PurchaseOrderService;
use App\Http\Services\ap\compras\PurchaseReceptionService;
use App\Http\Services\common\EmailService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReceptionDetail;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\Position;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * php artisan queue:work --tries=3
 */
class SyncInvoiceDynamicsJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public ?int $purchaseOrderId = null
  )
  {
    $this->onQueue('invoice_sync');
  }

  /**
   * Execute the job.
   * Si se proporciona un ID, procesa solo esa OC
   * Si no, procesa todas las OCs que no tienen invoice_dynamics
   */
  public function handle(): void
  {
    try {
      if ($this->purchaseOrderId) {
        $this->processPurchaseOrder($this->purchaseOrderId);
      } else {
        $this->processAllPurchaseOrders();
      }
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Procesa todas las órdenes de compra sin invoice_dynamics
   * O las que están completed con NC (para detectar cambios de factura)
   */
  protected function processAllPurchaseOrders(): void
  {
    // Obtener OCs que:
    // 1. No tienen invoice_dynamics (flujo normal)
    // 2. Están completed y tienen credit_note_dynamics (para detectar cambio de factura)
    $purchaseOrders = PurchaseOrder::where(function ($query) {
      $query->where(function ($q) {
        // Caso 1: Sin invoice
        $q->whereNull('invoice_dynamics')
          ->orWhere('invoice_dynamics', '');
      })->orWhere(function ($q) {
        // Caso 2: Completed con NC (para detectar cambio de factura)
        $q->where('migration_status', 'completed')
          ->whereNotNull('credit_note_dynamics')
          ->where('credit_note_dynamics', '!=', '');
      });
    })
      ->whereNotNull('number')
      ->get();

    if ($purchaseOrders->isEmpty()) {
      return;
    }


    foreach ($purchaseOrders as $order) {
      try {
        $this->processPurchaseOrder($order->id);
      } catch (\Exception $e) {
        // Continuar con la siguiente orden
        continue;
      }
    }
  }

  /**
   * Procesa una orden de compra específica
   */
  protected function processPurchaseOrder(int $purchaseOrderId): void
  {
    $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

    if (!$purchaseOrder) {
      return;
    }

    if (!$purchaseOrder->number) {
      return;
    }

    if ($purchaseOrder->migrated_at?->lt(now()->subHour())) {
      $purchaseOrder->updateQuietly([
        'invoice_sync_attempted_at' => now(),
        'invoice_sync_attempts' => $purchaseOrder->invoice_sync_attempts + 1,
      ]);
    }

    // Consultar el PA para obtener la factura actual de Dynamics
    try {
      $result = $this->consultStoredProcedure($purchaseOrder->number);

      if (!$result) {
        return;
      }

      $status = trim($result->EstadoDocumento) === 'Hist. Recep.';
      $statusReception = trim($result->EstadoRecepcion) === 'Hist. Recep.';

      if (!$statusReception) {
        return;
      }

      $newInvoice = trim($result->NroDocProvDocumento);
      $newReceipt = trim($result->NumeroDocumento);
      $invoiceDate = $result->FechaDocumento ?? null;

      // CASO 1: OC con factura y migration_status='completed' y tiene NC
      // Verificar si la factura cambió (nueva OC con punto)
      if ($purchaseOrder->migration_status === 'completed' && !empty($purchaseOrder->credit_note_dynamics)) {

        // Actualizar la factura y cambiar el estado a 'updated_with_nc'
        $purchaseOrder->update([
          'invoice_dynamics' => $newInvoice,
          'receipt_dynamics' => $newReceipt,
          'invoice_date_dyn' => $invoiceDate,
          'migration_status' => 'updated_with_nc',
          'status' => (!empty($purchaseOrder->invoice_dynamics) && !($newInvoice == $newReceipt)),
          'invoice_sync_attempts' => 0,
        ]);

        return;
      }

      if (!$status) {
        return;
      }

      if (empty($result->NumeroDocumento) || empty($result->NroDocProvDocumento)) {
        return;
      }

      /**
       * CASO 2: OC sin factura (flujo normal inicial)
       */
      if (empty($purchaseOrder->invoice_dynamics)) {
        $purchaseOrder->update([
          'invoice_dynamics' => $newInvoice,
          'receipt_dynamics' => $newReceipt,
          'invoice_date_dyn' => $invoiceDate,
          'invoice_sync_attempts' => 0,
        ]);

        if ($purchaseOrder->reception) {
          $purchaseReceptionService = new PurchaseReceptionService();
          $purchaseReceptionService->processReceptionStock($purchaseOrder);
        }

        // Solo si la factura no está anulada (invoice != receipt)
        $isNotVoided = $newInvoice !== $newReceipt;

        /**
         * Asignar vehículo a la cotización una vez contabilizada la factura
         */
        if ($isNotVoided && $purchaseOrder->quotation_id && $purchaseOrder->vehicle) {
          try {
            $quoteService = new PurchaseRequestQuoteService();
            $quoteService->assignVehicle([
              'id' => $purchaseOrder->quotation_id,
              'ap_vehicle_id' => $purchaseOrder->vehicle->id,
            ]);
          } catch (Throwable $e) {
            Log::error("Error al asignar vehículo a cotización para OC #{$purchaseOrder->id}: {$e->getMessage()}");
          }
        }

        /**
         * Crear movimiento de vehículo en tránsito
         */
        if ($purchaseOrder->vehicle_movement_id) {
          try {
            $vehicleMovementService = new VehicleMovementService();
            $vehicleMovementService->storeInTransitVehicleMovement($purchaseOrder->id);
          } catch (Throwable $e) {
            Log::error("Error al crear movimiento de vehículo en tránsito para OC #{$purchaseOrder->id}: {$e->getMessage()}");
          }
        }

        /**
         * Vincular anticipos de la cotización al vehículo
         */
        try {
          app(PurchaseOrderService::class)->linkAnticipationsToVehicle($purchaseOrder);
        } catch (Throwable $e) {
          Log::error("Error al vincular anticipos al vehículo para OC #{$purchaseOrder->id}: {$e->getMessage()}");
        }

        /**
         * Notificar a gerencia cuando el comprobante está recepcionado
         */
        if ($isNotVoided) {
          try {
            $this->notifyManagerInvoiceAccounted($purchaseOrder);
          } catch (Throwable $e) {
            Log::error("Error al notificar a gerencia para OC #{$purchaseOrder->id}: {$e->getMessage()}");
          }
        }

        return;
      }

      $vehicle = $purchaseOrder->vehicle;
      $hasInTransitMovement = $vehicle
        ? $vehicle->vehicleMovements()
          ->where('ap_vehicle_status_id', ApVehicleStatus::VEHICULO_EN_TRAVESIA)
          ->exists()
        : false;

      $isNotVoided = $newInvoice !== $newReceipt;

      /**
       * CASO 3: OC con factura pero sin movimiento (recuperación)
       */
      if (!empty($purchaseOrder->invoice_dynamics) && !$hasInTransitMovement && $purchaseOrder->vehicle_movement_id) {
        try {
          $vehicleMovementService = new VehicleMovementService();
          $vehicleMovementService->storeInTransitVehicleMovement($purchaseOrder->id);
        } catch (Throwable $e) {
        }
      }

      /**
       * CASO 3 (recuperación): Asignar vehículo a la cotización si aún no está asignado
       */
      if ($isNotVoided && $purchaseOrder->quotation_id && $vehicle) {
        $quotation = $purchaseOrder->quotation;
        if ($quotation && empty($quotation->ap_vehicle_id)) {
          try {
            $quoteService = new PurchaseRequestQuoteService();
            $quoteService->assignVehicle([
              'id' => $purchaseOrder->quotation_id,
              'ap_vehicle_id' => $vehicle->id,
            ]);
          } catch (Throwable $e) {
            Log::error("Error al asignar vehículo a cotización (recuperación) para OC #{$purchaseOrder->id}: {$e->getMessage()}");
          }
        }
      }

    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Consulta el Procedimiento Almacenado
   */
  protected function consultStoredProcedure(string $orderNumber): ?object
  {
    try {
      // Ejecutar el PA: EXEC nePoReporteSeguimientoOrdenCompra_Factura @pOrdenCompraId = 'OC1400000001'
      $results = DB::connection(Company::CONNECTION_DYNAMICS_3)
        ->select("EXEC nePoReporteSeguimientoOrdenCompra_Factura @pOrdenCompraId = '{$orderNumber}'");

      // El PA debería retornar un resultado con el campo NroDocProvDocumento
      if (!empty($results) && isset($results[0])) {
        return $results[0];
      }

      return null;
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Notifica a gerencia cuando el comprobante está recepcionado, con el detalle
   * de los repuestos recibidos en la recepción (purchase_receptions / purchase_reception_details)
   */
  protected function notifyManagerInvoiceAccounted(PurchaseOrder $purchaseOrder): void
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

    // Preparar datos para el correo
    $emailData = [
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

    $subject = 'Comprobante Recepcionado - OC ' . $purchaseOrder->number;

    // Enviar correo a cada gerente
    $emailService = new EmailService();
    foreach ($managers as $manager) {
      $managerEmail = $manager->person?->email2;

      if ($managerEmail) {
        try {
          $emailService->queue([
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

  public function failed(\Throwable $exception): void
  {
  }
}

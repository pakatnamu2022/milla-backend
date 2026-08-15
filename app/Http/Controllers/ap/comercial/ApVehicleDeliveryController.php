<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\GenerateShippingGuideRequest;
use App\Http\Requests\ap\comercial\IndexApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\RescheduleApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\StoreApVehicleDeliveryRequest;
use App\Http\Requests\ap\comercial\StoreApVehicleDeliveryStockInicialRequest;
use App\Http\Requests\ap\comercial\UpdateApVehicleDeliveryRequest;
use App\Http\Services\ap\comercial\ApVehicleDeliveryService;
use App\Jobs\SyncAccountingEntryJob;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApVehicleDeliveryController extends Controller
{
  protected ApVehicleDeliveryService $service;

  public function __construct(ApVehicleDeliveryService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleDeliveryRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleDeliveryRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function rescheduleHistory($id)
  {
    try {
      return $this->success($this->service->rescheduleHistory((int)$id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApVehicleDeliveryRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function reschedule(RescheduleApVehicleDeliveryRequest $request, $id)
  {
    try {
      return $this->success($this->service->reschedule((int)$id, $request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function approveExtraordinary($id)
  {
    try {
      return $this->success($this->service->approveExtraordinary((int) $id));
    } catch (\Throwable $th) {
      return $this->errorValidation($th->getMessage());
    }
  }

  public function rejectExtraordinary($id)
  {
    try {
      return $this->success($this->service->rejectExtraordinary((int) $id));
    } catch (\Throwable $th) {
      return $this->errorValidation($th->getMessage());
    }
  }

  public function resendExtraordinaryApproval($id)
  {
    try {
      return $this->success($this->service->resendExtraordinaryApproval((int)$id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function availableSlots(Request $request)
  {
    try {
      $request->validate([
        'date'    => 'required|date_format:Y-m-d',
        'shop_id' => 'nullable|integer',
      ]);
      return $this->success($this->service->availableSlots($request->input('date'), $request->input('shop_id')));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Diagnostica por qué no se puede generar la entrega de un VIN.
   */
  public function diagnoseVin(Request $request)
  {
    try {
      $request->validate([
        'vin'     => 'required|string',
        'sede_id' => 'nullable|integer',
      ]);

      $result = $this->service->diagnoseVin(
        $request->input('vin'),
        $request->integer('sede_id') ?: null
      );

      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Lista vehículos de stock inicial en estado VENDIDO NO ENTREGADO sin entrega registrada
   */
  public function vehiclesStockInicial(Request $request)
  {
    try {
      $sedeId = $request->integer('sede_id') ?: null;
      return $this->success($this->service->listStockInicialVehicles($sedeId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Crea una entrega para un vehículo de stock inicial
   */
  public function storeStockInicial(StoreApVehicleDeliveryStockInicialRequest $request)
  {
    try {
      return $this->success($this->service->storeStockInicial($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Genera la guía de remisión para una entrega de vehículo
   */
  public function generateShippingGuide($id, GenerateShippingGuideRequest $request)
  {
    try {
      return $this->success($this->service->generateShippingGuide($id, $request->validated()));
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Envía la guía de remisión a SUNAT mediante Nubefact
   */
  public function sendToNubefact($id)
  {
    try {
      return $this->service->sendToNubefact($id);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Consulta el estado de la guía en Nubefact/SUNAT
   */
  public function queryFromNubefact($id)
  {
    try {
      return $this->service->queryFromNubefact($id);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Envía la guía de remisión de venta a Dynamics GP
   */
  public function sendToDynamic($id)
  {
    try {
      return $this->service->sendToDynamic($id);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  /**
   * Resetea el estado de verificación del asiento contable y despacha el job
   * si el registro aún no existe en la tabla intermedia de GP.
   */
  public function syncAccountingEntry($id)
  {
    try {
      $delivery = ApVehicleDelivery::findOrFail($id);

      if (!$delivery->shipping_guide_id) {
        return response()->json(['success' => false, 'message' => 'La entrega no tiene guía de remisión asociada.'], 422);
      }

      $headerLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $delivery->shipping_guide_id)
        ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
        ->first();

      if (!$headerLog) {
        return response()->json(['success' => false, 'message' => 'No se encontró log de asiento contable para esta guía.'], 404);
      }

      // Resetear contadores para que VerifyAccountingEntryJob vuelva a intentarlo
      $headerLog->update([
        'attempts'        => 0,
        'proceso_estado'  => 0,
        'last_attempt_at' => null,
      ]);

      // Si ya existe en la intermedia no re-insertamos — el verify job lo procesará
      $existsInIntermediate = DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Referencia', $headerLog->external_id)
        ->exists();

      if ($existsInIntermediate) {
        return response()->json([
          'success'    => true,
          'message'    => 'Estado reseteado. El asiento ya existe en GP, será verificado en el próximo ciclo.',
          'dispatched' => false,
        ]);
      }

      // No existe: despachar job para insertarlo en la intermedia
      SyncAccountingEntryJob::dispatch($delivery->shipping_guide_id);

      return response()->json([
        'success'    => true,
        'message'    => 'Estado reseteado y job de sincronización despachado.',
        'dispatched' => true,
      ]);
    } catch (\Throwable $th) {
      return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
    }
  }

  /**
   * Exporta CSV con VINs entregados y estado de asiento contable
   */
  public function accountingEntryReport(): StreamedResponse
  {
    $guides = ShippingGuides::with([
      'vehicleMovement.vehicle',
      'migrationLogs' => fn($q) => $q->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER),
    ])
      ->where('transfer_reason_id', SunatConcepts::TRANSFER_REASON_VENTA)
      ->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('status_dynamic', 1)
      ->whereHas('sedeTransmitter', fn($q) => $q->where('empresa_id', Company::COMPANY_AP_ID))
      ->whereExists(fn($q) => $q->from('ap_vehicle_delivery')->whereColumn('ap_vehicle_delivery.shipping_guide_id', 'shipping_guides.id')->whereNull('ap_vehicle_delivery.deleted_at'))
      ->orderBy('issue_date')
      ->get();

    $filename = 'asientos_contables_' . now()->format('Y-m-d_H-i-s') . '.csv';

    return response()->streamDownload(function () use ($guides) {
      $handle = fopen('php://output', 'w');

      fputcsv($handle, ['ID Guía', 'Número Guía', 'Fecha', 'VIN', 'Estado Asiento', 'Estado GP', 'Referencia']);

      foreach ($guides as $guide) {
        $vin = $guide->vehicleMovement?->vehicle?->vin ?? '';
        $headerLog = $guide->migrationLogs->first();

        $accountingStatus = 'NO ENVIADO';
        $procesoEstado = '';

        if ($headerLog) {
          $accountingStatus = strtoupper($headerLog->status);
          $procesoEstado = $headerLog->proceso_estado === 1 ? 'Procesado GP' : 'Pendiente GP';
        }

        fputcsv($handle, [
          $guide->id,
          $guide->document_number,
          $guide->issue_date?->format('Y-m-d') ?? '',
          $vin,
          $accountingStatus,
          $procesoEstado,
          $headerLog?->external_id ?? '',
        ]);
      }

      fclose($handle);
    }, $filename, [
      'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
  }
}

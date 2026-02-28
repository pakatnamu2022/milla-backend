<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexShippingGuidesRequest;
use App\Http\Requests\ap\comercial\StoreShippingGuidesRequest;
use App\Http\Requests\ap\comercial\UpdateShippingGuidesRequest;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderMigrationLogResource;
use App\Http\Services\ap\comercial\ShippingGuidesService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Illuminate\Http\Request;
use function Pest\Laravel\json;

class ShippingGuidesController extends Controller
{
  protected ShippingGuidesService $service;

  public function __construct(ShippingGuidesService $service)
  {
    $this->service = $service;
  }

  public function index(IndexShippingGuidesRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeConsignment(StoreShippingGuidesRequest $request)
  {
    try {
      $data = $request->validated();
      if ($request->hasFile('file')) {
        $data['file'] = $request->file('file');
      }
      return $this->success($this->service->storeConsignment($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreShippingGuidesRequest $request)
  {
    try {
      $data = $request->validated();
      // Agregar el archivo si existe
      if ($request->hasFile('file')) {
        $data['file'] = $request->file('file');
      }

      return $this->success($this->service->store($data));
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

  public function update(UpdateShippingGuidesRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      // Agregar el archivo si existe
      if ($request->hasFile('file')) {
        $data['file'] = $request->file('file');
      }

      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function cancel(Request $request, $id)
  {
    try {
      $request->validate([
        'cancellation_reason' => 'required|string',
      ]);

      return $this->success($this->service->cancel($id, $request->cancellation_reason));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

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

  public function markAsReceived(Request $request, $id)
  {
    try {
      $request->validate([
        'note_received' => 'required|string',
      ]);

      return $this->service->markAsReceived($id, $request->note_received);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  public function logs(int $id)
  {
    try {
      $shippingGuide = ShippingGuides::find($id);

      if (!$shippingGuide) {
        return response()->json([
          'success' => false,
          'message' => 'Guía de remisión no encontrada',
        ], 404);
      }

      $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $id)
        ->orderBy('id')
        ->get();

      return response()->json([
        'success' => true,
        'data' => [
          'shipping_guide' => [
            'id' => $shippingGuide->id,
            'document_number' => $shippingGuide->document_number,
            'correlative' => $shippingGuide->correlative,
            'migration_status' => $shippingGuide->migration_status,
            'migrated_at' => $shippingGuide->migrated_at?->format('Y-m-d H:i:s'),
            'created_at' => $shippingGuide->created_at->format('Y-m-d H:i:s'),
          ],
          'logs' => VehiclePurchaseOrderMigrationLogResource::collection($logs),
        ],
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  public function history(int $id)
  {
    try {
      $shippingGuide = ShippingGuides::find($id);

      if (!$shippingGuide) {
        return response()->json([
          'success' => false,
          'message' => 'Guía de remisión no encontrada',
        ], 404);
      }

      $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $id)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

      // Crear timeline de eventos
      $timeline = $logs->map(function ($log) {
        $events = [];

        // Evento de creación
        $events[] = [
          'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
          'event' => 'created',
          'description' => "Paso '{$log->step}' creado",
          'status' => 'pending',
        ];

        // Eventos de intentos
        if ($log->last_attempt_at) {
          $events[] = [
            'timestamp' => $log->last_attempt_at->format('Y-m-d H:i:s'),
            'event' => 'attempt',
            'description' => "Intento #{$log->attempts} de sincronización",
            'status' => $log->status,
            'error' => $log->error_message,
          ];
        }

        // Evento de completado
        if ($log->completed_at) {
          $events[] = [
            'timestamp' => $log->completed_at->format('Y-m-d H:i:s'),
            'event' => 'completed',
            'description' => "Paso completado exitosamente",
            'status' => 'completed',
            'proceso_estado' => $log->proceso_estado,
          ];
        }

        return [
          'step' => $log->step,
          'step_name' => (new \App\Http\Resources\ap\comercial\VehiclePurchaseOrderMigrationLogResource($log))->step_name,
          'events' => $events,
        ];
      });

      return response()->json([
        'success' => true,
        'data' => [
          'shipping_guide' => [
            'id' => $shippingGuide->id,
            'document_number' => $shippingGuide->document_number,
            'correlative' => $shippingGuide->correlative,
            'migration_status' => $shippingGuide->migration_status,
            'migrated_at' => $shippingGuide->migrated_at?->format('Y-m-d H:i:s'),
          ],
          'timeline' => $timeline,
        ],
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  public function dispatchMigration(int $id)
  {
    try {
      return $this->success($this->service->dispatchMigration($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function dispatchAll()
  {
    try {
      return $this->success($this->service->dispatchAll());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function checkResources($id)
  {
    try {
      return response()->json([
        'success' => true,
        'data' => $this->service->checkResources($id)
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }

  public function syncWithDynamics($id)
  {
    try {
      return response()->json([
        'success' => true,
        'data' => $this->service->syncWithDynamics($id)
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 400);
    }
  }
  
  public function nextDocumentNumber(Request $request)
  {
    try {
      $request->validate([
        'document_series_id' => 'required|integer|exists:assign_sales_series,id',
      ]);

      return $this->success($this->service->nextDocumentNumber($request->document_series_id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}

<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderMigrationLogResource;
use App\Jobs\VerifyAndMigratePurchaseOrderJob;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehiclePurchaseOrderMigrationController extends Controller
{
  /**
   * Get migration status summary
   */
  public function summary(): JsonResponse
  {
    $summary = [
      'total' => PurchaseOrder::count(),
      'pending' => PurchaseOrder::where('migration_status', 'pending')->count(),
      'in_progress' => PurchaseOrder::where('migration_status', 'in_progress')->count(),
      'completed' => PurchaseOrder::where('migration_status', 'completed')->count(),
      'failed' => PurchaseOrder::where('migration_status', 'failed')->count(),
    ];

    return response()->json([
      'success' => true,
      'data' => $summary,
    ]);
  }

  /**
   * Get migration logs for a specific purchase order
   */
  public function logs(int $purchaseOrderId): JsonResponse
  {
    $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

    if (!$purchaseOrder) {
      return response()->json([
        'success' => false,
        'message' => 'Orden de compra no encontrada',
      ], 404);
    }

    $logs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrderId)
      ->orderBy('id')
      ->get();

    return response()->json([
      'success' => true,
      'data' => [
        'purchase_order' => [
          'id' => $purchaseOrder->id,
          'number' => $purchaseOrder->number,
          'number_guide' => $purchaseOrder->number_guide,
          'migration_status' => $purchaseOrder->migration_status,
          'migrated_at' => $purchaseOrder->migrated_at?->format('Y-m-d H:i:s'),
          'created_at' => $purchaseOrder->created_at->format('Y-m-d H:i:s'),
        ],
        'logs' => VehiclePurchaseOrderMigrationLogResource::collection($logs),
      ],
    ]);
  }

  /**
   * Get all purchase orders with their migration status
   */
  public function index(Request $request): JsonResponse
  {
    $perPage = $request->get('per_page', 15);
    $status = $request->get('status'); // pending, in_progress, completed, failed

    $query = PurchaseOrder::query()
      ->select([
        'id',
        'number',
        'number_guide',
        'vin',
        'migration_status',
        'migrated_at',
        'created_at',
        'updated_at',
      ])
      ->orderBy('created_at', 'desc');

    if ($status) {
      $query->where('migration_status', $status);
    }

    $purchaseOrders = $query->paginate($perPage);

    // Agregar conteo de pasos por estado para cada OC
    $purchaseOrders->getCollection()->transform(function ($po) {
      $logs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $po->id)
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

      $po->steps_summary = [
        'pending' => $logs['pending'] ?? 0,
        'in_progress' => $logs['in_progress'] ?? 0,
        'completed' => $logs['completed'] ?? 0,
        'failed' => $logs['failed'] ?? 0,
        'total' => 8, // Total de pasos
      ];

      return $po;
    });

    return response()->json([
      'success' => true,
      'data' => $purchaseOrders,
    ]);
  }

  /**
   * Get detailed migration history for a purchase order
   */
  public function history(int $purchaseOrderId): JsonResponse
  {
    $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

    if (!$purchaseOrder) {
      return response()->json([
        'success' => false,
        'message' => 'Orden de compra no encontrada',
      ], 404);
    }

    $logs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrderId)
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
        'step_name' => (new VehiclePurchaseOrderMigrationLogResource($log))->step_name,
        'events' => $events,
      ];
    });

    return response()->json([
      'success' => true,
      'data' => [
        'purchase_order' => [
          'id' => $purchaseOrder->id,
          'number' => $purchaseOrder->number,
          'number_guide' => $purchaseOrder->number_guide,
          'migration_status' => $purchaseOrder->migration_status,
          'migrated_at' => $purchaseOrder->migrated_at?->format('Y-m-d H:i:s'),
        ],
        'timeline' => $timeline,
      ],
    ]);
  }

  /**
   * Despacha el job de migración para una orden de compra específica
   */
  public function dispatch(int $id): JsonResponse
  {
    $purchaseOrder = PurchaseOrder::find($id);

    if (!$purchaseOrder) {
      return response()->json([
        'success' => false,
        'message' => 'Orden de compra no encontrada',
      ], 404);
    }

    if ($purchaseOrder->migration_status === 'completed') {
      return response()->json([
        'success' => false,
        'message' => 'La orden ya está migrada completamente',
      ], 422);
    }

    VerifyAndMigratePurchaseOrderJob::dispatch($purchaseOrder->id);

    return response()->json([
      'success' => true,
      'message' => "Job de migración despachado para la orden {$purchaseOrder->number}",
    ]);
  }

  /**
   * Get statistics about migration process
   */
  public function statistics(): JsonResponse
  {
    // Estadísticas generales
    $totalOrders = PurchaseOrder::count();
    $completedOrders = PurchaseOrder::where('migration_status', 'completed')->count();
    $failedOrders = PurchaseOrder::where('migration_status', 'failed')->count();

    // Promedio de intentos por paso
    $avgAttempts = VehiclePurchaseOrderMigrationLog::avg('attempts');

    // Pasos que más fallan
    $failedSteps = VehiclePurchaseOrderMigrationLog::where('status', 'failed')
      ->selectRaw('step, COUNT(*) as count')
      ->groupBy('step')
      ->orderByDesc('count')
      ->get()
      ->map(function ($item) {
        return [
          'step' => $item->step,
          'step_name' => match ($item->step) {
            'supplier' => 'Proveedor',
            'supplier_address' => 'Dirección del Proveedor',
            'article' => 'Artículo',
            'purchase_order' => 'Orden de Compra',
            'purchase_order_detail' => 'Detalle de Orden de Compra',
            'reception' => 'Recepción',
            'reception_detail' => 'Detalle de Recepción',
            'reception_detail_serial' => 'Serial de Recepción',
            default => $item->step,
          },
          'failures' => $item->count,
        ];
      });

    // Tiempo promedio de migración (desde creación hasta completado)
    $avgMigrationTime = PurchaseOrder::whereNotNull('migrated_at')
      ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, migrated_at)) as avg_seconds')
      ->value('avg_seconds');

    return response()->json([
      'success' => true,
      'data' => [
        'summary' => [
          'total_orders' => $totalOrders,
          'completed_orders' => $completedOrders,
          'failed_orders' => $failedOrders,
          'success_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
        ],
        'performance' => [
          'avg_attempts_per_step' => round($avgAttempts, 2),
          'avg_migration_time_seconds' => round($avgMigrationTime ?? 0, 2),
          'avg_migration_time_minutes' => round(($avgMigrationTime ?? 0) / 60, 2),
        ],
        'failed_steps' => $failedSteps,
      ],
    ]);
  }
}

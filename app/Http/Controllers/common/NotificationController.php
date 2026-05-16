<?php

namespace App\Http\Controllers\common;

use App\Http\Controllers\Controller;
use App\Http\Requests\common\IndexNotificationRequest;
use App\Http\Services\common\NotificationService;
use App\Http\Services\common\LowStockNotificationService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class NotificationController extends Controller
{
  use HasApiResponse;

  protected NotificationService $service;
  protected LowStockNotificationService $lowStockService;

  public function __construct(
    NotificationService $service,
    LowStockNotificationService $lowStockService
  ) {
    $this->service = $service;
    $this->lowStockService = $lowStockService;
  }

  public function index(IndexNotificationRequest $request): JsonResponse
  {
    try {
      return response()->json(
        $this->service->listForUser($request, auth()->id())
      );
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function unreadCount(): JsonResponse
  {
    try {
      return $this->success(['count' => $this->service->unreadCount(auth()->id())]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function markAsRead(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->markAsRead($id, auth()->id()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function markAllAsRead(): JsonResponse
  {
    try {
      return $this->success($this->service->markAllAsRead(auth()->id()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Notificar manualmente a encargados de almacén sobre stock bajo
   */
  public function notifyLowStock(): JsonResponse
  {
    try {
      $result = $this->lowStockService->notifyLowStock();

      if ($result['success']) {
        return $this->success($result);
      }

      return $this->error($result['message'] ?? 'Error al enviar notificaciones');
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener estadísticas de productos con stock bajo
   */
  public function getLowStockStats(): JsonResponse
  {
    try {
      $stats = $this->lowStockService->getLowStockStats();
      return $this->success($stats);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}

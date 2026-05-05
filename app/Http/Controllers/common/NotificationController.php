<?php

namespace App\Http\Controllers\common;

use App\Http\Controllers\Controller;
use App\Http\Requests\common\IndexNotificationRequest;
use App\Http\Services\common\NotificationService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class NotificationController extends Controller
{
  use HasApiResponse;

  protected NotificationService $service;

  public function __construct(NotificationService $service)
  {
    $this->service = $service;
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
}

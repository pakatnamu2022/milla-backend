<?php

namespace App\Http\Services\common;

use App\Http\Resources\common\NotificationResource;
use App\Http\Services\BaseService;
use App\Models\SysNotification;
use Illuminate\Http\Request;

class NotificationService extends BaseService
{
  public function listForUser(Request $request, int $userId)
  {
    $query = SysNotification::whereHas('users', fn($q) => $q->where('usr_users.id', $userId))
      ->with(['users' => fn($q) => $q->where('usr_users.id', $userId)])
      ->where(fn($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
      ->orderByDesc('created_at');

    if ($request->filled('type')) {
      $query->where('type', $request->input('type'));
    }

    if ($request->has('is_read')) {
      $isRead = filter_var($request->input('is_read'), FILTER_VALIDATE_BOOLEAN);
      $query->whereHas('users', function ($q) use ($userId, $isRead) {
        $q->where('usr_users.id', $userId);
        if ($isRead) {
          $q->whereNotNull('sys_notification_user.read_at');
        } else {
          $q->whereNull('sys_notification_user.read_at');
        }
      });
    }

    $perPage = $request->input('per_page', 15);
    $paginated = $query->paginate($perPage);

    return NotificationResource::collection($paginated)->response()->getData(true);
  }

  public function markAsRead(int $notificationId, int $userId): array
  {
    $notification = SysNotification::findOrFail($notificationId);

    $notification->users()->updateExistingPivot($userId, [
      'read_at' => now(),
    ]);

    return ['message' => 'Notificación marcada como leída'];
  }

  public function markAllAsRead(int $userId): array
  {
    SysNotification::whereHas('users', fn($q) => $q->where('usr_users.id', $userId))
      ->with(['users' => fn($q) => $q->where('usr_users.id', $userId)
        ->whereNull('sys_notification_user.read_at')])
      ->get()
      ->each(function (SysNotification $notif) use ($userId) {
        $notif->users()->updateExistingPivot($userId, ['read_at' => now()]);
      });

    return ['message' => 'Todas las notificaciones marcadas como leídas'];
  }

  public function unreadCount(int $userId): int
  {
    return SysNotification::whereHas('users', function ($q) use ($userId) {
      $q->where('usr_users.id', $userId)
        ->whereNull('sys_notification_user.read_at');
    })
      ->where(fn($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
      ->count();
  }

  public function destroy(int $id): array
  {
    $notification = SysNotification::findOrFail($id);
    $notification->delete();
    return ['message' => 'Notificación eliminada'];
  }
}


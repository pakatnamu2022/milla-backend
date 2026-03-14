<?php

namespace App\Http\Traits;

use App\Models\SysNotification;
use Illuminate\Database\Eloquent\Model;

trait SendsNotifications
{
  /**
   * Create a notification and attach it to one or more users.
   *
   * @param  string        $title
   * @param  string        $body
   * @param  string        $type    Dot-slug, e.g. 'vehicle.inventory.evaluated'
   * @param  int|int[]     $userIds Single user ID or array of user IDs
   * @param  Model|null    $source  Polymorphic source model (optional)
   * @param  array|null    $data    Extra JSON payload (optional)
   */
  public function notify(
    string $title,
    string $body,
    string $type,
    int|array $userIds,
    ?Model $source = null,
    ?array $data = null,
    mixed $scheduledAt = null
  ): SysNotification {
    $notification = SysNotification::create([
      'title'           => $title,
      'body'            => $body,
      'type'            => $type,
      'data'            => $data,
      'notifiable_type' => $source ? get_class($source) : null,
      'notifiable_id'   => $source?->getKey(),
      'created_by'      => auth()->id(),
      'scheduled_at'    => $scheduledAt,
    ]);

    $notification->users()->attach((array) $userIds);

    return $notification;
  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SysNotification extends BaseModel
{
  use SoftDeletes;

  protected $table = 'sys_notifications';

  protected $fillable = [
    'title',
    'body',
    'type',
    'data',
    'notifiable_type',
    'notifiable_id',
    'created_by',
    'scheduled_at',
  ];

  protected $casts = [
    'data' => 'array',
  ];

  public function notifiable(): MorphTo
  {
    return $this->morphTo();
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'sys_notification_user')
      ->withPivot('read_at')
      ->withTimestamps();
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}

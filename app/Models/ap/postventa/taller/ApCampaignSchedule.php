<?php

namespace App\Models\ap\postventa\taller;

use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApCampaignSchedule extends Model
{
  protected $table = 'ap_campaign_schedules';

  protected $fillable = [
    'sede_id',
    'worker_id',
    'date',
    'created_by',
  ];

  protected $casts = [
    'date' => 'date',
  ];

  const filters = [
    'sede_id' => '=',
    'worker_id' => '=',
    'date' => 'date_between',
    'created_by' => '=',
  ];

  const sorts = [
    'id',
    'date',
    'created_at',
  ];

  // Relations
  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
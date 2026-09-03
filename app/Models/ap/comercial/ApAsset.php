<?php

namespace App\Models\ap\comercial;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAsset extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_assets';

  protected $fillable = [
    'ap_vehicle_id',
    'worker_id',
    'assigned_date',
    'observation',
    'dyn_series',
    'migration_status',
    'created_by',
  ];

  protected $casts = [
    'assigned_date' => 'date',
  ];

  const MIGRATION_STATUS_PENDING = 'pending';
  const MIGRATION_STATUS_IN_PROGRESS = 'in_progress';
  const MIGRATION_STATUS_COMPLETED = 'completed';
  const MIGRATION_STATUS_FAILED = 'failed';
  const MIGRATION_STATUS_SKIPPED = 'skipped';

  const array filters = [
    'search'           => ['vehicle.vin', 'vehicle.plate', 'worker.nombre_completo'],
    'worker_id'        => '=',
    'ap_vehicle_id'    => '=',
    'migration_status' => 'in_or_equal',
  ];

  const array sorts = [
    'id',
    'assigned_date',
    'created_at',
  ];

  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicle_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id')->withoutGlobalScope('working');
  }

  public function createdByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}

<?php

namespace App\Models\gp\gestionhumana;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountantDistrictAssignment extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_accountant_district_assignments';

  protected $fillable = [
    'worker_id',
    'district_id',
  ];

  const filters = [
    'worker_id' => '=',
    'district_id' => 'in',
    'search' => ['worker.nombre_completo', 'district.name'],
  ];

  const sorts = [
    'id',
    'created_at',
    'updated_at',
  ];

  /**
   * Relationship with Worker (Accountant)
   */
  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  /**
   * Relationship with District
   */
  public function district(): BelongsTo
  {
    return $this->belongsTo(District::class);
  }

  /**
   * Scope to filter by worker
   */
  public function scopeByWorker($query, int $workerId)
  {
    return $query->where('worker_id', $workerId);
  }

  /**
   * Scope to filter by district
   */
  public function scopeByDistrict($query, int $districtId)
  {
    return $query->where('district_id', $districtId);
  }

  /**
   * Scope to filter by multiple districts
   */
  public function scopeByDistricts($query, array $districtIds)
  {
    return $query->whereIn('district_id', $districtIds);
  }
}

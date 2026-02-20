<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneLineWorker extends BaseModel
{
  use SoftDeletes;

  protected $table = 'phone_line_worker';

  protected $fillable = [
    'phone_line_id',
    'worker_id',
    'assigned_at',
    'active',
    'unassigned_at',
    'pdf_path',
    'pdf_unassign_path',
  ];

  protected $casts = [
    'assigned_at' => 'datetime',
    'unassigned_at' => 'datetime',
    'active' => 'boolean',
  ];

  const filters = [
    'id' => '=',
    'phone_line_id' => '=',
    'worker_id' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'assigned_at' => 'desc',
  ];

  /**
   * Relación con la línea telefónica
   */
  public function phoneLine()
  {
    return $this->belongsTo(PhoneLine::class, 'phone_line_id');
  }

  /**
   * Relación con el trabajador
   */
  public function worker()
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }
}

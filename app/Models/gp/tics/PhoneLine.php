<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneLine extends BaseModel
{
  use SoftDeletes;

  protected $table = 'phone_line';

  protected $fillable = [
    'telephone_account_id',
    'telephone_plan_id',
    'line_number',
    'status',
    'is_active',
  ];

  const filters = [
    'id' => '=',
    'search' => ['line_number'],
    'telephone_account_id' => '=',
    'telephone_plan_id' => '=',
    'line_number' => 'like',
    'status' => '=',
    'is_active' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'line_number' => 'asc',
    'status' => 'asc',
  ];

  /**
   * Relación con la cuenta telefónica
   */
  public function telephoneAccount()
  {
    return $this->belongsTo(TelephoneAccount::class, 'telephone_account_id');
  }

  /**
   * Relación con el plan telefónico
   */
  public function telephonePlan()
  {
    return $this->belongsTo(TelephonePlan::class, 'telephone_plan_id');
  }

  /**
   * Relación muchos a muchos con trabajadores
   */
  public function workers()
  {
    return $this->belongsToMany(Worker::class, 'phone_line_worker', 'phone_line_id', 'worker_id')
      ->withPivot('assigned_at')
      ->withTimestamps();
  }
}

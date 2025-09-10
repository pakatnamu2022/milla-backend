<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignmentLeadership extends Model
{
  use SoftDeletes;

  protected $table = 'ap_assignment_leadership';

  protected $fillable = [
    'boss_id',
    'worker_id'
  ];

  public function boss()
  {
    return $this->belongsTo(Person::class, 'boss_id');
  }

  public function worker()
  {
    return $this->belongsTo(Person::class, 'worker_id');
  }
}

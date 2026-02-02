<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelephonePlan extends BaseModel
{
  use SoftDeletes;

  protected $table = 'telephone_plan';

  protected $fillable = [
    'name',
    'price',
    'description',
  ];

  const filters = [
    'id' => '=',
    'search' => ['name', 'description'],
    'name' => 'like',
  ];

  const sorts = [
    'id' => 'asc',
    'name' => 'asc',
    'price' => 'asc',
  ];

  /**
   * Relación con las líneas telefónicas que usan este plan
   */
  public function phoneLines()
  {
    return $this->hasMany(PhoneLine::class, 'telephone_plan_id');
  }
}

<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Warehouse extends Model
{
  use SoftDeletes;

  protected $table = 'warehouse';

  protected $fillable = [
    'dyn_code',
    'description',
    'sede_id',
    'type_operation_id',
    'status',
  ];

  const filters = [
    'search' => ['dyn_code', 'description'],
    'sede_id' => '=',
    'type_operation_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'dyn_code',
    'description',
    'sede_id',
    'type_operation_id',
    'status',
  ];

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function typeOperation()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_operation_id');
  }
}

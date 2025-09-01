<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\Sede;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignSede extends Model
{
  use SoftDeletes;

  protected $table = "ap_assign_sede";

  protected $fillable = [
    'sede_id',
    'asesor_id',
  ];

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function asesor()
  {
    return $this->belongsTo(Person::class, 'asesor_id');
  }
}

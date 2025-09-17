<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;

class Area extends BaseModel
{
  protected $table = 'rrhh_area';

  public $timestamps = true;

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

//    public function centro_costo()
//    {
//        return $this->belongsTo(Ceco::class, 'centro_costo_id');
//    }

}

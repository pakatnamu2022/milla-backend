<?php

namespace App\Models\gp\gestionhumana\ausentismo;

use Illuminate\Database\Eloquent\Model;

class TipoDescanso extends Model
{
  protected $table = 'rrhh_tipo_descanso';

  protected $fillable = ['descripcion'];
}

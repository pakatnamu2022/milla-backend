<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Person extends BaseModel
{
  protected $table = "rrhh_persona";

  protected static function booted()
  {
    static::addGlobalScope('not_deleted', function (Builder $builder) {
      $builder->where('status_deleted', 1);
    });
  }

  public function sede()
  {
    return $this->hasOne(Sede::class, 'id', 'sede_id');
  }

  public function position()
  {
    return $this->hasOne(Position::class, 'id', 'cargo_id');
  }

  public function user()
  {
    return $this->hasOne(User::class, 'partner_id', 'id');
  }
}

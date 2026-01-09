<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Http\Utils\Constants;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionsistema\Status;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Worker extends BaseModel
{
  protected $table = "rrhh_persona";

  protected $fillable = [
    'id',
    'vat',
    'nombre_completo',
    'sede_id',
    'jefe_id',
    'email',
    'email2',
    'email3',
  ];

  const filters = [
    'search' => ['nombre_completo', 'vat'],
    'vat' => 'like',
    'sede.empresa_id' => '=',
    'nombre_completo' => 'like',
    'cargo_id' => 'in',
    'status_id' => '=',
    'sede_id' => '=',
    'sede.departamento' => '=',
  ];

  const sorts = [
    'nombre_completo',
  ];

  protected static function booted()
  {
    static::addGlobalScope('working', function (Builder $builder) {
      $builder->where('status_deleted', 1)
        ->where('b_empleado', 1);
    });
  }

  /**
   * Indica si el trabajador tiene el rol de TICS
   * Attribute: has_rol_tics
   * @return bool
   */
  public function getHasRolTicsAttribute(): bool
  {
    return $this->user && $this->user->role && $this->user->role->id === Constants::TICS_ROL_ID;
  }

  public function scopeWorking($query)
  {
    return $query
      ->where('status_deleted', 1)
      ->where('b_empleado', 1)
      ->where('status_id', 22);
  }

  public function offerLetterStatus(): HasOne
  {
    return $this->hasOne(Status::class, 'id', 'status_carta_oferta_id');
  }

  public function emailOfferLetterStatus(): HasOne
  {
    return $this->hasOne(Status::class, 'id', 'status_envio_mail_carta_oferta');
  }

  public function scopeFromEmpresa($query, int $empresaId)
  {
    return $query->whereHas('sede', fn($q) => $q->where('empresa_id', $empresaId));
  }

  public function objectives()
  {
    return $this->hasMany(EvaluationCategoryObjectiveDetail::class, 'person_id')
      ->where('active', true);
  }

  public function competences()
  {
    return $this->hasMany(EvaluationCategoryCompetenceDetail::class, 'person_id')
      ->where('active', true);
  }

  public function sede()
  {
    return $this->hasOne(Sede::class, 'id', 'sede_id');
  }

  public function area()
  {
    return $this->hasOne(Area::class, 'id', 'area_id');
  }

  public function evaluationDetails()
  {
    return $this->hasMany(EvaluationPersonDetail::class, 'person_id');
  }

  public function boss()
  {
    return $this->hasOne(Worker::class, 'id', 'jefe_id');
  }

  public function evaluator()
  {
    return $this->hasOne(Worker::class, 'id', 'supervisor_id');
  }

  public function subordinates()
  {
    return $this->hasMany(Worker::class, 'jefe_id', 'id');
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

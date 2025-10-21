<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Status;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Worker extends Person
{
  const filters = [
    'search' => ['nombre_completo'],
    'sede.empresa_id' => '=',
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
}

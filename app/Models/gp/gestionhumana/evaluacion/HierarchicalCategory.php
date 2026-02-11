<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Utils\Constants;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class HierarchicalCategory extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_hierarchical_category';

  protected $fillable = [
    'name',
    'description',
    'hasObjectives',
    'excluded_from_evaluation',
  ];

  const filters = [
    'search' => ['name', 'description'],
    'excluded_from_evaluation' => '=',
    'pass' => 'virtual_bool',
    'hasObjectives' => '=',
  ];

  const sorts = [
    'name',
    'description',
    'pass',
    'excluded_from_evaluation'
  ];

  protected $casts = [
    'pass' => 'boolean',
    'issues' => 'array',
  ];

  public function children()
  {
    return $this->hasMany(HierarchicalCategoryDetail::class, 'hierarchical_category_id');
  }

  public function objectives()
  {
    return $this->belongsToMany(
      EvaluationObjective::class,
      'gh_evaluation_category_objective',
      'category_id',
      'objective_id'
    )
      ->wherePivotNull('deleted_at')
      ->where('gh_evaluation_objective.active', 1)
      ->distinct(); // 👈 solo los que no están eliminados
  }


  public function competences()
  {
    return $this->belongsToMany(
      EvaluationCompetence::class,
      'gh_evaluation_category_competence',
      'category_id',
      'competence_id'
    )->wherePivotNull('deleted_at')->distinct();
  }

  public function cycles()
  {
    return $this->belongsToMany(
      EvaluationCycle::class,
      'gh_evaluation_cycle_category_detail',
      'hierarchical_category_id',
      'cycle_id'
    );
  }

  public function positions()
  {
    return $this->hasManyThrough(
      Position::class,
      HierarchicalCategoryDetail::class,
      'hierarchical_category_id',
      'id',
      'id',
      'position_id'
    );
  }

  public function workers()
  {
    return $this->hasManyThrough(
      Worker::class,
      HierarchicalCategoryDetail::class,
      'hierarchical_category_id',
      'cargo_id',
      'id',
      'position_id'
    )->where('rrhh_persona.status_id', 22)->where('rrhh_persona.status_deleted', 1)->where('rrhh_persona.b_empleado', 1);
  }

  public static function whereAllPersonsHaveJefe(bool $hasObjectives, $cutOffDate = null)
  {
    $categories = self::where('excluded_from_evaluation', false)
      ->when($hasObjectives === true, function ($q) {
        $q->where('hasObjectives', true);
      })
      ->with([
        'positions' => function ($q) use ($cutOffDate) {
          $q->with(['persons' => function ($q2) use ($cutOffDate) {
            $q2->where('status_deleted', 1)
              ->where('b_empleado', 1)
              ->where('status_id', Constants::WORKER_ACTIVE)
              ->whereDoesntHave('evaluationDetails')
              ->when($cutOffDate, function ($q3) use ($cutOffDate) {
                $q3->where('fecha_inicio', '<=', $cutOffDate);
              })
              ->with([
                'evaluator' => function ($qb) {
                  $qb->with('position');
                },
                'position',
              ]);
          }]);
        },
        'competences'
      ])
      ->get();

    return $categories->map(function ($category) use ($hasObjectives) {
      $persons = $category->positions->flatMap->persons;
      $total = $persons->count();
      $conJefe = $persons->whereNotNull('supervisor_id')->count();
      $sinJefe = $persons->whereNull('supervisor_id')->count();

      $issues = [];

      // Validar competencias si hasObjectives es false
      if (!$hasObjectives) {
        $competencesCount = $category->competences->count();
        if ($competencesCount === 0) {
          $issues[] = "La categoría debe tener al menos una competencia asignada cuando no tiene objetivos.";
        }
      }

      foreach ($persons as $person) {
        if (is_null($person->supervisor_id)) {
          $issues[] = "La persona {$person->nombre_completo} no tiene evaluador asignado.";
        } elseif ($person->evaluator && $person->evaluator->status_id == 23) {
          $issues[] = "El evaluador {$person->evaluator->nombre_completo} de la persona {$person->nombre_completo} está dado de baja.";
        }
      }

      if ($total === 0 && empty($issues)) {
        $pass = false;
        $issues = ['No tiene personas asignadas'];
      } elseif (empty($issues)) {
        $pass = true;
        $issues = ['Todas las personas tienen evaluador válido'];
      } else {
        $pass = false;
      }

      $category->pass = $pass;
      $category->issues = $issues;
      $category->total_personas = $total;
      $category->con_jefe = $conJefe;
      $category->sin_jefe = $sinJefe;

      return $category;
    });
  }

  public static function whereAllPersonsHaveJefeBuilder(): Builder
  {
    $T_CAT = 'gh_hierarchical_category';
    $T_DET = 'gh_hierarchical_category_detail';
    $T_POS = 'rrhh_cargo';
    $T_PER = 'rrhh_persona';
    $T_EPD = 'gh_evaluation_person_detail';
    $T_COMP = 'gh_evaluation_category_competence';

    $cycleId = null;

    $eligibles = function ($q) use ($T_EPD, $cycleId) {
      $q->where('p.status_deleted', 1)
        ->where('p.b_empleado', 1)
        ->where('p.status_id', 22)
        ->whereNotExists(function ($sq) use ($T_EPD, $cycleId) {
          $sq->from("$T_EPD as epd")
            ->whereColumn('epd.person_id', 'p.id')
            ->whereNull('epd.deleted_at');
          if (!is_null($cycleId)) {
            $sq->where('epd.cycle_id', $cycleId);
          }
        });
    };

    $base = fn() => DB::table("$T_PER as p")
      ->join("$T_POS as pos", 'pos.id', '=', 'p.cargo_id')
      ->join("$T_DET as hcd", 'hcd.position_id', '=', 'pos.id')
      ->whereColumn("hcd.hierarchical_category_id", "$T_CAT.id")
      ->where($eligibles);

    $sqTotal = $base()->cloneWithout([])->selectRaw('COUNT(*)');
    $sqConJefe = $base()->cloneWithout([])->whereNotNull('p.supervisor_id')->selectRaw('COUNT(*)');
    $sqSinJefe = $base()->cloneWithout([])->whereNull('p.supervisor_id')->selectRaw('COUNT(*)');

    $sqConJefeBaja = $base()->cloneWithout([])
      ->leftJoin("$T_PER as b", 'b.id', '=', 'p.supervisor_id')
      ->whereNotNull('p.supervisor_id')
      ->where('b.status_id', 23)
      ->selectRaw('COUNT(*)');

    $sqCompetences = DB::query()->fromRaw("$T_COMP as comp")
      ->whereColumn("comp.category_id", "$T_CAT.id")
      ->whereNull('comp.deleted_at')
      ->selectRaw('COUNT(DISTINCT comp.competence_id)');

    $sqIssuesRaw = $base()->cloneWithout([])
      ->leftJoin("$T_PER as b", 'b.id', '=', 'p.supervisor_id')
      ->where(function ($q) {
        $q->whereNull('p.supervisor_id')->orWhere('b.status_id', 23);
      })
      ->selectRaw("
      JSON_ARRAYAGG(
        CASE
          WHEN p.supervisor_id IS NULL
            THEN CONCAT('La persona ', p.nombre_completo, ' no tiene evaluador asignado.')
          WHEN b.status_id = 23
            THEN CONCAT('El evaluador ', b.nombre_completo, ' de la persona ', p.nombre_completo, ' está dado de baja.')
        END
      )
    ");

    $issuesFinal = DB::query()->selectRaw("
    COALESCE(
      ( {$sqIssuesRaw->toSql()} ),
      CASE
        WHEN ( {$sqTotal->toSql()} ) = 0
          THEN JSON_ARRAY('No tiene personas asignadas')
        ELSE JSON_ARRAY('Todas las personas tienen evaluador válido')
      END
    )
  ")->setBindings(array_merge(
      $sqIssuesRaw->getBindings(),
      $sqTotal->getBindings()
    ));

    $passExpr = DB::query()->selectRaw("
    CASE
      WHEN ( {$sqTotal->toSql()} ) = 0 THEN 0
      WHEN $T_CAT.hasObjectives = 0 AND ({$sqCompetences->toSql()}) = 0 THEN 0
      WHEN ( {$sqSinJefe->toSql()} ) = 0 AND ( {$sqConJefeBaja->toSql()} ) = 0 THEN 1
      ELSE 0
    END
  ")->setBindings(array_merge(
      $sqTotal->getBindings(),
      $sqCompetences->getBindings(),
      $sqSinJefe->getBindings(),
      $sqConJefeBaja->getBindings()
    ));

    return self::query()
      ->from($T_CAT)
      ->where('excluded_from_evaluation', false)
      ->select("$T_CAT.*")
      ->selectSub($sqTotal, 'total_personas')
      ->selectSub($sqConJefe, 'con_jefe')
      ->selectSub($sqSinJefe, 'sin_jefe')
      ->selectSub($sqConJefeBaja, 'con_jefe_baja')
      ->selectSub($sqCompetences, 'competences_count')
      ->selectSub($issuesFinal, 'issues')
      ->selectSub($passExpr, 'pass');
  }


}

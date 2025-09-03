<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Position;
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
    )->wherePivotNull('deleted_at')->distinct(); // 👈 solo los que no están eliminados
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
      Person::class,
      HierarchicalCategoryDetail::class,
      'hierarchical_category_id',
      'cargo_id',
      'id',
      'position_id'
    )->where('rrhh_persona.status_id', 22);
  }

  public static function whereAllPersonsHaveJefe(bool $hasObjectives)
  {
    $categories = self::where('excluded_from_evaluation', false)
      ->when($hasObjectives === true, function ($q) {
        $q->where('hasObjectives', true);
      })
      ->with([
        'positions' => function ($q) {
          $q->with(['persons' => function ($q2) {
            $q2->where('status_deleted', 1)
              ->where('b_empleado', 1)
              ->where('status_id', 22)
              ->whereDoesntHave('evaluationDetails')
              ->with([
                'boss' => function ($qb) {
                  $qb->with('position'); // traemos cargo del jefe
                },
                'position',
              ]);
          }]);
        }
      ])
      ->get();

    return $categories->map(function ($category) {
      $persons = $category->positions->flatMap->persons;
      $total = $persons->count();
      $conJefe = $persons->whereNotNull('jefe_id')->count();
      $sinJefe = $persons->whereNull('jefe_id')->count();

      $issues = [];

      foreach ($persons as $person) {
        if (is_null($person->jefe_id)) {
          $issues[] = "La persona {$person->nombre_completo} no tiene jefe asignado.";
        } elseif ($person->boss && $person->boss->status_id == 23) {
          $issues[] = "El jefe {$person->boss->nombre_completo} de la persona {$person->nombre_completo} está dado de baja.";
        }
      }

      if ($total === 0) {
        $pass = false;
        $issues = ['No tiene personas asignadas'];
      } elseif (empty($issues)) {
        $pass = true;
        $issues = ['Todas las personas tienen jefe válido'];
      } else {
        $pass = false;
      }

      $category->pass = $pass;
      $category->issues = $issues; // ahora es un array de strings
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
    $T_EPD = 'gh_evaluation_person_detail'; // <-- ajusta al nombre real

    // si necesitas por ciclo, setea aquí; si no, déjalo como null
    $cycleId = null;

    $eligibles = function ($q) use ($T_EPD, $cycleId) {
      $q->where('p.status_deleted', 1)
        ->where('p.b_empleado', 1)
        ->where('p.status_id', 22)
        // personas SIN registros activos en evaluation_person_detail
        ->whereNotExists(function ($sq) use ($T_EPD, $cycleId) {
          $sq->from("$T_EPD as epd")
            ->whereColumn('epd.person_id', 'p.id')
            ->whereNull('epd.deleted_at'); // quita esta línea si el modelo usa SoftDeletes
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
    $sqConJefe = $base()->cloneWithout([])->whereNotNull('p.jefe_id')->selectRaw('COUNT(*)');
    $sqSinJefe = $base()->cloneWithout([])->whereNull('p.jefe_id')->selectRaw('COUNT(*)');

    $sqConJefeBaja = $base()->cloneWithout([])
      ->leftJoin("$T_PER as b", 'b.id', '=', 'p.jefe_id')
      ->whereNotNull('p.jefe_id')
      ->where('b.status_id', 23)
      ->selectRaw('COUNT(*)');

    $sqIssuesRaw = $base()->cloneWithout([])
      ->leftJoin("$T_PER as b", 'b.id', '=', 'p.jefe_id')
      ->where(function ($q) {
        $q->whereNull('p.jefe_id')->orWhere('b.status_id', 23);
      })
      ->selectRaw("
      JSON_ARRAYAGG(
        CASE
          WHEN p.jefe_id IS NULL
            THEN CONCAT('La persona ', p.nombre_completo, ' no tiene jefe asignado.')
          WHEN b.status_id = 23
            THEN CONCAT('El jefe ', b.nombre_completo, ' de la persona ', p.nombre_completo, ' está dado de baja.')
        END
      )
    ");

    $issuesFinal = DB::query()->selectRaw("
    COALESCE(
      ( {$sqIssuesRaw->toSql()} ),
      CASE
        WHEN ( {$sqTotal->toSql()} ) = 0
          THEN JSON_ARRAY('No tiene personas asignadas')
        ELSE JSON_ARRAY('Todas las personas tienen jefe válido')
      END
    )
  ")->setBindings(array_merge(
      $sqIssuesRaw->getBindings(),
      $sqTotal->getBindings()
    ));

    $passExpr = DB::query()->selectRaw("
    CASE
      WHEN ( {$sqTotal->toSql()} ) = 0 THEN 0
      WHEN ( {$sqSinJefe->toSql()} ) = 0 AND ( {$sqConJefeBaja->toSql()} ) = 0 THEN 1
      ELSE 0
    END
  ")->setBindings(array_merge(
      $sqTotal->getBindings(),
      $sqSinJefe->getBindings(),
      $sqConJefeBaja->getBindings()
    ));

    return self::query()
      ->from($T_CAT)
//      ->where('excluded_from_evaluation', false)
      ->select("$T_CAT.*")
      ->selectSub($sqTotal, 'total_personas')
      ->selectSub($sqConJefe, 'con_jefe')
      ->selectSub($sqSinJefe, 'sin_jefe')
      ->selectSub($sqConJefeBaja, 'con_jefe_baja')
      ->selectSub($issuesFinal, 'issues')
      ->selectSub($passExpr, 'pass');
  }


}

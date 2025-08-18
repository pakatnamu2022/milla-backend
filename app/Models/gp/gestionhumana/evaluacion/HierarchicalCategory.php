<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Position;
use Illuminate\Database\Eloquent\SoftDeletes;

class HierarchicalCategory extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_hierarchical_category';

    protected $fillable = [
        'name',
        'description',
        'excluded_from_evaluation',
    ];

    const filters = [
        'search' => ['name', 'description'],
        'excluded_from_evaluation' => '=',
    ];

    const sorts = [
        'name',
        'description'
    ];

    public function children()
    {
        return $this->hasMany(HierarchicalCategoryDetail::class, 'hierarchical_category_id');
    }

    public function objectives()
    {
        return $this->belongsToMany(
            EvaluationObjective::class,
            'gh_evaluation_category_objective_detail',
            'category_id',
            'objective_id'
        );
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

    public static function whereAllPersonsHaveJefe()
    {
        $categories = self::where('excluded_from_evaluation', false)
            ->with([
                'positions' => function ($q) {
                    $q->with(['persons' => function ($q2) {
                        $q2->where('status_deleted', 1)
                            ->where('b_empleado', 1)
                            ->where('status_id', 22)
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


}

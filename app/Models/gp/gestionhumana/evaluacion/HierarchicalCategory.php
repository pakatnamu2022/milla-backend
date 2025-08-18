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
        return self::where('excluded_from_evaluation', false)
            ->whereHas('positions.persons', function ($query) {
                $query->where('b_empleado', 1);
            })
            ->with([
                'positions' => function ($q) {
                    $q->with(['persons' => function ($q2) {
                        $q2->where('b_empleado', 1);
                    }]);
                }
            ])
            ->get();
    }

}

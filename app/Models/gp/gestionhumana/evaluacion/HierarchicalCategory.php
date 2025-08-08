<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class HierarchicalCategory extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_hierarchical_category';

    protected $fillable = [
        'name',
        'description',
    ];

    const filters = [
        'search' => ['name', 'description'],
    ];

    const sorts = [
        'name',
        'description'
    ];

    public function children()
    {
        return $this->hasMany(HierarchicalCategoryDetail::class, 'hierarchical_category_id');
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


}

<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;

class EvaluationCycleCategoryDetail extends Model
{
    protected $table = 'gh_evaluation_cycle_category_detail';

    protected $fillable = [
        'cycle_id',
        'hierarchical_category_id',
    ];

    public function cycle()
    {
        return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
    }

    public function hierarchicalCategory()
    {
        return $this->belongsTo(HierarchicalCategory::class, 'hierarchical_category_id');
    }
}

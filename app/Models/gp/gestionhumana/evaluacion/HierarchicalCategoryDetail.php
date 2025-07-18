<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;

class HierarchicalCategoryDetail extends BaseModel
{
    protected $table = 'gh_hierarchical_category_detail';

    protected $fillable = [
        'hierarchical_category_id',
        'position_id',
    ];

    const filters = [
        'search' => ['hierarchicalCategory.name', 'description'],
    ];

    const sorts = [
        'hierarchical_category_id',
        'position_id',
    ];

    public function hierarchicalCategory()
    {
        return $this->belongsTo(HierarchicalCategory::class, 'hierarchical_category_id');
    }
}

<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Position;
use Illuminate\Database\Eloquent\SoftDeletes;

class HierarchicalCategoryDetail extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_hierarchical_category_detail';

  protected $fillable = [
    'hierarchical_category_id',
    'position_id',
  ];

  const filters = [
    'search' => ['hierarchicalCategory.name', 'hierarchicalCategory.description'],
    'position.name' => 'like',
  ];

  const sorts = [
    'hierarchical_category_id',
    'position_id',
  ];

  public function hierarchicalCategory()
  {
    return $this->belongsTo(HierarchicalCategory::class, 'hierarchical_category_id');
  }

  public function position()
  {
    return $this->belongsTo(Position::class, 'position_id');
  }
}

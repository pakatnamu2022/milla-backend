<?php

namespace App\Models\gp\tics\pm;

use App\Models\BaseModel;

class ScrumTag extends BaseModel
{
  protected $table = 'scrum_tags';

  protected $fillable = [
    'project_id',
    'name',
    'color',
  ];

  const filters = [
    'id'         => '=',
    'project_id' => '=',
    'search'     => ['name'],
  ];

  const sorts = [
    'id'   => 'asc',
    'name' => 'asc',
  ];

  public function project()
  {
    return $this->belongsTo(ScrumProject::class, 'project_id');
  }

  public function items()
  {
    return $this->belongsToMany(ScrumItem::class, 'scrum_item_tag', 'tag_id', 'item_id');
  }
}

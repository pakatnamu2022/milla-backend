<?php

namespace App\Models\gp\tics\pm;

use App\Models\BaseModel;

class ScrumSprint extends BaseModel
{
  protected $table = 'scrum_sprints';

  protected $fillable = [
    'project_id',
    'name',
    'goal',
    'start_date',
    'end_date',
    'status',
  ];

  protected $casts = [
    'start_date' => 'date',
    'end_date'   => 'date',
  ];

  const filters = [
    'id'         => '=',
    'project_id' => '=',
    'status'     => '=',
  ];

  const sorts = [
    'id'         => 'asc',
    'name'       => 'asc',
    'start_date' => 'asc',
    'end_date'   => 'asc',
    'status'     => 'asc',
  ];

  public function project()
  {
    return $this->belongsTo(ScrumProject::class, 'project_id');
  }

  public function items()
  {
    return $this->hasMany(ScrumItem::class, 'sprint_id');
  }

  public function getCompletionPercentageAttribute(): float
  {
    $total = $this->items()->count();
    if ($total === 0) return 0;
    $done = $this->items()->where('status', 'hecho')->count();
    return round(($done / $total) * 100, 1);
  }
}

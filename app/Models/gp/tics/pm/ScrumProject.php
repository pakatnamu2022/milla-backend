<?php

namespace App\Models\gp\tics\pm;

use App\Models\BaseModel;
use App\Models\User;

class ScrumProject extends BaseModel
{
  protected $table = 'scrum_projects';

  protected $fillable = [
    'name',
    'description',
    'color',
    'status',
    'created_by',
  ];

  const filters = [
    'id'     => '=',
    'status' => '=',
    'search' => ['name', 'description'],
  ];

  const sorts = [
    'id'         => 'desc',
    'name'       => 'asc',
    'status'     => 'asc',
    'created_at' => 'desc',
  ];

  public function sprints()
  {
    return $this->hasMany(ScrumSprint::class, 'project_id');
  }

  public function items()
  {
    return $this->hasMany(ScrumItem::class, 'project_id');
  }

  public function tags()
  {
    return $this->hasMany(ScrumTag::class, 'project_id');
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function activeSprint()
  {
    return $this->hasOne(ScrumSprint::class, 'project_id')->where('status', 'activo');
  }
}

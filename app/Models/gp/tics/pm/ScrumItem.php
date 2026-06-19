<?php

namespace App\Models\gp\tics\pm;

use App\Models\BaseModel;
use App\Models\User;

class ScrumItem extends BaseModel
{
  protected $table = 'scrum_items';

  protected $fillable = [
    'project_id',
    'sprint_id',
    'parent_id',
    'type',
    'title',
    'description',
    'status',
    'priority',
    'assigned_to',
    'created_by',
    'story_points',
    'estimated_hours',
    'actual_hours',
    'order',
    'due_date',
    'closed_at',
  ];

  protected $casts = [
    'due_date'  => 'date:Y-m-d',
    'closed_at' => 'datetime',
  ];

  const filters = [
    'id'          => '=',
    'project_id'  => '=',
    'sprint_id'   => '=',
    'parent_id'   => '=',
    'type'        => '=',
    'status'      => '=',
    'priority'    => '=',
    'assigned_to' => '=',
    'created_by'  => '=',
    'search'      => ['title', 'description'],
  ];

  const sorts = [
    'id'              => 'desc',
    'order'           => 'asc',
    'priority'        => 'asc',
    'status'          => 'asc',
    'story_points'    => 'asc',
    'estimated_hours' => 'asc',
    'due_date'        => 'asc',
    'created_at'      => 'desc',
  ];

  public function project()
  {
    return $this->belongsTo(ScrumProject::class, 'project_id');
  }

  public function sprint()
  {
    return $this->belongsTo(ScrumSprint::class, 'sprint_id');
  }

  public function parent()
  {
    return $this->belongsTo(ScrumItem::class, 'parent_id');
  }

  public function children()
  {
    return $this->hasMany(ScrumItem::class, 'parent_id');
  }

  public function assignee()
  {
    return $this->belongsTo(User::class, 'assigned_to');
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function comments()
  {
    return $this->hasMany(ScrumComment::class, 'item_id');
  }

  public function history()
  {
    return $this->hasMany(ScrumItemHistory::class, 'item_id')->orderByDesc('created_at');
  }

  public function tags()
  {
    return $this->belongsToMany(ScrumTag::class, 'scrum_item_tag', 'item_id', 'tag_id');
  }

  public function watchers()
  {
    return $this->belongsToMany(User::class, 'scrum_item_watcher', 'item_id', 'user_id');
  }
}

<?php

namespace App\Models\gp\tics\pm;

use App\Models\BaseModel;
use App\Models\User;

class ScrumComment extends BaseModel
{
  protected $table = 'scrum_comments';

  protected $fillable = [
    'item_id',
    'user_id',
    'content',
  ];

  const filters = [
    'item_id' => '=',
    'user_id' => '=',
  ];

  const sorts = [
    'id'         => 'asc',
    'created_at' => 'asc',
  ];

  public function item()
  {
    return $this->belongsTo(ScrumItem::class, 'item_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}

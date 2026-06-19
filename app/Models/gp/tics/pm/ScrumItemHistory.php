<?php

namespace App\Models\gp\tics\pm;

use App\Models\BaseModel;
use App\Models\User;

class ScrumItemHistory extends BaseModel
{
  protected $table = 'scrum_item_history';
  public $timestamps = false;

  protected $fillable = [
    'item_id',
    'user_id',
    'field',
    'old_value',
    'new_value',
    'created_at',
  ];

  protected $casts = [
    'created_at' => 'datetime',
  ];

  const filters = [
    'item_id' => '=',
    'user_id' => '=',
    'field'   => '=',
  ];

  const sorts = [
    'created_at' => 'desc',
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

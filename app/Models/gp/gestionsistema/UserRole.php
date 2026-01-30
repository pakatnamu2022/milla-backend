<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\User;

class UserRole extends BaseModel
{
  protected $table = 'config_asig_role_user';

  protected $fillable = ['id', 'role_id', 'user_id', 'status_deleted'];

  const filters = [
    'search' => [],
    'role_id' => '=',
    'user_id' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'role_id' => 'asc',
    'user_id' => 'asc',
  ];

  public function user()
  {
    return $this->hasOne(User::class, 'id', 'user_id');
  }

  public function role()
  {
    return $this->hasOne(Role::class, 'id', 'role_id');
  }
}

<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
  protected $table = 'department';
  protected $fillable = ['name'];

  const filters = ['search' => ['name']];
  const sorts = ['name', 'created_at', 'updated_at'];

  public function provinces()
  {
    return $this->hasMany(Province::class);
  }
}

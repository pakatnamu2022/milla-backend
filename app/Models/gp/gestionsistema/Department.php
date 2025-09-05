<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
  protected $table = 'department';
  protected $fillable = ['name'];

  public function provinces()
  {
    return $this->hasMany(Province::class);
  }
}

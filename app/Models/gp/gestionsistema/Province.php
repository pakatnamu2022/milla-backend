<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
  protected $table = 'province';
  protected $fillable = ['name', 'department_id'];

  public function department()
  {
    return $this->belongsTo(Department::class);
  }

  public function districts()
  {
    return $this->hasMany(District::class);
  }
}

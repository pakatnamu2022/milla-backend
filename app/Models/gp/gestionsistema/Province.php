<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
  protected $table = 'province';
  protected $fillable = ['name', 'department_id'];

  const filters = [
    'search' => ['name'],
    'department_id' => '=',
  ];

  const sorts = ['name'];

  public function department()
  {
    return $this->belongsTo(Department::class);
  }
}

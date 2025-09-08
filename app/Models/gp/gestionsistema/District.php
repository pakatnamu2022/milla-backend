<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
  protected $table = 'district';
  protected $fillable = ['name', 'ubigeo', 'province_id'];

  const filters = [
    'search' => ['name', 'ubigeo'],
    'province_id' => '=',
  ];

  const sorts = ['name', 'ubigeo'];

  public function province()
  {
    return $this->belongsTo(Province::class);
  }
}

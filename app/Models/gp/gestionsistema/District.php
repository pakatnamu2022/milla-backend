<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
  protected $table = 'district';
  protected $fillable = ['name', 'ubigeo', 'sendCost', 'province_id'];

  public function province()
  {
    return $this->belongsTo(Province::class);
  }
}

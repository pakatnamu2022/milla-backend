<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class District extends Model
{
  protected $table = 'district';
  protected $fillable = ['name', 'ubigeo', 'province_id'];

  const filters = [
    'search' => ['name', 'ubigeo'],
    'province_id' => '=',
  ];

  const sorts = ['name', 'ubigeo'];

  public function setNameAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setUbigeoAttribute($value)
  {
    $this->attributes['ubigeo'] = Str::upper(Str::ascii($value));
  }

  public function province()
  {
    return $this->belongsTo(Province::class);
  }
}

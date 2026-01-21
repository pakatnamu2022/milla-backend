<?php

namespace App\Models\gp\gestionsistema;

use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class District extends Model
{
  protected $table = 'district';
  protected $fillable = ['name', 'ubigeo', 'province_id'];

  const filters = [
    'search' => ['name', 'ubigeo'],
    'province_id' => '=',
    'province.department_id' => '=',
    'id' => 'in',
    'has_sede' => 'accessor_bool',
  ];

  const sorts = ['name', 'ubigeo'];

  public function getHasSedeAttribute()
  {
    return $this->hasMany(Sede::class)->exists();
  }

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
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

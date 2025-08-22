<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApBrandGroups extends Model
{
  use SoftDeletes;

  protected $table = 'ap_grupo_marca';

  protected $fillable = [
    'id',
    'name',
  ];

  const filters = [
    'search' => ['name'],
    'name' => 'like',
  ];

  const sorts = [
    'id',
    'name',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }
}

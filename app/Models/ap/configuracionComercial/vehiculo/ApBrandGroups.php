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
    'status',
  ];

  const filters = [
    'search' => ['name'],
  ];

  const sorts = [
    'name',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }
}

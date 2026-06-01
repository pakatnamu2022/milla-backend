<?php

namespace App\Models\gp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GpMasters extends Model
{
  use SoftDeletes;

  protected $table = 'gp_masters';

  protected $fillable = [
    'id',
    'code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type' => 'in_or_equal',
    'status' => '=',
  ];

  const sorts = [
    'code',
    'description',
    'status',
    'type',
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  public function scopeOfType($query, string $type)
  {
    return $query->where('type', strtoupper($type));
  }
}
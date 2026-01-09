<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GeneralMaster extends Model
{
  use SoftDeletes;

  protected $table = 'general_masters';

  protected $fillable = [
    'id',
    'code',
    'description',
    'type',
    'value',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type' => '=',
    'status' => '=',
    'code' => '=',
  ];

  const sorts = [
    'code',
    'description',
    'status',
    'type',
  ];

  const string PER_DIEM_MIN_DAYS = 'PER_DIEM_MIN_DAYS';

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

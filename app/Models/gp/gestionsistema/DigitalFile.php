<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalFile extends Model
{
  use SoftDeletes;

  protected $table = 'gp_digital_files';

  protected $fillable = [
    'name',
    'description',
    'url',
    'mimeType',
    'model',
    'id_model'
  ];

  const filters = [
    'id' => '=',
    'model' => '=',
    'id_model' => '=',
    'search' => ['name', 'mimeType'],
  ];

  const sorts = [
    'id',
    'name',
    'mimeType',
    'model',
    'id_model',
    'created_at',
    'updated_at'
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at'
  ];

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = strtoupper($value);
  }
}

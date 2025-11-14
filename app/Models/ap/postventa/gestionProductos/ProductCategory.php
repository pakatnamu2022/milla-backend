<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApPostVentaMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
  use SoftDeletes;

  protected $table = 'product_category';

  protected $fillable = [
    'name',
    'description',
    'status',
    'type_id',
  ];

  const filters = [
    'search' => ['name', 'description', 'type.description'],
    'type_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'name',
    'description',
    'type',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function type()
  {
    return $this->belongsTo(ApPostVentaMasters::class, 'type_id');
  }
}

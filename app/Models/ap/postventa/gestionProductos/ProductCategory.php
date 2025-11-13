<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApPostVentaMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

  public function type()
  {
    return $this->belongsTo(ApPostVentaMasters::class, 'type_id');
  }
}

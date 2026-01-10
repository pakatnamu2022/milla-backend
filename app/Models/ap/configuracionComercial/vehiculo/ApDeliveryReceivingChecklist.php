<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApDeliveryReceivingChecklist extends Model
{
  use SoftDeletes;

  protected $table = 'ap_delivery_receiving_checklist';

  protected $fillable = [
    'id',
    'description',
    'type',
    'category_id',
    'has_quantity',
    'status'
  ];

  const filters = [
    'search' => ['description', 'type'],
    'type' => '='
  ];

  const sorts = [
    'description',
    'type',
  ];

  //casteamos al tipo booleano
  protected $casts = [
    'status' => 'boolean',
    'has_quantity' => 'boolean',
  ];

  public function category()
  {
    return $this->belongsTo(ApMasters::class, 'category_id');
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }
}

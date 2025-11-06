<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Warehouse extends Model
{
  use SoftDeletes;

  protected $table = 'warehouse';

  protected $fillable = [
    'dyn_code',
    'description',
    'article_class_id',
    'type',
    'status',
    'is_received',
    'sede_id',
    'type_operation_id',
    'inventory_account',
    'counterparty_account',
  ];

  const filters = [
    'search' => ['dyn_code', 'description', 'inventory_account', 'counterparty_account'],
    'article_class_id' => '=',
    'type' => '=',
    'status' => '=',
    'is_received' => '=',
    'sede_id' => '=',
    'type_operation_id' => '=',
  ];

  const sorts = [
    'id',
    'dyn_code',
    'description',
    'sede_id',
    'type_operation_id',
    'status',
  ];

  const REAL = 'REAL';
  const PHYSICAL = 'FISICO';

  public function setDynCodeAttribute($value): void
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value): void
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function typeOperation(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_operation_id');
  }

  public function articleClass(): BelongsTo
  {
    return $this->belongsTo(ApClassArticle::class, 'article_class_id');
  }
}

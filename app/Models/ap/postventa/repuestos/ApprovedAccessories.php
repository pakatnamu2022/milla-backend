<?php

namespace App\Models\ap\postventa\repuestos;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApprovedAccessories extends Model
{
  use SoftDeletes;

  protected $table = 'approved_accessories';

  protected $fillable = [
    'code',
    'code_dynamics',
    'type_operation_id',
    'description',
    'status',
    'type_currency_id',
  ];

  const filters = [
    'search' => ['code', 'description'],
    'status' => '=',
    'type_currency_id' => '=',
    'type_operation_id' => '=',
    'body_type_id' => 'scope',
  ];

  const sorts = [
    'code',
    'description',
  ];

  public function setCodeAttribute($value): void
  {
    if ($value) {
      $this->attributes['code'] = Str::upper($value);
    }
  }

  public function setDescriptionAttribute($value): void
  {
    if ($value) {
      $this->attributes['description'] = Str::upper($value);
    }
  }

  /**
   * Filtra los accesorios que tienen un precio para la carrocería indicada.
   * Se usa en la solicitud de compra para listar solo lo que aplica al
   * vehículo/modelo seleccionado.
   */
  public function scopeBodyTypeId(Builder $query, $bodyTypeId): Builder
  {
    return $query->whereHas('prices', function ($q) use ($bodyTypeId) {
      $q->where('body_type_id', $bodyTypeId);
    });
  }

  public function prices(): HasMany
  {
    return $this->hasMany(ApprovedAccessoryPrice::class, 'approved_accessory_id');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
  }

  public function typeOperation(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'type_operation_id');
  }
}

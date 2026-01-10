<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApClassArticle extends Model
{
  use SoftDeletes;

  protected $table = 'ap_class_article';

  protected $fillable = [
    'dyn_code',
    'description',
    'account',
    'type_operation_id',
    'type_class_id',
    'status',
  ];

  const filters = [
    'search' => ['dyn_code', 'description', 'account', 'typeOperation.description'],
    'type_operation_id' => '='
  ];

  const sorts = [
    'dyn_code',
    'description',
  ];

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setAccountAttribute($value)
  {
    $this->attributes['account'] = Str::upper(Str::ascii($value));
  }

  public function typeOperation(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'type_operation_id');
  }

  /**
   * Relación con los mapeos de cuentas contables
   */
  public function accountMappings(): HasMany
  {
    return $this->hasMany(ApClassArticleAccountMapping::class, 'ap_class_article_id');
  }

  /**
   * Obtiene el mapeo de cuenta para un tipo específico
   */
  public function getAccountMapping(string $accountType): ?ApClassArticleAccountMapping
  {
    return $this->accountMappings()
      ->where('account_type', $accountType)
      ->where('status', true)
      ->first();
  }

  public function typeClass()
  {
    return $this->belongsTo(ApMasters::class, 'type_class_id');
  }
}

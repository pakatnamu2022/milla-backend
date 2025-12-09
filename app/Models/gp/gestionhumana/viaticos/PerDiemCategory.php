<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemCategory extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_per_diem_category';

  protected $fillable = [
    'name',
    'description',
    'active',
    'order',
  ];

  protected $casts = [
    'active' => 'boolean',
  ];

  const filters = [
    'search' => ['name', 'description'],
    'active' => '=',
  ];

  const sorts = [
    'name',
    'order',
    'active',
  ];

  /**
   * Get all per diem rates for this category
   */
  public function perDiemRates(): HasMany
  {
    return $this->hasMany(PerDiemRate::class);
  }

  /**
   * Get all per diem requests for this category
   */
  public function perDiemRequests(): HasMany
  {
    return $this->hasMany(PerDiemRequest::class);
  }

  /**
   * Scope to filter active categories ordered by order field
   */
  public function scopeActive($query)
  {
    return $query->where('active', true)->orderBy('order');
  }
}

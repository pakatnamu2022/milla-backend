<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PerDiemPolicy extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_per_diem_policy';

  protected $fillable = [
    'version',
    'name',
    'effective_from',
    'effective_to',
    'is_current',
    'document_path',
    'notes',
    'created_by',
  ];

  protected $casts = [
    'effective_from' => 'date',
    'effective_to' => 'date',
    'is_current' => 'boolean',
  ];

  const filters = [
    'search' => ['version', 'name', 'notes'],
    'is_current' => '=',
    'effective_from' => 'between',
    'effective_to' => 'between',
    'created_by' => '=',
  ];

  const sorts = [
    'version',
    'name',
    'effective_from',
    'effective_to',
    'is_current',
    'created_at',
  ];

  /**
   * Get all per diem rates for this policy
   */
  public function perDiemRates(): HasMany
  {
    return $this->hasMany(PerDiemRate::class);
  }

  /**
   * Get all per diem requests for this policy
   */
  public function perDiemRequests(): HasMany
  {
    return $this->hasMany(PerDiemRequest::class);
  }

  /**
   * Get the user who created this policy
   */
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  /**
   * Scope to filter current policy
   */
  public function scopeCurrent($query)
  {
    return $query->where('is_current', true);
  }

  /**
   * Scope to filter policies effective on a specific date
   */
  public function scopeEffectiveOn($query, $date)
  {
    return $query->where('effective_from', '<=', $date)
      ->where(function ($q) use ($date) {
        $q->whereNull('effective_to')
          ->orWhere('effective_to', '>=', $date);
      });
  }

  /**
   * Activate this policy and deactivate all others
   */
  public function activate(): bool
  {
    return DB::transaction(function () {
      // Deactivate all other policies
      self::where('id', '!=', $this->id)->update(['is_current' => false]);

      // Activate this policy
      $this->is_current = true;
      return $this->save();
    });
  }

  /**
   * Close this policy with an end date
   */
  public function close($endDate): bool
  {
    $this->effective_to = $endDate;
    $this->is_current = false;
    return $this->save();
  }
}

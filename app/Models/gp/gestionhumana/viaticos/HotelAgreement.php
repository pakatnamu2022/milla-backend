<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelAgreement extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_hotel_agreement';

  protected $fillable = [
    'city',
    'name',
    'corporate_rate',
    'features',
    'includes_breakfast',
    'includes_lunch',
    'includes_dinner',
    'includes_parking',
    'email',
    'phone',
    'address',
    'website',
    'active',
  ];

  protected $casts = [
    'corporate_rate' => 'decimal:2',
    'includes_breakfast' => 'boolean',
    'includes_lunch' => 'boolean',
    'includes_dinner' => 'boolean',
    'includes_parking' => 'boolean',
    'active' => 'boolean',
  ];

  const filters = [
    'search' => ['name', 'city', 'email', 'phone'],
    'city' => '=',
    'active' => '=',
    'includes_breakfast' => '=',
    'includes_lunch' => '=',
    'includes_dinner' => '=',
    'includes_parking' => '=',
  ];

  const sorts = [
    'name',
    'city',
    'corporate_rate',
    'active',
  ];

  /**
   * Get all reservations for this hotel agreement
   */
  public function reservations(): HasMany
  {
    return $this->hasMany(HotelReservation::class);
  }

  /**
   * Scope to filter active hotel agreements
   */
  public function scopeActive($query)
  {
    return $query->where('active', true);
  }

  /**
   * Scope to filter hotel agreements by city
   */
  public function scopeByCity($query, string $city)
  {
    return $query->where('city', $city);
  }

  /**
   * Scope to order hotel agreements by corporate rate
   */
  public function scopeOrderByRate($query, string $direction = 'asc')
  {
    return $query->orderBy('corporate_rate', $direction);
  }
}

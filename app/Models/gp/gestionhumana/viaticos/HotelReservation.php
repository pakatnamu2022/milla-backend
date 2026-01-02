<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelReservation extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_hotel_reservation';

  protected $fillable = [
    'per_diem_request_id',
    'hotel_agreement_id',
    'hotel_name',
    'address',
    'phone',
    'checkin_date',
    'checkout_date',
    'nights_count',
    'total_cost',
    'receipt_path',
    'notes',
    'attended',
    'penalty',
  ];

  protected $casts = [
    'checkin_date' => 'date',
    'checkout_date' => 'date',
    'total_cost' => 'decimal:2',
    'penalty' => 'decimal:2',
    'attended' => 'boolean',
  ];

  const filters = [
    'per_diem_request_id' => '=',
    'hotel_agreement_id' => '=',
    'attended' => '=',
    'checkin_date' => 'date_between',
    'checkout_date' => 'date_between',
  ];

  const sorts = [
    'checkin_date',
    'checkout_date',
    'total_cost',
    'attended',
    'created_at',
  ];

  /**
   * Get the per diem request this reservation belongs to
   */
  public function request(): BelongsTo
  {
    return $this->belongsTo(PerDiemRequest::class, 'per_diem_request_id');
  }

  public function expense(): BelongsTo
  {
    return $this->belongsTo(PerDiemExpense::class, 'id', 'hotel_reservation_id');
  }

  /**
   * Get the hotel agreement this reservation is associated with
   */
  public function hotelAgreement(): BelongsTo
  {
    return $this->belongsTo(HotelAgreement::class);
  }

  /**
   * Get the company expense associated with this reservation
   */
  public function companyExpense(): HasOne
  {
    return $this->hasOne(PerDiemExpense::class, 'hotel_reservation_id');
  }

  /**
   * Calculate the number of nights between checkin and checkout
   */
  public function calculateNights(): int
  {
    return $this->checkin_date->diffInDays($this->checkout_date);
  }

  /**
   * Check if this reservation has a receipt
   */
  public function hasReceipt(): bool
  {
    return !empty($this->receipt_path);
  }

  /**
   * Scope to filter reservations with penalty
   */
  public function scopeWithPenalty($query)
  {
    return $query->where('penalty', '>', 0);
  }
}

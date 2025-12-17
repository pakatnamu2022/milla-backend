<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemRequest extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_per_diem_request';

  protected $fillable = [
    'code',
    'per_diem_policy_id',
    'employee_id',
    'company_id',
    'destination',
    'per_diem_category_id',
    'start_date',
    'end_date',
    'days_count',
    'purpose',
    'cost_center',
    'status',
    'total_budget',
    'cash_amount',
    'transfer_amount',
    'paid',
    'payment_date',
    'payment_method',
    'settled',
    'settlement_date',
    'total_spent',
    'balance_to_return',
    'notes',
  ];

  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
    'payment_date' => 'date',
    'settlement_date' => 'date',
    'total_budget' => 'decimal:2',
    'cash_amount' => 'decimal:2',
    'transfer_amount' => 'decimal:2',
    'total_spent' => 'decimal:2',
    'balance_to_return' => 'decimal:2',
    'paid' => 'boolean',
    'settled' => 'boolean',
  ];

  const filters = [
    'search' => ['code', 'destination', 'purpose'],
    'status' => '=',
    'employee_id' => '=',
    'company_id' => '=',
    'per_diem_category_id' => '=',
    'per_diem_policy_id' => '=',
    'start_date' => 'date_between',
    'end_date' => 'date_between',
    'paid' => '=',
    'settled' => '=',
  ];

  const sorts = [
    'code',
    'status',
    'start_date',
    'end_date',
    'total_budget',
    'employee_id',
    'company_id',
    'created_at',
  ];

  /**
   * Get the policy this request belongs to
   */
  public function policy(): BelongsTo
  {
    return $this->belongsTo(PerDiemPolicy::class, 'per_diem_policy_id');
  }

  /**
   * Get the employee who made this request
   */
  public function employee(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'employee_id');
  }

  /**
   * Get the company this request belongs to
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  /**
   * Get the category this request belongs to
   */
  public function category(): BelongsTo
  {
    return $this->belongsTo(PerDiemCategory::class, 'per_diem_category_id');
  }

  /**
   * Get all budgets for this request
   */
  public function budgets(): HasMany
  {
    return $this->hasMany(RequestBudget::class);
  }

  /**
   * Get all approvals for this request
   */
  public function approvals(): HasMany
  {
    return $this->hasMany(PerDiemApproval::class);
  }

  /**
   * Get the hotel reservation for this request
   */
  public function hotelReservation(): HasOne
  {
    return $this->hasOne(HotelReservation::class);
  }

  /**
   * Get all expenses for this request
   */
  public function expenses(): HasMany
  {
    return $this->hasMany(PerDiemExpense::class);
  }

  /**
   * Scope to filter requests by status
   */
  public function scopeByStatus($query, string $status)
  {
    return $query->where('status', $status);
  }

  /**
   * Scope to filter requests by employee
   */
  public function scopeByEmployee($query, int $employeeId)
  {
    return $query->where('employee_id', $employeeId);
  }

  /**
   * Scope to filter requests pending settlement
   */
  public function scopePendingSettlement($query)
  {
    return $query->where('status', 'pending_settlement');
  }

  /**
   * Scope to filter overdue settlement requests (more than 3 days after end_date)
   */
  public function scopeOverdue($query)
  {
    return $query->where('status', 'pending_settlement')
      ->whereDate('end_date', '<=', now()->subDays(3));
  }

  /**
   * Generate a unique code for the request
   */
  public function generateCode(): string
  {
    $year = now()->year;
    $lastRequest = self::whereYear('created_at', $year)
      ->orderBy('id', 'desc')
      ->first();

    $sequence = $lastRequest ? (int)substr($lastRequest->code, -4) + 1 : 1;

    return 'PDR-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
  }

  /**
   * Calculate total budget from all budget items
   */
  public function calculateTotalBudget(): float
  {
    return $this->budgets()->sum('total');
  }

  /**
   * Check if expenses can be recorded for this request
   */
  public function canRecordExpenses(): bool
  {
    return in_array($this->status, ['approved', 'in_progress']);
  }

  /**
   * Get days without settlement since end_date
   */
  public function daysWithoutSettlement(): ?int
  {
    if ($this->status !== 'pending_settlement') {
      return null;
    }

    return $this->end_date->diffInDays(now());
  }
}

<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\District;
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
    'company_service_id',
    'district_id',
    'per_diem_category_id',
    'start_date',
    'end_date',
    'days_count',
    'purpose',
    'status',
    'total_budget',
    'cash_amount',
    'transfer_amount',
    'paid',
    'payment_date',
    'payment_method',
    'settled',
    'settlement_status',
    'settlement_date',
    'total_spent',
    'balance_to_return',
    'notes',
    'final_result',
    'with_active',
    'with_request',
    'deposit_voucher_url',
    'authorizer_id',
    'mobility_payroll_generated',
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
    'with_active' => 'boolean',
    'with_request' => 'boolean',
    'mobility_payroll_generated' => 'boolean',
  ];

  const filters = [
    'search' => ['code', 'purpose', 'employee.nombre_completo'],
    'status' => '=',
    'settlement_status' => '=',
    'employee_id' => '=',
    'authorizer_id' => '=',
    'company_id' => '=',
    'company_service_id' => '=',
    'district_id' => '=',
    'per_diem_category_id' => '=',
    'per_diem_policy_id' => '=',
    'start_date' => 'date_between',
    'end_date' => 'date_between',
    'paid' => '=',
    'settled' => '=',
    'with_active' => '=',
    'with_request' => '=',
  ];

  const sorts = [
    'code',
    'status',
    'start_date',
    'end_date',
    'total_budget',
    'employee_id',
    'company_id',
    'company_service_id',
    'district_id',
    'created_at',
  ];

  /**
   * SETTLEMENT STATUS VALUES
   */
  const string SETTLEMENT_PENDING = 'pending';
  const string SETTLEMENT_SUBMITTED = 'submitted';
  const string SETTLEMENT_APPROVED = 'approved';
  const string SETTLEMENT_REJECTED = 'rejected';
  const string SETTLEMENT_COMPLETED = 'completed';

  const array SETTLEMENT_STATUSES = [
    self::SETTLEMENT_PENDING,
    self::SETTLEMENT_SUBMITTED,
    self::SETTLEMENT_APPROVED,
    self::SETTLEMENT_REJECTED,
    self::SETTLEMENT_COMPLETED,
  ];

  /**
   * REQUEST STATUS VALUES
   */
  const string STATUS_PENDING = 'pending';
  const string STATUS_IN_PROGRESS = 'in_progress';
  const string STATUS_PENDING_SETTLEMENT = 'pending_settlement';
  const string STATUS_CANCELLED = 'cancelled';
  const string STATUS_APPROVED = 'approved';
  const string STATUS_REJECTED = 'rejected';
  const string STATUS_SETTLED = 'settled';

  const array STATUSES = [
    self::STATUS_PENDING,
    self::STATUS_IN_PROGRESS,
    self::STATUS_PENDING_SETTLEMENT,
    self::STATUS_CANCELLED,
    self::STATUS_APPROVED,
    self::STATUS_REJECTED,
    self::STATUS_SETTLED,
  ];


  public function SetPurposeAttribute($value)
  {
    return $this->attributes['purpose'] = strtoupper($value);
  }

  public function SetNotesAttribute($value)
  {
    return $this->attributes['notes'] = strtoupper($value);
  }

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

  public function authorizer(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'authorizer_id');
  }

  /**
   * Get the company this request belongs to
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  public function companyService(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'company_service_id');
  }

  /**
   * Get the district this request is for
   */
  public function district(): BelongsTo
  {
    return $this->belongsTo(District::class, 'district_id');
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
    return $this->hasOne(HotelReservation::class, 'per_diem_request_id');
  }

  /**
   * Get all expenses for this request
   */
  public function expenses(): HasMany
  {
    return $this->hasMany(PerDiemExpense::class)->orderBy('expense_date', 'desc');
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

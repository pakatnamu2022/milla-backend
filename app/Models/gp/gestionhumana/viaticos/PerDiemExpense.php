<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemExpense extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_per_diem_expense';

  protected $fillable = [
    'per_diem_request_id',
    'expense_type_id',
    'expense_date',
    'receipt_amount',
    'company_amount',
    'employee_amount',
    'receipt_type',
    'receipt_number',
    'receipt_path',
    'notes',
    'is_company_expense',
    'validated',
    'validated_by',
    'validated_at',
    'rejected',
    'rejected_by',
    'rejected_at',
    'rejection_reason',
  ];

  protected $casts = [
    'expense_date' => 'date',
    'receipt_amount' => 'decimal:2',
    'company_amount' => 'decimal:2',
    'employee_amount' => 'decimal:2',
    'is_company_expense' => 'boolean',
    'validated' => 'boolean',
    'validated_at' => 'datetime',
    'rejected' => 'boolean',
    'rejected_at' => 'datetime',
  ];

  /**
   * Get the per diem request this expense belongs to
   */
  public function request(): BelongsTo
  {
    return $this->belongsTo(PerDiemRequest::class, 'per_diem_request_id');
  }

  /**
   * Get the expense type this expense is for
   */
  public function expenseType(): BelongsTo
  {
    return $this->belongsTo(ExpenseType::class);
  }

  /**
   * Get the user who validated this expense
   */
  public function validator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'validated_by');
  }

  /**
   * Get the worker who rejected this expense
   */
  public function rejector(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'rejected_by');
  }

  /**
   * Scope to filter expenses by date
   */
  public function scopeByDate($query, $date)
  {
    return $query->whereDate('expense_date', $date);
  }

  /**
   * Scope to filter expenses by type
   */
  public function scopeByType($query, int $typeId)
  {
    return $query->where('expense_type_id', $typeId);
  }

  /**
   * Scope to filter validated expenses
   */
  public function scopeValidated($query)
  {
    return $query->where('validated', true);
  }

  /**
   * Scope to filter user expenses (not company expenses)
   */
  public function scopeUserExpenses($query)
  {
    return $query->where('is_company_expense', false);
  }

  /**
   * Scope to filter company expenses
   */
  public function scopeCompanyExpenses($query)
  {
    return $query->where('is_company_expense', true);
  }

  /**
   * Check if this expense has a receipt
   */
  public function hasReceipt(): bool
  {
    return $this->receipt_type !== 'no_receipt';
  }
}

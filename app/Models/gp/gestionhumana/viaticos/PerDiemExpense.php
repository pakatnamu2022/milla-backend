<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemExpense extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'per_diem_request_id',
    'expense_type_id',
    'expense_date',
    'concept',
    'receipt_amount',
    'company_amount',
    'employee_amount',
    'receipt_type',
    'receipt_number',
    'receipt_path',
    'notes',
    'validated',
    'validated_by',
    'validated_at',
  ];

  protected $casts = [
    'expense_date' => 'date',
    'receipt_amount' => 'decimal:2',
    'company_amount' => 'decimal:2',
    'employee_amount' => 'decimal:2',
    'validated' => 'boolean',
    'validated_at' => 'datetime',
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
   * Check if this expense has a receipt
   */
  public function hasReceipt(): bool
  {
    return $this->receipt_type !== 'no_receipt';
  }
}

<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestBudget extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_request_budget';

  protected $fillable = [
    'per_diem_request_id',
    'expense_type_id',
    'daily_amount',
    'days',
    'total',
  ];

  protected $casts = [
    'daily_amount' => 'decimal:2',
    'total' => 'decimal:2',
  ];

  const filters = [
    'per_diem_request_id' => '=',
    'expense_type_id' => '=',
  ];

  const sorts = [
    'expense_type_id',
    'total',
    'daily_amount',
    'days',
  ];

  /**
   * Get the per diem request this budget belongs to
   */
  public function request(): BelongsTo
  {
    return $this->belongsTo(PerDiemRequest::class, 'per_diem_request_id');
  }

  /**
   * Get the expense type this budget is for
   */
  public function expenseType(): BelongsTo
  {
    return $this->belongsTo(ExpenseType::class);
  }
}

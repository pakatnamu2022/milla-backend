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

  /**
   * Calculate total spent for this budget
   * Includes expenses that match this budget's expense_type_id directly
   * OR expenses whose expense_type parent_id matches this budget's expense_type_id
   * Excludes rejected expenses and company expenses
   */
  public function calculateSpent(): float
  {
    // Get the request's expenses (should be eager loaded)
    $request = $this->request;

    if (!$request || !$request->relationLoaded('expenses')) {
      // Fallback: if not eager loaded, load expenses
      $request = $this->request()->with('expenses.expenseType')->first();
    }

    if (!$request || !$request->expenses) {
      return 0.0;
    }

    // Filter expenses that apply to this budget
    $relevantExpenses = $request->expenses->filter(function ($expense) {
      // Exclude rejected expenses
      if ($expense->rejected) {
        return false;
      }

      // Exclude company expenses
      if ($expense->is_company_expense) {
        return false;
      }

      // Include if expense type matches directly
      if ($expense->expense_type_id === $this->expense_type_id) {
        return true;
      }

      // Include if expense type's parent matches this budget's type
      if ($expense->expenseType && $expense->expenseType->id !== ExpenseType::BREAKFAST_ID &&
        $expense->expenseType->parent_id === $this->expense_type_id) {
        return true;
      }

      return false;
    });

    // Sum company_amount
    return (float)$relevantExpenses->sum('company_amount');
  }
}

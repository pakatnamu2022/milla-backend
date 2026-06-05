<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollLoan extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_loans';

  protected $fillable = [
    'worker_id',
    'delivery_date',
    'reason',
    'payment_start',
    'payment_days',
    'loan_amount',
    'installments_count',
    'installment_amount',
    'remaining_balance',
    'status',
  ];

  protected $casts = [
    'loan_amount' => 'decimal:2',
    'installment_amount' => 'decimal:2',
    'remaining_balance' => 'decimal:2',
    'payment_days' => 'array',
    'delivery_date' => 'date',
    'payment_start' => 'date',
    'installments_count' => 'integer',
    'status' => 'integer',
  ];

  const filters = [
    'search' => ['reason'],
    'worker_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'worker_id',
    'delivery_date',
    'loan_amount',
    'remaining_balance',
    'created_at',
  ];

  public function setReasonAttribute($value)
  {
    $this->attributes['reason'] = strtoupper($value);
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function extraDiscounts(): HasMany
  {
    return $this->hasMany(PayrollLoanExtraDiscount::class, 'loan_id');
  }

  public function pendingInstallments(): HasMany
  {
    return $this->hasMany(PayrollLoanExtraDiscount::class, 'loan_id')
      ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
      ->where('applied', false)
      ->orderBy('scheduled_date');
  }
}

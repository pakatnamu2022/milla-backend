<?php

namespace App\Models\ap\marketing;

use App\Models\BaseModel;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MktBudget extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_mkt_budgets';

  const TYPE_REGULAR    = 'regular';
  const TYPE_ADDITIONAL = 'additional';

  const TYPE_LABELS = [
    'regular'    => 'Regular',
    'additional' => 'Adicional',
  ];

  const STATUS_DRAFT    = 'draft';
  const STATUS_APPROVED = 'approved';
  const STATUS_CLOSED   = 'closed';

  const STATUS_LABELS = [
    'draft'    => 'Borrador',
    'approved' => 'Aprobado',
    'closed'   => 'Cerrado',
  ];

  protected $fillable = [
    'plan_id',
    'type',
    'period_month',
    'currency_id',
    'amount_estimated',
    'amount_executed',
    'status',
    'notes',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'period_month'     => 'integer',
    'amount_estimated' => 'decimal:2',
    'amount_executed'  => 'decimal:2',
  ];

  const filters = [
    'search'       => ['notes'],
    'plan_id'      => '=',
    'type'         => '=',
    'status'       => '=',
    'period_month' => '=',
    'currency_id'  => '=',
  ];

  const sorts = [
    'type',
    'period_month',
    'status',
    'amount_estimated',
    'amount_executed',
    'created_at',
  ];

  public function plan(): BelongsTo
  {
    return $this->belongsTo(MktPlan::class, 'plan_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function fundings(): HasMany
  {
    return $this->hasMany(MktBudgetFunding::class, 'budget_id');
  }

  public function activities(): HasMany
  {
    return $this->hasMany(MktActivity::class, 'budget_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'created_by');
  }

  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
  }
}

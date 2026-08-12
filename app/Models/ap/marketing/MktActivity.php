<?php

namespace App\Models\ap\marketing;

use App\Models\BaseModel;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MktActivity extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_mkt_activities';

  const STATUS_PLANNED     = 'planned';
  const STATUS_IN_PROGRESS = 'in_progress';
  const STATUS_EXECUTED    = 'executed';
  const STATUS_CANCELLED   = 'cancelled';

  const STATUS_LABELS = [
    'planned'     => 'Planificado',
    'in_progress' => 'En progreso',
    'executed'    => 'Ejecutado',
    'cancelled'   => 'Cancelado',
  ];

  protected $fillable = [
    'budget_id',
    'activity_type',
    'name',
    'description',
    'objective',
    'responsible',
    'channel',
    'start_date',
    'end_date',
    'currency_id',
    'estimated_amount',
    'supplier_id',
    'status',
    'notes',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'start_date'       => 'date',
    'end_date'         => 'date',
    'estimated_amount' => 'decimal:2',
  ];

  const filters = [
    'search'        => ['name', 'activity_type', 'responsible'],
    'budget_id'     => '=',
    'activity_type' => '=',
    'status'        => '=',
    'supplier_id'   => '=',
    'currency_id'   => '=',
  ];

  const sorts = [
    'name',
    'activity_type',
    'start_date',
    'end_date',
    'status',
    'estimated_amount',
    'created_at',
  ];

  public function budget(): BelongsTo
  {
    return $this->belongsTo(MktBudget::class, 'budget_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(\App\Models\ap\comercial\BusinessPartners::class, 'supplier_id');
  }

  public function locations(): HasMany
  {
    return $this->hasMany(MktActivityLocation::class, 'activity_id');
  }

  public function proposals(): HasMany
  {
    return $this->hasMany(MktProposal::class, 'activity_id');
  }

  public function purchaseOrders(): HasMany
  {
    return $this->hasMany(MktPurchaseOrder::class, 'activity_id');
  }

  public function supports(): HasMany
  {
    return $this->hasMany(MktSupport::class, 'activity_id');
  }

  public function kpis(): HasMany
  {
    return $this->hasMany(MktKpi::class, 'activity_id');
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

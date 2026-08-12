<?php

namespace App\Models\ap\marketing;

use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MktPlan extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_mkt_plans';

  const STATUS_DRAFT = 'draft';
  const STATUS_ACTIVE = 'active';
  const STATUS_CLOSED = 'closed';
  const STATUS_CANCELLED = 'cancelled';

  const STATUS_LABELS = [
    'draft'     => 'Borrador',
    'active'    => 'Activo',
    'closed'    => 'Cerrado',
    'cancelled' => 'Cancelado',
  ];

  protected $fillable = [
    'brand_id',
    'name',
    'concept',
    'year',
    'description',
    'status',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'year' => 'integer',
  ];

  const filters = [
    'search'   => ['name', 'concept'],
    'brand_id' => '=',
    'year'     => '=',
    'status'   => '=',
  ];

  const sorts = [
    'name',
    'year',
    'status',
    'created_at',
  ];

  public function brand(): BelongsTo
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id');
  }

  public function budgets(): HasMany
  {
    return $this->hasMany(MktBudget::class, 'plan_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by');
  }
}

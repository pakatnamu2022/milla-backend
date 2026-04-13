<?php

namespace App\Models\ap\postventa\taller;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypePlanningWorkOrder extends Model
{
  use SoftDeletes;

  protected $table = 'type_planning_work_order';

  protected $fillable = [
    'code',
    'description',
    'validate_receipt',
    'validate_labor',
    'type_document',
    'status'
  ];

  protected $casts = [
    'validate_receipt' => 'boolean',
    'validate_labor' => 'boolean',
    'status' => 'boolean'
  ];
  // CONST ID
  const int TYPE_PLANNING_PDI_ID = 6;
  const int TYPE_PLANNING_INST_ACCESORIOS_ID = 10;

  // TYPES DOCUMENT
  const string INTERNA = 'INTERNA';
  const string PAYMENT_RECEIPTS = 'PAYMENT_RECEIPTS';

  const filters = [
    'search' => ['code', 'description'],
    'type_document' => '=',
    'status' => '=',
  ];

  const sorts = [
    'code',
    'description',
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = strtoupper($value);
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = strtoupper($value);
  }
}

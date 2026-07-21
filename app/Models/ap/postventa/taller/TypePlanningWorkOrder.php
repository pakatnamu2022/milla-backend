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
    'notes',
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
  const int TYPE_PLANNING_DERCO_WARRANTY_ID = 9;
  const int TYPE_PLANNING_ODEBRECHT_MAINTENANCE = 13;
  const int TYPE_PLANNING_RECALL_ID = 4;

  // TYPES DOCUMENT
  const string INTERNA_SC = 'INTERNA_SC';
  const string INTERNA_CC = 'INTERNA_CC';
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

  public function setNotesAttribute($value)
  {
    $this->attributes['notes'] = strtoupper($value);
  }
}

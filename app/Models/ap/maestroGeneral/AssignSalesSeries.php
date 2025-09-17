<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignSalesSeries extends Model
{
  use SoftDeletes;

  protected $table = 'assign_sales_series';

  protected $fillable = [
    'series',
    'correlative_start',
    'type_receipt_id',
    'type_operation_id',
    'sede_id',
    'status',
  ];

  const filters = [
    'search' => ['series'],
    'type_receipt_id' => '=',
    'type_operation_id' => '=',
    'sede_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'series',
    'correlative_start',
    'type_receipt_id',
    'type_operation_id',
    'sede_id',
  ];

  public function typeReceipt()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_receipt_id');
  }

  public function typeOperation()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_operation_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}

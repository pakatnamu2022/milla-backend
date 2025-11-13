<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\gp\maestroGeneral\Sede;
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
    'search' => ['series', 'typeReceipt.description', 'typeOperation.description', 'sede.abreviatura'],
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

  const int FACTURA = 799;
  const int BOLETA = 800;
  const int NOTA_CREDITO = 801;
  const int NOTA_DEBITO = 802;
  const int FACTURA_NUBEFACT = 29;
  const int BOLETA_NUBEFACT = 30;
  const int NOTA_CREDITO_NUBEFACT = 31;
  const int NOTA_DEBITO_NUBEFACT = 32;
  const int GUIA_REMISION = 803;

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

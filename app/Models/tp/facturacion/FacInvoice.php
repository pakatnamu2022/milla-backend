<?php

namespace App\Models\tp\facturacion;

use App\Models\BaseModel;

class FacInvoice extends BaseModel
{
  protected $table = 'fac_invoice';
  protected $primaryKey = 'DocumentoId';
  public $incrementing = false;
  protected $keyType = 'string';

  public $timestamps = false;

  protected $fillable = [
    'DocumentoId',
    'FechaVencimiento',
  ];

  protected $casts = [
    'FechaVencimiento' => 'date',
  ];
}

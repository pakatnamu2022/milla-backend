<?php

namespace App\Models\ap\compras;

use App\Models\BaseModel;

class AccountPayable extends BaseModel
{
  protected $table = 'accounts_payable';

  protected $fillable = [
    'documento',
    'proveedor_documento',
    'proveedor_nombre',
    'fecha_documento',
    'fecha_contable',
    'moneda',
    'monto',
    'monto_sin_aplicar',
    'synced_at',
  ];

  protected $casts = [
    'fecha_documento'   => 'date',
    'fecha_contable'    => 'date',
    'monto'             => 'decimal:5',
    'monto_sin_aplicar' => 'decimal:5',
    'synced_at'         => 'datetime',
  ];
}

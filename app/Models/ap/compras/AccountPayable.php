<?php

namespace App\Models\ap\compras;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountPayable extends BaseModel
{
  protected $table = 'accounts_payable';

  protected $fillable = [
    'company',
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

  const filters = [
    'search'               => ['documento', 'proveedor_nombre', 'proveedor_documento'],
    'company'               => '=',
    'moneda'                => '=',
    'proveedor_documento'   => '=',
    'fecha_documento'       => 'date_between',
    'fecha_contable'        => 'date_between',
  ];

  const sorts = [
    'documento',
    'proveedor_nombre',
    'fecha_documento',
    'fecha_contable',
    'monto',
    'monto_sin_aplicar',
    'synced_at',
    'created_at',
  ];

  public function comments(): HasMany
  {
    return $this->hasMany(AccountPayableComment::class, 'accounts_payable_id')->latest();
  }
}

<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApBank extends Model
{
  use softDeletes;

  protected $table = 'ap_bank';

  protected $fillable = [
    'codigo',
    'numero_cuenta',
    'cci',
    'banco_id',
    'moneda_id',
    'sede_id',
    'status',
  ];

  const filters = [
    'search' => ['codigo', 'numero_cuenta', 'cci'],
    'banco_id' => '=',
    'moneda_id' => '=',
    'sede_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'codigo',
    'numero_cuenta',
    'cci',
    'banco_id',
    'moneda_id',
    'sede_id',
    'status',
  ];

  public function banco()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'banco_id');
  }

  public function moneda()
  {
    return $this->belongsTo(TypeCurrency::class, 'moneda_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}

<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\tp\Customer;

class TravelControl extends BaseModel
{
  protected $table = 'op_despacho';
  protected $primaryKey = 'id';


  protected $fillable = [
    'id',
    'conductor_id',
    'tracto_id',
    'carreta_id',
    'idcliente',
    'estado',
    'km_inicio',
    'km_fin',
    'fecha_viaje',
    'observacion_comercial',
    'proxima_prog',
    'ubicacion'
  ];

  protected $casts = [
    'fecha_viaje' => 'datetime',
    'km_inicio' => 'decimal:2',
    'km_fin' => 'decimal:2',
  ];

  //RELACIONES

  public function driver()
  {
    return $this->belongsTo(Worker::class, 'conductor_id', 'id');
  }

  //tracto
  public function tract()
  {
    return $this->belongsTo(Vehicle::class, 'tracto_id', 'id');
  }

  //carreta
  public function cart()
  {
    return $this->belongsTo(Vehicle::class, 'carreta_id', 'id');
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class, 'idcliente', 'id');
  }

  public function statusTrip()
  {
    return $this->belongsTo(DispatchStatus::class, 'estado', 'id');
  }

  public function items()
  {
    return $this->hasMany(DispatchItem::class, 'despacho_id', 'id');
  }

  public function recordsDriver()
  {
    return $this->hasMany(DriverTravelRecord::class, 'dispatch_id', 'id')
      ->orderBy('recorded_at');
  }

  //gastos
  public function expenses()
  {
    return $this->hasMany(TravelExpense::class, 'viaje_id', 'id')
      ->where('concepto_id', 25); // Solo en el caso de CONCEPTO = COMBUSTIBLE
  }

  //Scopes

  public function scopeActivos($query)
  {
    return $query->whereNotIn('estado', [9, 10]);
  }

  public function scopePorEstado($query, $estado)
  {
    if ($estado === 'all') {
      return $query;
    }
    return $query->where('estado', $estado);
  }

  public function scopeBuscar($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('id', 'LIKE', "%{$search}%")
        ->orWhereHas('conductor', function ($q2) use ($search) {
          $q2->where('nombre_completo', 'LIKE', "%{$search}%");
        })
        ->orWhereHas('tracto', function ($q3) use ($search) {
          $q3->where('placa', 'LIKE', "%{$search}%");
        });
    });
  }

  public function getTripNumberAttribute()
  {
    return 'TPV' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
  }

  public function getTotalKmAttribute()
  {
    if ($this->km_inicio && $this->km_fin) {
      return $this->km_fin - $this->km_inicio;
    }
    return 0;
  }

  public function getTotalHoursAttribute()
  {
    $recordStart = $this->recordsDriver()->where('record_type', 'start')->first();
    $recordEnd = $this->recordsDriver()->where('record_type', 'end')->first();

    if ($recordStart && $recordEnd) {
      $inicio = \Carbon\Carbon::parse($recordStart->recorded_at);
      $fin = \Carbon\Carbon::parse($recordEnd->recorded_at);
      return $fin->diffInHours($inicio, true);
    }
    return 0;
  }

  public function getTonnageAttribute()
  {
    return $this->items()->sum('cantidad');
  }

  public function getFuelDataAttribute()
  {
    $gasto = $this->expenses()->first();
    if ($gasto) {
      return [
        'fuelGallons' => $gasto->km_tanqueo,
        'fuelAmount' => $gasto->monto,
        'factorKm' => $gasto->km_tanqueo ? ($gasto->monto / $gasto->km_tanqueo) : 0
      ];
    }
    return null;
  }

  //mapear estados
  public function getMappedStatusAttribute()
  {
    return DispatchStatus::toTripStatus($this->estado);
  }


}

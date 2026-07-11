<?php

namespace App\Models\tp\configuracionComercial\vehiculo;

use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'op_vehiculo';
    public $timestamps = true;

    protected $fillable = [
        'tipo_vehiculo_id',
        'placa',
        'modelo',
        'marca',
        'serie_chasis',
        'motor',
        'num_mtc',
        'tarjeta_circulacion',
        'kilometraje',
        'tercero',
        'capacidad',
        'capacidad_bruta',
        'reserva',
        'capacidad_util',
        'vehiculo_status',
        'status_geotab_km',
        'status_matpel',
        'status_ubicacion',
        'sede_id',
        'write_id',
        'status_deleted',
        'ult_manteniento_realizado',
        'km_mantenimiento',
        'geotab_serial',
        'identificador_geotab',
        'longitud',
        'latitud',
        'isDriving',
        'coordenadas',
        'ubicacion',
        'region',
        'city',
        'country'
    ];

   protected $casts = [
        'id' => 'integer',
        'tipo_vehiculo_id' => 'integer',
        'vehiculo_status' => 'integer',
        'status_geotab_km' => 'integer',
        'status_matpel' => 'integer',
        'status_ubicacion' => 'integer',
        'sede_id' => 'integer',
        'write_id' => 'integer',
        'status_deleted' => 'integer',
        'tercero' => 'string',
        'kilometraje' => 'decimal:2',
        'capacidad_bruta' => 'decimal:2',
        'reserva' => 'decimal:2',
        'capacidad_util' => 'decimal:2',
        'km_mantenimiento' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id', 'id');
    }

    public function tipo_vehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'write_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status_deleted', 1);
    }

    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('placa', 'like', "%{$searchTerm}%")
                ->orWhere('modelo', 'like', "%{$searchTerm}%")
                ->orWhere('marca', 'like', "%{$searchTerm}%")
                ->orWhere('serie_chasis', 'like', "%{$searchTerm}%")
                ->orWhere('motor', 'like', "%{$searchTerm}%")
                ->orWhere('num_mtc', 'like', "%{$searchTerm}%")
                ->orWhereHas('tipo_vehiculo', function ($tvQuery) use ($searchTerm) {
                    $tvQuery->where('descripcion', 'like', "%{$searchTerm}%");
                });
        });
    }
}
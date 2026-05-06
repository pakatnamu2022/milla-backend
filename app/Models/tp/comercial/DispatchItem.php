<?php
namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class DispatchItem extends BaseModel
{
    protected $table = 'op_despacho_item'; 
    protected $primaryKey = 'id';
   protected $fillable = [
        'id',
        'despacho_id',
        'order',
        'idorigen',
        'iddestino',
        'idproducto',
        'observacion',
        'tiempo_estimado',
        'tipo_flete',
        'unidad_medida_id',
        'km_viaje',
        'initial_mileage',
        'final_mileage',
        'total_mileage',
        'total_hours',
        'actual_start',
        'actual_end',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'segment_status',
        'cantidad',
        'precio_unit',
        'total',
        'statusigv',
        'statusflete',
        'created_at',
        'updated_at',
    ];

     protected $casts = [
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'initial_mileage' => 'decimal:2',
        'final_mileage' => 'decimal:2',
        'total_mileage' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'km_viaje' => 'decimal:2',
        'tiempo_estimado' => 'decimal:2',
        'cantidad' => 'decimal:4',
        'precio_unit' => 'decimal:2',
        'total' => 'decimal:2',
        'start_latitude' => 'decimal:8',
        'start_longitude' => 'decimal:8',
        'end_latitude' => 'decimal:8',
        'end_longitude' => 'decimal:8',
    ];

    const SEGMENT_STATUS = [
        'LOCKED' => 'locked',
        'PENDING' => 'pending',
        'IN_PROGRESS' => 'in_progress',
        'COMPLETED' => 'completed',
    ];

    public function product()
    {       
        return $this->hasOne(FacProductSales::class, 'id', 'idproducto');
    }

    public function origin()
    {       
        return $this->hasOne(FacCitySales::class, 'id', 'idorigen');
    }

    public function destination()
    {       
        return $this->hasOne(FacCitySales::class, 'id', 'iddestino');
    }
    public function travel()
    {
        return $this->belongsTo(TravelControl::class, 'despacho_id', 'id');
    }
     public function scopeForTravel($query, $travelId)
    {
        return $query->where('despacho_id', $travelId)->orderBy('order');
    }
    public function scopeActive($query)
    {
        return $query->whereNotIn('segment_status', ['locked', 'completed']);
    }
    public function scopePending($query)
    {
        return $query->where('segment_status', 'pending');
    }
     public function scopeInProgress($query)
    {
        return $query->where('segment_status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('segment_status', 'completed');
    }

     public function calculateTotalMileage(): float
    {
        if ($this->initial_mileage && $this->final_mileage) {
            return $this->final_mileage - $this->initial_mileage;
        }
        return 0;
    }
     public function calculateTotalHours(): float
    {
        if ($this->actual_start && $this->actual_end) {
            return $this->actual_end->diffInHours($this->actual_start, true);
        }
        return 0;
    }
     public function canStart(): bool
    {
        return $this->segment_status === self::SEGMENT_STATUS['PENDING'];
    }
    public function canEnd(): bool
    {
        return $this->segment_status === self::SEGMENT_STATUS['IN_PROGRESS'];
    }
    public function isLocked(): bool
    {
        return $this->segment_status === self::SEGMENT_STATUS['LOCKED'];
    }
     public function isCompleted(): bool
    {
        return $this->segment_status === self::SEGMENT_STATUS['COMPLETED'];
    }
    public function isPending(): bool
    {
        return $this->segment_status === self::SEGMENT_STATUS['PENDING'];
    }
    public function isInProgress(): bool
    {
        return $this->segment_status === self::SEGMENT_STATUS['IN_PROGRESS'];
    }


    public function getSegmentNameAttribute(): string
    {
        $origin = $this->origin?->descripcion ?? 'Origin';
        $destination = $this->destination?->descripcion ?? 'Destination';
        return "Tramo {$this->order}: {$origin} - {$destination}";
    }

    public function getFormattedInitialMileageAttribute(): string
    {
        return $this->initial_mileage ? number_format($this->initial_mileage, 0, '.', ',') . ' km' : '-';
    }
    public function getFormattedFinalMileageAttribute(): string
    {
        return $this->final_mileage ? number_format($this->final_mileage, 0, '.', ',') . ' km' : '-';
    }
    public function getFormattedTotalMileageAttribute(): string
    {
        return $this->total_mileage ? number_format($this->total_mileage, 0, '.', ',') . ' km' : '-';
    }
    public function getFormattedTotalHoursAttribute(): string
    {
        return $this->total_hours ? number_format($this->total_hours, 2) . ' h' : '-';
    }
    public function getHasStartLocationAttribute(): bool
    {
        return !empty($this->start_latitude) && !empty($this->start_longitude);
    }
    public function getHasEndLocationAttribute(): bool
    {
        return !empty($this->end_latitude) && !empty($this->end_longitude);
    }

     public function getStartLocationAttribute(): ?array
    {
        if ($this->has_start_location) {
            return [
                'lat' => $this->start_latitude,
                'lng' => $this->start_longitude,
            ];
        }
        return null;
    }

    public function getEndLocationAttribute(): ?array
    {
        if ($this->has_end_location) {
            return [
                'lat' => $this->end_latitude,
                'lng' => $this->end_longitude,
            ];
        }
        return null;
    }

    public function getStateSpanishTramoAttribute(): string {
        $estados = [
            'locked' => 'Bloqueado',
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed' => 'Completado'
        ];

        return $estados[$this->segment_status] ?? $this->segment_status;
    }
}
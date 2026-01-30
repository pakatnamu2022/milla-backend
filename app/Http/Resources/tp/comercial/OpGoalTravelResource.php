<?php

namespace App\Http\Resources\tp\comercial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpGoalTravelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $fecha = $this->fecha ? Carbon::parse($this->fecha) : null;
        return [
            //RESOURCE
            'id' => $this->id,
            'date' => $fecha ? $fecha->format('Y-m-d') : null,
            'year' => $fecha ? $fecha->year : null,
            'month' => $fecha ? $fecha->month : null,
            'total' => (float) $this->total,
            'driver_goal' => (float) $this->meta_conductor,
            'vehicle_goal' => (float) $this->meta_vehiculo,
            'total_units' => (int) $this->total_unidades,
            'status_deleted' => (int) $this->status_deleted
        ];
    }

    public static function collection($resource)
    {
        $collection = parent::collection($resource);

        $collection->additional([
            'available_years' => []
        ]);

        return $collection;
    }
}

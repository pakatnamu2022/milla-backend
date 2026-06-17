<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkScheduleResource extends JsonResource
{
  public function toArray($request): array
  {
    return [
      'id'        => $this->id,
      'name'      => $this->name,
      'checkin'   => $this->checkin,
      'lunch_out' => $this->lunch_out,
      'lunch_in'  => $this->lunch_in,
      'checkout'  => $this->checkout,
      'details'   => $this->whenLoaded('details', fn() =>
        $this->details->map(fn($d) => [
          'id'          => $d->id,
          'day_of_week' => $d->day_of_week,
          'checkin'     => $d->checkin,
          'lunch_out'   => $d->lunch_out,
          'lunch_in'    => $d->lunch_in,
          'checkout'    => $d->checkout,
        ])
      ),
    ];
  }
}

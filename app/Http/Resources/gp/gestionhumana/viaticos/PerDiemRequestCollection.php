<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PerDiemRequestCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => PerDiemRequestResource::collection($this->collection),
        ];
    }
}

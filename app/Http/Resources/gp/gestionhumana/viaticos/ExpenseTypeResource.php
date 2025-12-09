<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fullName = $this->name;
        if ($this->parent) {
            $fullName = $this->parent->name . ' - ' . $this->name;
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'full_name' => $fullName,
            'description' => $this->description,
            'requires_receipt' => (bool) $this->requires_receipt,
            'active' => (bool) $this->active,
            'order' => $this->order,

            // Relations
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? [
                    'id' => $this->parent->id,
                    'code' => $this->parent->code,
                    'name' => $this->parent->name,
                ] : null;
            }),

            'children_count' => $this->when(isset($this->children_count), $this->children_count),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

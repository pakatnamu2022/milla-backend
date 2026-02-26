<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateApReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'items_receiving' => 'nullable|array',
      'items_receiving.*' => 'nullable|integer|exists:ap_delivery_receiving_checklist,id',
      'shipping_guide_id' => 'required|integer|exists:shipping_guides,id',
      'note' => 'nullable|string|max:250',
      'kilometers' => 'required|numeric|min:0',
      'photo_front' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'photo_back' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'photo_left' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'photo_right' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'general_observations' => 'nullable|string|max:1000',
      'damages' => 'nullable|array',
      'damages.*.damage_type' => 'required_with:damages|string|max:100',
      'damages.*.x_coordinate' => 'nullable|numeric',
      'damages.*.y_coordinate' => 'nullable|numeric',
      'damages.*.description' => 'nullable|string',
      'damages.*.photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
    ];
  }

  public function attributes()
  {
    return [
      'items_receiving' => 'items de recepción',
      'shipping_guide_id' => 'guía de remisión',
      'note' => 'nota',
      'kilometers' => 'kilómetros',
      'photo_front' => 'foto frontal',
      'photo_back' => 'foto trasera',
      'photo_left' => 'foto lateral izquierda',
      'photo_right' => 'foto lateral derecha',
      'general_observations' => 'observaciones generales',
      'damages' => 'daños',
      'damages.*.damage_type' => 'tipo de daño',
      'damages.*.x_coordinate' => 'coordenada X del daño',
      'damages.*.y_coordinate' => 'coordenada Y del daño',
      'damages.*.description' => 'descripción del daño',
      'damages.*.photo' => 'foto del daño',
    ];
  }
}

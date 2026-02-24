<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateApReceivingChecklistRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'items_receiving'           => 'nullable|array',
      'items_receiving.*'         => 'nullable|integer',
      'shipping_guide_id'         => 'required|integer|exists:shipping_guides,id',
      'note'                      => 'nullable|string|max:250',
      'kilometers'                => 'required|numeric|min:0',
      'photo_front'               => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'photo_back'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'photo_left'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'photo_right'               => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
      'general_observations'      => 'nullable|string|max:1000',
      'damages'                   => 'nullable|array',
      'damages.*.damage_type'     => 'required_with:damages|string|max:100',
      'damages.*.x_coordinate'    => 'nullable|numeric',
      'damages.*.y_coordinate'    => 'nullable|numeric',
      'damages.*.description'     => 'nullable|string',
      'damages.*.photo'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
    ];
  }

  public function messages(): array
  {
    return [
      'items_receiving.array' => 'Los items de recepción deben ser un objeto.',
      'items_receiving.*.integer' => 'Cada cantidad debe ser un número entero.',
      'shipping_guide_id.required' => 'El ID de la guía de envío es obligatorio.',
      'shipping_guide_id.integer' => 'El ID de la guía de envío debe ser un entero válido.',
      'shipping_guide_id.exists' => 'El ID de la guía de envío no existe.',
      'note.string' => 'La nota debe ser una cadena de texto.',
      'note.max' => 'La nota no puede exceder los 250 caracteres.',
      'kilometers.required' => 'Los kilómetros son obligatorios.',
      'kilometers.numeric' => 'Los kilómetros deben ser un número.',
      'kilometers.min' => 'Los kilómetros no pueden ser negativos.',
    ];
  }
}

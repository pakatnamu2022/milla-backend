<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApDeliveryReceivingChecklistRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'descripcion' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_delivery_receiving_checklist', 'descripcion')->whereNull('deleted_at'),],
      'tipo' => [
        'required',
        'string',
        'max:50',
        Rule::unique('ap_delivery_receiving_checklist', 'tipo')->whereNull('deleted_at'),],
      'categoria' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_delivery_receiving_checklist', 'categoria')->whereNull('deleted_at'),],
    ];
  }

  public function messages(): array
  {
    return [
      'descripcion.required' => 'El campo descripción es obligatorio.',
      'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
      'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'El campo descripción ya existe.',
      'tipo.required' => 'El campo tipo es obligatorio.',
      'tipo.string' => 'El campo tipo debe ser una cadena de texto.',
      'tipo.max' => 'El campo tipo no debe exceder los 255 caracteres.',
      'tipo.unique' => 'El campo tipo ya existe.',
      'categoria.required' => 'El campo categoria es obligatorio.',
      'categoria.string' => 'El campo categoria debe ser una cadena de texto.',
      'categoria.max' => 'El campo categoria no debe exceder los 255 caracteres.',
      'categoria.unique' => 'El campo categoria ya existe.',
    ];
  }
}

<?php

namespace App\Http\Requests\Position;

use App\Http\Requests\StoreRequest;

class StorePositionRequest extends Storerequest
{
  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'descripcion' => 'nullable|string',
      'area_id' => 'nullable|integer|exists:rrhh_area,id',
      'hierarchical_category_id' => 'nullable|integer|exists:gh_hierarchical_category,id',
      'cargo_id' => 'nullable|integer|exists:rrhh_cargo,id',
      'ntrabajadores' => 'nullable|integer|min:0',
      'banda_salarial_min' => 'nullable|numeric|min:0',
      'banda_salarial_media' => 'nullable|numeric|min:0',
      'banda_salarial_max' => 'nullable|numeric|min:0',
      'tipo_onboarding_id' => 'nullable|integer|exists:tipo_onboarding,id',
      'plazo_proceso_seleccion' => 'nullable|integer|min:0',
      'presupuesto' => 'nullable|numeric|min:0',
      'mof_adjunto' => 'required|file|mimes:pdf,doc,docx|max:5120',
      'files' => 'nullable|array|max:6',
      'files.*' => 'file|mimes:pdf,doc,docx|max:5120',
    ];
  }
}

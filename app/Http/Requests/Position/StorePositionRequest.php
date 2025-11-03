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
      'area_id' => 'nullable|integer|exists:areas,id',
      'ntrabajadores' => 'nullable|integer|min:0',
      'banda_salarial_min' => 'nullable|numeric|min:0',
      'banda_salarial_media' => 'nullable|numeric|min:0',
      'banda_salarial_max' => 'nullable|numeric|min:0',
      'cargo_id' => 'nullable|integer|exists:cargos,id',
      'tipo_onboarding_id' => 'nullable|integer|exists:tipo_onboarding,id',
      'plazo_proceso_seleccion' => 'nullable|integer|min:0',
      'presupuesto' => 'nullable|numeric|min:0',
      'mof_adjunto' => 'required|file|mimes:pdf,doc,docx|max:2048',
      'fileadic1' => 'required|file|mimes:pdf,doc,docx|max:2048',
      'fileadic2' => 'required|file|mimes:pdf,doc,docx|max:2048',
      'fileadic3' => 'required|file|mimes:pdf,doc,docx|max:2048',
      'fileadic4' => 'required|file|mimes:pdf,doc,docx|max:2048',
      'fileadic5' => 'required|file|mimes:pdf,doc,docx|max:2048',
      'fileadic6' => 'required|file|mimes:pdf,doc,docx|max:2048',
    ];
  }
}

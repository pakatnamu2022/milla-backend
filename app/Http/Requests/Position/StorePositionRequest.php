<?php

namespace App\Http\Requests\Position;

use App\Http\Requests\StoreRequest;

class StorePositionRequest extends Storerequest
{
  protected function prepareForValidation()
  {
    $input = $this->all();

    // Remover mof_adjunto si no es un archivo válido
    if (isset($input['mof_adjunto']) && !$this->file('mof_adjunto')) {
      unset($input['mof_adjunto']);
    }

    // Limpiar el array de files, removiendo elementos que no sean archivos válidos
    if (isset($input['files']) && is_array($input['files'])) {
      $input['files'] = array_filter($input['files'], function($file) {
        return $file instanceof \Illuminate\Http\UploadedFile;
      });

      // Si el array quedó vacío, removerlo completamente
      if (empty($input['files'])) {
        unset($input['files']);
      }
    } elseif (isset($input['files'])) {
      // Si files no es un array, removerlo
      unset($input['files']);
    }

    $this->replace($input);
  }

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
      'mof_adjunto' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
      'files' => 'nullable|array|max:6',
      'files.*' => 'file|mimes:pdf,doc,docx|max:5120',
    ];
  }
}

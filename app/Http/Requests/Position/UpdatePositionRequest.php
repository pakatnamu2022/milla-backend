<?php

namespace App\Http\Requests\Position;

use App\Http\Requests\StoreRequest;

class UpdatePositionRequest extends StoreRequest
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
      $input['files'] = array_filter($input['files'], function ($file) {
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
      'ntrabajadores' => 'nullable|integer|min:0',
      'banda_salarial_min' => 'nullable|numeric|min:0',
      'banda_salarial_media' => 'nullable|numeric|min:0',
      'banda_salarial_max' => 'nullable|numeric|min:0',
      'cargo_id' => 'nullable|integer|exists:rrhh_cargo,id',
      'tipo_onboarding_id' => 'nullable|integer|exists:rrhh_tipo_contingencia,id',
      'plazo_proceso_seleccion' => 'nullable|integer|min:0',
      'presupuesto' => 'nullable|numeric|min:0',
      'mof_adjunto' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
      'files' => 'nullable|array|max:6',
      'files.*' => 'file|mimes:pdf,doc,docx|max:5120',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El campo nombre es obligatorio.',
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 255 caracteres.',

      'descripcion.string' => 'La descripción debe ser una cadena de texto.',

      'area_id.integer' => 'El campo área debe ser un número entero.',
      'area_id.exists' => 'El área seleccionada no es válida.',

      'hierarchical_category_id.integer' => 'El campo categoría jerárquica debe ser un número entero.',
      'hierarchical_category_id.exists' => 'La categoría jerárquica seleccionada no es válida.',

      'ntrabajadores.integer' => 'El número de trabajadores debe ser un número entero.',
      'ntrabajadores.min' => 'El número de trabajadores no puede ser negativo.',

      'banda_salarial_min.numeric' => 'La banda salarial mínima debe ser un número.',
      'banda_salarial_min.min' => 'La banda salarial mínima no puede ser negativa.',

      'banda_salarial_media.numeric' => 'La banda salarial media debe ser un número.',
      'banda_salarial_media.min' => 'La banda salarial media no puede ser negativa.',

      'banda_salarial_max.numeric' => 'La banda salarial máxima debe ser un número.',
      'banda_salarial_max.min' => 'La banda salarial máxima no puede ser negativa.',

      'cargo_id.integer' => 'El campo cargo de liderazgo debe ser un número entero.',
      'cargo_id.exists' => 'El cargo de liderazgo seleccionado no es válido.',

      'tipo_onboarding_id.integer' => 'El campo tipo de onboarding debe ser un número entero.',
      'tipo_onboarding_id.exists' => 'El tipo de onboarding seleccionado no es válido.',

      'plazo_proceso_seleccion.integer' => 'El plazo del proceso de selección debe ser un número entero.',
      'plazo_proceso_seleccion.min' => 'El plazo del proceso de selección no puede ser negativo.',

      'presupuesto.numeric' => 'El presupuesto debe ser un número.',
      'presupuesto.min' => 'El presupuesto no puede ser negativo.',

      'mof_adjunto.file' => 'El mof adjunto debe ser un archivo válido.',
      'mof_adjunto.mimes' => 'El mof adjunto debe ser un archivo de tipo: pdf, doc, docx.',
      'mof_adjunto.max' => 'El mof adjunto no debe exceder los 5 MB.',
    ];
  }
}

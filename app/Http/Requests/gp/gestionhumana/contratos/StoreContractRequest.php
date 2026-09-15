<?php

namespace App\Http\Requests\gp\gestionhumana\contratos;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'empleado_id'              => 'required|integer|exists:rrhh_persona,id',
      'tipo_contrato_id'         => 'required|integer|exists:rrhh_tipo_contrato,id',
      'template_contrato_id'     => 'required|integer|exists:rrhh_plantilla_contrato,id',
      'sede_id'                  => 'required|integer|exists:config_sede,id',
      'cargo_id'                 => 'required|integer|exists:rrhh_cargo,id',
      'sueldo'                   => 'required|numeric|min:0',
      'fecha_inicio_actividades' => 'nullable|date_format:Y-m-d',
      'fecha_inicio_contrato'    => 'required|date_format:Y-m-d',
      'fecha_fin_contrato'       => 'nullable|date_format:Y-m-d|after_or_equal:fecha_inicio_contrato',
      'observacion'              => 'nullable|string|max:500',
      'grupo_contrato'           => 'nullable|string|max:100',
      'contrato_principal'       => 'nullable|integer|exists:rrhh_contrato,id',
      'convenio'                 => 'nullable|string|max:50',
      'firmante_id'              => 'nullable|integer|exists:rrhh_firmante,id',
      'firmante_sec_id'          => 'nullable|integer|exists:rrhh_firmante,id',
      'lote'                     => 'nullable|string|max:150',
    ];
  }
}

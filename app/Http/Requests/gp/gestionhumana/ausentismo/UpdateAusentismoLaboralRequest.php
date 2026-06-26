<?php

namespace App\Http\Requests\gp\gestionhumana\ausentismo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAusentismoLaboralRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'empleado_id'       => ['sometimes', 'integer', 'exists:rrhh_persona,id'],
      'fecha_inicial'     => ['sometimes', 'date_format:Y-m-d'],
      'fecha_fin'         => ['sometimes', 'date_format:Y-m-d', 'gte:fecha_inicial'],
      'id_tipo_descanso'  => ['sometimes', 'integer', 'exists:rrhh_tipo_descanso,id'],
      'motivo'            => ['nullable', 'string', 'max:255'],
      'tipo_contingencia' => ['nullable', 'integer'],
      'fecha_contingencia'=> ['nullable', 'date_format:Y-m-d'],
      'atencion'          => ['nullable', 'string', 'max:50'],
      'diagnostico'       => ['nullable', 'string'],
      'citt'              => ['nullable', 'string', 'max:250'],
      'centro_atencion'   => ['nullable', 'string', 'max:250'],
      'sede_id'           => ['nullable', 'integer', 'exists:config_sede,id'],
      'area_id'           => ['nullable', 'integer', 'exists:rrhh_area,id'],
      'estado'            => ['nullable', 'string', 'max:45'],
      'estado_aprobacion' => ['nullable', 'integer'],
      'fecha_aprobacion'  => ['nullable', 'date_format:Y-m-d H:i:s'],
    ];
  }
}

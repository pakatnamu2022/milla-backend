<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicantRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Campos de identidad/contacto/educacion del postulante. La sede/area/cargo/
   * centro de costo se heredan del proceso de postulacion, no se envian.
   */
  public static function personFields(bool $required = false): array
  {
    $name = $required ? 'required' : 'nullable';

    return [
      'proceso_postulacion_id'  => ($required ? 'required' : 'sometimes|required') . '|integer|exists:rrhh_proceso_postulacion,id',
      'nombre_completo'         => "$name|string|max:255",
      'vat'                     => "$name|string|max:25",
      'vat2'                    => 'nullable|string|max:25',
      'vat3'                    => 'nullable|string|max:25',
      'sexo'                    => 'nullable|string|max:10',
      'fecha_nacimiento'        => 'nullable|date_format:Y-m-d',
      'estado_civil'            => 'nullable|string|max:100',
      'fecha_estado_civil'      => 'nullable|date_format:Y-m-d',
      'nacionalidad'            => 'nullable|string|max:50',
      'lugar_nacimiento'        => 'nullable|string|max:100',
      'ubigeo'                  => 'nullable|string|max:200',
      'email'                   => 'nullable|email|max:255',
      'cel_personal'            => 'nullable|string|max:150',
      'cel_refencia'            => 'nullable|string|max:150',
      'tel_referencia_2'        => 'nullable|string|max:150',
      'direccion_principal'     => 'nullable|string|max:500',
      'direccion_ref'           => 'nullable|string',
      'distrito'                => 'nullable|string|max:255',
      'provincia'               => 'nullable|string|max:255',
      'departamento'            => 'nullable|string|max:100',
      'brevete_matpel'          => 'nullable|string|max:50',
      'clase_brev'              => 'nullable|string|max:50',
      'categoria_brev'          => 'nullable|string|max:50',
      'estudios_id'             => 'nullable|integer',
      'escolaridad'             => 'nullable',
      'estado_estudios_prim'    => 'nullable|string|max:100',
      'centro_estudios_prim'    => 'nullable|string|max:250',
      'estado_estudios_sec'     => 'nullable|string|max:100',
      'centro_estudios_sec'     => 'nullable|string|max:250',
      'institucion_tec_univ'    => 'nullable|string|max:100',
      'carrera_tec_univ'        => 'nullable|string|max:100',
      'ciudad_dep_est_tec_univ' => 'nullable|string|max:100',
      'nivel_alcanzado'         => 'nullable|string|max:100',
      'ciclo_estudios'          => 'nullable|string|max:100',
      'anos_curso'              => 'nullable|string|max:100',
      'grado_obtenido'          => 'nullable|string|max:100',
      'file_cv'                 => 'nullable|file|max:10240',
      'file_foto'               => 'nullable|file|image|max:10240',
    ];
  }

  public function rules(): array
  {
    return self::personFields(required: true);
  }
}

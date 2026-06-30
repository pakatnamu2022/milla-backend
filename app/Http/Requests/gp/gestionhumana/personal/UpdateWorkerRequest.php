<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use App\Http\Requests\StoreRequest;

class UpdateWorkerRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'vat'                    => ['sometimes', 'string', 'max:20'],
      'nombre_completo'        => ['sometimes', 'string', 'max:255'],
      'sede_id'                => ['sometimes', 'integer', 'exists:config_sede,id'],
      'jefe_id'                => ['sometimes', 'nullable', 'integer', 'exists:rrhh_persona,id'],
      'second_boss_id'         => ['sometimes', 'nullable', 'integer', 'exists:rrhh_persona,id'],
      'supervisor_id'          => ['sometimes', 'nullable', 'integer', 'exists:rrhh_persona,id'],
      'fecha_inicio'           => ['sometimes', 'nullable', 'date_format:Y-m-d'],
      'email'                  => ['sometimes', 'nullable', 'email', 'max:255'],
      'email2'                 => ['sometimes', 'nullable', 'email', 'max:255'],
      'email3'                 => ['sometimes', 'nullable', 'email', 'max:255'],
      'tel_referencia_3'       => ['sometimes', 'nullable', 'string', 'max:50'],
      'sueldo'                 => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'horas_jornada'          => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'work_schedule_id'       => ['sometimes', 'nullable', 'integer', 'exists:work_schedules,id'],
      'asignacion'             => ['sometimes', 'nullable', 'string', 'max:50'],
      'status_id'              => ['sometimes', 'integer', 'exists:config_status,id'],
      'no_attendance_required' => ['sometimes', 'boolean'],
      'worker_signature'       => ['sometimes', 'nullable', 'string'],
    ];
  }

  public function attributes()
  {
    return [
      'vat'                    => 'dni',
      'nombre'                 => 'nombre',
      'sede_id'                => 'sede',
      'jefe_id'                => 'jefe',
      'second_boss_id'         => 'segundo jefe',
      'supervisor_id'          => 'evaluador',
      'fecha_inicio'           => 'fecha inicio',
      'email'                  => 'email',
      'email2'                 => 'email corporativo',
      'email3'                 => 'email extra',
      'tel_referencia_3'       => 'teléfono referencia 3',
      'sueldo'                 => 'sueldo',
      'horas_jornada'          => 'horas jornada',
      'work_schedule_id'       => 'horario',
      'asignacion'             => 'asignacion',
      'status'                 => 'estado',
      'no_attendance_required' => 'no requiere asistencia',
      'worker_signature'       => 'firma trabajador',
    ];
  }
}

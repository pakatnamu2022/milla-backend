<?php

namespace App\Http\Requests\gp\gestionhumana\asistencias;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkAttendanceSyncRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'person_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
      'dates' => ['required', 'array', 'min:1'],
      'dates.*.date' => ['required', 'date_format:Y-m-d'],
      'dates.*.marks' => ['required', 'array', 'min:1', 'max:4'],
      'dates.*.marks.*.time' => ['required', 'date_format:H:i:s'],
      'dates.*.marks.*.mark_type' => ['required', 'string', 'in:check_in,lunch_out,lunch_in,check_out'],
    ];
  }

  /**
   * Configure the validator instance.
   */
  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      $dates = $this->input('dates', []);

      foreach ($dates as $dateIndex => $dateItem) {
        $marks = $dateItem['marks'] ?? [];
        $markTypes = [];
        $times = [];

        // Validar que no haya mark_types duplicados en la misma fecha
        foreach ($marks as $markIndex => $mark) {
          $markType = $mark['mark_type'] ?? null;
          $time = $mark['time'] ?? null;

          if ($markType && isset($markTypes[$markType])) {
            $validator->errors()->add(
              "dates.{$dateIndex}.marks.{$markIndex}.mark_type",
              "No puede haber más de un {$markType} para la misma fecha."
            );
          }

          if ($markType) {
            $markTypes[$markType] = $time;
          }

          if ($time) {
            $times[] = $time;
          }
        }

        // Validar orden lógico de los horarios
        if (isset($markTypes['check_in']) && isset($markTypes['lunch_out'])) {
          if ($markTypes['check_in'] >= $markTypes['lunch_out']) {
            $validator->errors()->add(
              "dates.{$dateIndex}.marks",
              "El horario de entrada (check_in) debe ser anterior a la salida de almuerzo (lunch_out)."
            );
          }
        }

        if (isset($markTypes['lunch_out']) && isset($markTypes['lunch_in'])) {
          if ($markTypes['lunch_out'] >= $markTypes['lunch_in']) {
            $validator->errors()->add(
              "dates.{$dateIndex}.marks",
              "El horario de salida de almuerzo (lunch_out) debe ser anterior al retorno de almuerzo (lunch_in)."
            );
          }
        }

        if (isset($markTypes['lunch_in']) && isset($markTypes['check_out'])) {
          if ($markTypes['lunch_in'] >= $markTypes['check_out']) {
            $validator->errors()->add(
              "dates.{$dateIndex}.marks",
              "El horario de retorno de almuerzo (lunch_in) debe ser anterior a la salida (check_out)."
            );
          }
        }

        if (isset($markTypes['check_in']) && isset($markTypes['check_out'])) {
          if ($markTypes['check_in'] >= $markTypes['check_out']) {
            $validator->errors()->add(
              "dates.{$dateIndex}.marks",
              "El horario de entrada (check_in) debe ser anterior a la salida (check_out)."
            );
          }
        }
      }
    });
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'person_id.required' => 'El ID de persona es requerido.',
      'person_id.integer' => 'El ID de persona debe ser un número entero.',
      'person_id.exists' => 'La persona con ID :input no existe en el sistema.',

      'sede_id.required' => 'El campo sede es requerido.',
      'sede_id.integer' => 'El ID de sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe en el sistema.',

      'dates.required' => 'Debe enviar al menos una fecha.',
      'dates.array' => 'El campo dates debe ser un arreglo.',
      'dates.min' => 'Debe enviar al menos una fecha.',

      'dates.*.date.required' => 'La fecha es requerida.',
      'dates.*.date.date_format' => 'La fecha debe tener el formato Y-m-d (ejemplo: 2026-08-27).',

      'dates.*.marks.required' => 'Debe enviar al menos una marcación.',
      'dates.*.marks.array' => 'El campo marks debe ser un arreglo.',
      'dates.*.marks.min' => 'Debe enviar al menos una marcación.',
      'dates.*.marks.max' => 'No puede enviar más de 4 marcaciones por fecha.',

      'dates.*.marks.*.time.required' => 'El horario es requerido.',
      'dates.*.marks.*.time.date_format' => 'El horario debe tener el formato H:i:s (ejemplo: 08:00:00).',

      'dates.*.marks.*.mark_type.required' => 'El tipo de marcación es requerido.',
      'dates.*.marks.*.mark_type.string' => 'El tipo de marcación debe ser un texto.',
      'dates.*.marks.*.mark_type.in' => 'El tipo de marcación debe ser uno de los siguientes: check_in, lunch_out, lunch_in, check_out.',
    ];
  }

  /**
   * Get custom attributes for validator errors.
   *
   * @return array<string, string>
   */
  public function attributes(): array
  {
    return [
      'person_id' => 'persona',
      'sede_id' => 'sede',
      'dates' => 'fechas',
      'dates.*.date' => 'fecha',
      'dates.*.marks' => 'marcaciones',
      'dates.*.marks.*.time' => 'horario',
      'dates.*.marks.*.mark_type' => 'tipo de marcación',
    ];
  }
}

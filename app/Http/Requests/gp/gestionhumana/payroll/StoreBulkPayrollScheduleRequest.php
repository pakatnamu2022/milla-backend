<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\personal\Worker;

class StoreBulkPayrollScheduleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'period_id' => ['required', 'integer', 'exists:gh_payroll_periods,id'],
      'schedules' => ['required', 'array', 'min:1'],
      'schedules.*.worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'schedules.*.code' => ['required', 'string', 'max:50'],
      'schedules.*.work_date' => ['required', 'date'],
      'schedules.*.notes' => ['nullable', 'string', 'max:255'],
      'schedules.*.status' => ['nullable', 'string', 'in:' . implode(',', PayrollSchedule::STATUSES)],
    ];
  }

  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      $schedules = $this->input('schedules', []);

      // Cargar en memoria los códigos permitidos por worker para evitar N+1
      $workerIds = collect($schedules)->pluck('worker_id')->unique()->filter()->values()->toArray();
      $workers = Worker::whereIn('id', $workerIds)->with('allowedAttendanceRules')->get()->keyBy('id');

      foreach ($schedules as $index => $schedule) {
        $workerId = $schedule['worker_id'] ?? null;
        $code = $schedule['code'] ?? null;

        if (!$workerId || !$code) {
          continue;
        }

        $worker = $workers->get($workerId);
        if (!$worker) {
          continue;
        }

        $allowedCodes = $worker->allowedAttendanceRules->pluck('code')->toArray();
        if (!empty($allowedCodes) && !in_array($code, $allowedCodes)) {
          $validator->errors()->add(
            "schedules.{$index}.code",
            "El código '{$code}' no está permitido para el trabajador ID {$workerId}"
          );
        }
      }
    });
  }

  public function attributes(): array
  {
    return [
      'period_id' => 'period',
      'schedules' => 'schedules',
      'schedules.*.worker_id' => 'worker',
      'schedules.*.code' => 'attendance code',
      'schedules.*.work_date' => 'work date',
      'schedules.*.notes' => 'notes',
      'schedules.*.status' => 'status',
    ];
  }
}

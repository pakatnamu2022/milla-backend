<?php

namespace App\Http\Services\gp\gestionhumana\asistencias;

use App\Models\gp\gestionhumana\asistencias\AttendanceSync;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para calcular horas trabajadas desde AttendanceSync
 */
class WorkedHoursCalculatorService
{
  /**
   * Calcula las horas trabajadas por un trabajador en un rango de fechas
   *
   * NUEVA LÓGICA:
   * - Horas estándar: 8 horas × cantidad de días con check_in
   * - Horas reales: (check_out - check_in) - tiempo_almuerzo_PROGRAMADO (del work_schedule)
   *
   * @param int $workerId
   * @param string $startDate Format: Y-m-d
   * @param string $endDate Format: Y-m-d
   * @return array
   */
  public function calculateWorkedHours(int $workerId, string $startDate, string $endDate): array
  {
    // Obtener DNI y work_schedule_id del trabajador
    $worker = DB::table('rrhh_persona')
      ->where('id', $workerId)
      ->select('vat', 'work_schedule_id')
      ->first();

    if (!$worker) {
      return [
        'worker_id' => $workerId,
        'date_from' => $startDate,
        'date_to' => $endDate,
        'has_work_schedule' => false,
        'error' => 'Trabajador no encontrado',
        'standard_hours' => 0,
        'real_hours' => 0,
        'total_days_with_checkin' => 0,
        'total_days_with_checkout' => 0,
        'daily_details' => [],
        'missing_marks_summary' => [
          'total_days_with_missing_marks' => 0,
          'days_with_missing_marks' => [],
        ],
      ];
    }

    // Validar que tenga work_schedule_id asignado
    if (!$worker->work_schedule_id) {
      return [
        'worker_id' => $workerId,
        'date_from' => $startDate,
        'date_to' => $endDate,
        'has_work_schedule' => false,
        'error' => 'El trabajador debe tener un horario (work_schedule_id) asignado',
        'standard_hours' => 0,
        'real_hours' => 0,
        'total_days_with_checkin' => 0,
        'total_days_with_checkout' => 0,
        'daily_details' => [],
        'missing_marks_summary' => [
          'total_days_with_missing_marks' => 0,
          'days_with_missing_marks' => [],
        ],
      ];
    }

    // Buscar registros por person_id O emp_code (DNI)
    $attendanceRecords = AttendanceSync::where(function ($query) use ($workerId, $worker) {
      $query->where('person_id', $workerId);
      if ($worker->vat) {
        $query->orWhere('emp_code', $worker->vat);
      }
    })
      ->whereBetween('date', [$startDate, $endDate])
      ->orderBy('date')
      ->orderBy('time')
      ->get();

    if ($attendanceRecords->isEmpty()) {
      return [
        'worker_id' => $workerId,
        'date_from' => $startDate,
        'date_to' => $endDate,
        'has_work_schedule' => true,
        'standard_hours' => 0,
        'real_hours' => 0,
        'total_days_with_checkin' => 0,
        'total_days_with_checkout' => 0,
        'daily_details' => [],
        'missing_marks_summary' => [
          'total_days_with_missing_marks' => 0,
          'days_with_missing_marks' => [],
        ],
      ];
    }

    // Agrupar por fecha y normalizar formato
    $attendanceByDate = $attendanceRecords->groupBy(function ($record) {
      return Carbon::parse($record->date)->format('Y-m-d');
    });

    $dailyDetails = [];
    $totalStandardHours = 0;
    $totalRealHours = 0;
    $totalDaysWithCheckin = 0;
    $totalDaysWithCheckout = 0;
    $daysWithMissingMarks = [];

    foreach ($attendanceByDate as $date => $dayRecords) {
      $dayDetail = $this->calculateDayWorkedHours($date, $dayRecords, $workerId);

      $dailyDetails[] = $dayDetail;

      // Horas estándar: Si tiene check_in → +8 horas
      if ($dayDetail['has_check_in']) {
        $totalStandardHours += 8;
        $totalDaysWithCheckin++;
      }

      // Horas reales: Solo si tiene check_in Y check_out
      if ($dayDetail['real_hours'] !== null) {
        $totalRealHours += $dayDetail['real_hours'];
        $totalDaysWithCheckout++;
      }

      if (!$dayDetail['has_all_required_marks']) {
        $daysWithMissingMarks[] = [
          'date' => $date,
          'missing_marks' => $dayDetail['missing_marks'],
          'marks_count' => $dayDetail['marks_count'],
        ];
      }
    }

    return [
      'worker_id' => $workerId,
      'date_from' => $startDate,
      'date_to' => $endDate,
      'has_work_schedule' => true,
      'standard_hours' => round($totalStandardHours, 2),
      'real_hours' => round($totalRealHours, 2),
      'total_days_with_checkin' => $totalDaysWithCheckin,
      'total_days_with_checkout' => $totalDaysWithCheckout,
      'daily_details' => $dailyDetails,
      'missing_marks_summary' => [
        'total_days_with_missing_marks' => count($daysWithMissingMarks),
        'days_with_missing_marks' => $daysWithMissingMarks,
      ],
    ];
  }

  /**
   * Calcula las horas trabajadas en un día específico
   *
   * @param string $date
   * @param Collection $dayRecords
   * @param int $workerId
   * @return array
   */
  private function calculateDayWorkedHours(string $date, Collection $dayRecords, int $workerId): array
  {
    $isSaturday = Carbon::parse($date)->dayOfWeek === 6;

    // Obtener las marcaciones del día
    $marks = $this->extractMarksFromRecords($dayRecords);

    // Obtener horario programado del trabajador
    $schedule = $this->getWorkerSchedule($workerId, $date);

    // Calcular horas reales: (check_out - check_in) - tiempo_almuerzo_PROGRAMADO
    $realHours = null;
    if ($marks['check_in'] && $marks['check_out']) {
      $realHours = $this->calculateRealHoursFromMarks($marks, $schedule, $isSaturday);
    }

    // Validar marcaciones obligatorias
    $requiredMarks = $isSaturday ? ['check_in', 'check_out'] : ['check_in', 'lunch_out', 'lunch_in', 'check_out'];
    $missingMarks = $this->getMissingMarks($marks, $requiredMarks);

    return [
      'date' => $date,
      'is_saturday' => $isSaturday,
      'has_check_in' => !empty($marks['check_in']),
      'has_check_out' => !empty($marks['check_out']),
      'marks' => $marks,
      'marks_count' => count(array_filter($marks)),
      'required_marks_count' => count($requiredMarks),
      'has_all_required_marks' => empty($missingMarks),
      'missing_marks' => $missingMarks,
      'standard_hours' => !empty($marks['check_in']) ? 8 : 0,
      'real_hours' => $realHours,
      'scheduled_lunch_minutes' => $schedule['lunch_minutes'],
    ];
  }

  /**
   * Extrae las marcaciones de los registros del día
   */
  private function extractMarksFromRecords(Collection $dayRecords): array
  {
    $marks = [
      'check_in' => null,
      'lunch_out' => null,
      'lunch_in' => null,
      'check_out' => null,
    ];

    foreach ($dayRecords as $record) {
      $markType = $record->mark_type;
      if (array_key_exists($markType, $marks)) {
        // Si hay múltiples marcaciones del mismo tipo, tomar la primera para check_in/lunch_out
        // y la última para lunch_in/check_out
        if (in_array($markType, ['check_in', 'lunch_out'])) {
          if ($marks[$markType] === null) {
            $marks[$markType] = $record->time;
          }
        } else {
          // Para lunch_in y check_out, tomar la última marcación
          $marks[$markType] = $record->time;
        }
      }
    }

    return $marks;
  }

  /**
   * Obtiene el horario programado del trabajador
   */
  private function getWorkerSchedule(int $workerId, string $date): array
  {
    $dayOfWeek = Carbon::parse($date)->dayOfWeek + 1; // MySQL DAYOFWEEK (1=Sun, 7=Sat)

    $schedule = DB::table('rrhh_persona as p')
      ->leftJoin('work_schedules as ws', 'ws.id', '=', 'p.work_schedule_id')
      ->leftJoin('work_schedule_details as wsd', function ($join) use ($dayOfWeek) {
        $join->on('wsd.work_schedule_id', '=', 'p.work_schedule_id')
          ->where('wsd.day_of_week', '=', $dayOfWeek);
      })
      ->where('p.id', $workerId)
      ->select([
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.checkin ELSE ws.checkin END AS check_in"),
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.lunch_out ELSE ws.lunch_out END AS lunch_out"),
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.lunch_in ELSE ws.lunch_in END AS lunch_in"),
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.checkout ELSE ws.checkout END AS check_out"),
      ])
      ->first();

    // Calcular minutos de almuerzo programado
    $lunchMinutes = 0;
    if ($schedule && $schedule->lunch_out && $schedule->lunch_in) {
      $lunchOut = Carbon::parse($schedule->lunch_out);
      $lunchIn = Carbon::parse($schedule->lunch_in);
      $lunchMinutes = $lunchOut->diffInMinutes($lunchIn);
    }

    return [
      'check_in' => $schedule->check_in ?? null,
      'lunch_out' => $schedule->lunch_out ?? null,
      'lunch_in' => $schedule->lunch_in ?? null,
      'check_out' => $schedule->check_out ?? null,
      'lunch_minutes' => $lunchMinutes,
    ];
  }

  /**
   * Calcula las horas REALES trabajadas
   * Fórmula: (check_out - check_in) - tiempo_almuerzo_PROGRAMADO
   */
  private function calculateRealHoursFromMarks(array $marks, array $schedule, bool $isSaturday): float
  {
    $checkIn = $marks['check_in'];
    $checkOut = $marks['check_out'];

    if (!$checkIn || !$checkOut) {
      return 0.0;
    }

    // Calcular minutos brutos trabajados
    $checkInTime = Carbon::parse($checkIn);
    $checkOutTime = Carbon::parse($checkOut);
    $grossMinutes = $checkInTime->diffInMinutes($checkOutTime);

    // Restar tiempo de almuerzo PROGRAMADO (del work_schedule)
    $lunchMinutes = $isSaturday ? 0 : $schedule['lunch_minutes'];

    // Horas reales = (minutos brutos - minutos de almuerzo programado) / 60
    $realHours = max(0, $grossMinutes - $lunchMinutes) / 60;

    return round($realHours, 2);
  }

  /**
   * Identifica marcaciones faltantes
   */
  private function getMissingMarks(array $marks, array $requiredMarks): array
  {
    $missing = [];

    foreach ($requiredMarks as $markType) {
      if (empty($marks[$markType])) {
        $missing[] = $markType;
      }
    }

    return $missing;
  }

  /**
   * Calcula horas trabajadas para múltiples trabajadores en un rango de fechas
   *
   * @param array $workerIds
   * @param string $startDate
   * @param string $endDate
   * @return Collection
   */
  public function calculateWorkedHoursForMultipleWorkers(array $workerIds, string $startDate, string $endDate): Collection
  {
    $results = [];

    foreach ($workerIds as $workerId) {
      $results[] = $this->calculateWorkedHours($workerId, $startDate, $endDate);
    }

    return collect($results);
  }
}
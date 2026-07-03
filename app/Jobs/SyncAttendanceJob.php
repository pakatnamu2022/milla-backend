<?php

namespace App\Jobs;

use App\Models\gp\gestionhumana\asistencias\AttendanceSync;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAttendanceJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  public function __construct(public readonly string $date)
  {
    $this->onQueue('attendance');
  }

  public function handle(): int
  {
    if (!config('attendance.sync_enable')) {
      Log::info('SyncAttendanceJob: disabled via SYNC_DBZKBIO_ENABLED.');
      return 0;
    }

    $date = Carbon::parse($this->date)->toDateString();
    $inserted = 0;

    try {
      $personMap = $this->buildPersonMap();
      $codeMap = $this->buildCodeMappings();
      $rows = $this->fetchTransactions($date);
      $grouped = $rows->groupBy('emp_code');
      $scheduleMap = $this->buildScheduleMap();
      $excluded = $this->buildExclusionSet($date);
      $permisosMap = $this->buildPermisosMap($date);

      AttendanceSync::whereDate('date', $date)->delete();

      $grouped->each(function ($punches, string $empCode) use ($date, $personMap, $codeMap, $scheduleMap, $excluded, $permisosMap, &$inserted) {
        $effectiveVat = $codeMap[$empCode] ?? $empCode;
        $personId = $personMap[$effectiveVat] ?? null;

        if ($personId && isset($excluded[$personId])) {
          Log::info("SyncAttendanceJob [{$date}]: emp_code={$empCode} excluido ({$excluded[$personId]}), se omite.");
          return;
        }

        $permisos = $personId ? ($permisosMap[$personId] ?? []) : [];
        $sorted = $punches->sortBy('punch_time')->values();
        $records = $this->classifyPunches($sorted, $empCode, $date, $personId, $scheduleMap, $permisos);

        $data = $records->map(fn($r) => array_merge($r, [
          'synced_at'  => now()->toDateTimeString(),
          'created_at' => now()->toDateTimeString(),
          'updated_at' => now()->toDateTimeString(),
        ]))->toArray();

        if (empty($data)) {
          return;
        }

        AttendanceSync::insert($data);

        $inserted += count($data);
      });
    } catch (\Throwable $e) {
      Log::error("SyncAttendanceJob [{$date}]: {$e->getMessage()}", ['exception' => $e]);
    }

    Log::info("SyncAttendanceJob [{$date}]: {$inserted} records processed.");
    return $inserted;
  }

  private function fetchTransactions(string $date): \Illuminate\Support\Collection
  {
    $rows = collect();

    DB::connection('zkbiotime')
      ->table('db_owner.iclock_transaction as t')
      ->leftJoin('db_owner.personnel_employee as e', 'e.id', '=', 't.emp_id')
      ->select([
        't.id as transaction_id',
        DB::raw("COALESCE(e.emp_code, t.emp_code) AS emp_code"),
        DB::raw("LTRIM(RTRIM(ISNULL(e.first_name,'')+' '+ISNULL(e.last_name,''))) AS full_name"),
        't.punch_time',
        't.punch_state',
        't.area_alias',
      ])
      ->whereDate('t.punch_time', $date)
      ->orderBy('e.emp_code')
      ->orderBy('t.punch_time')
      ->chunk(500, function ($chunk) use (&$rows) {
        $rows = $rows->concat($chunk);
      });

    return $rows;
  }

  private function classifyPunches(
    \Illuminate\Support\Collection $punches,
    string                         $empCode,
    string                         $date,
    ?int                           $personId,
    array                          $scheduleMap,
    array                          $permisos = []
  ): \Illuminate\Support\Collection
  {
    $schedule = $personId ? ($scheduleMap[$personId] ?? null) : null;
    $isSaturday = Carbon::parse($date)->dayOfWeek === 6;

    $checkin = $schedule->checkin ?? '08:00:00';
    $lunchOut = $schedule->lunch_out ?? '13:00:00';
    $lunchIn = $schedule->lunch_in ?? '14:24:00';
    $checkout = $schedule->checkout ?? '18:00:00';

    // Saturday: only 2 slots (check_in + check_out at lunch_out time = half day)
    $slots = $isSaturday
      ? ['check_in' => $checkin, 'check_out' => $lunchOut]
      : ['check_in' => $checkin, 'lunch_out' => $lunchOut, 'lunch_in' => $lunchIn, 'check_out' => $checkout];

    if (!empty($permisos)) {
      $permisosMin = array_map(fn($p) => [
        'desde' => $this->timeToMinutes($p['hora_inicio']),
        'hasta' => $this->timeToMinutes($p['hora_fin']),
      ], $permisos);

      $inPermiso = function(int $min) use ($permisosMin): bool {
        foreach ($permisosMin as $p) {
          if ($min >= $p['desde'] && $min <= $p['hasta']) return true;
        }
        return false;
      };

      $slots = array_filter($slots, fn($t) => !$inPermiso($this->timeToMinutes($t)));

      if (empty($slots)) {
        Log::info("SyncAttendanceJob [{$date}]: emp_code={$empCode} permiso jornada completa, se omite.");
        return collect();
      }

      $punches = $punches->filter(
        fn($row) => !$inPermiso($this->timeToMinutes(Carbon::parse($row->punch_time)->format('H:i:s')))
      )->values();
    }

    // Punch-first matching: each punch claims its nearest unassigned slot.
    // This correctly handles single-punch scenarios (e.g. only a 18:11 punch maps
    // to check_out, not check_in, because it's only 11 min away vs 611 min).
    $usedSlots = [];
    $results = [];

    foreach ($punches->sortBy(fn($r) => $r->punch_time)->values() as $row) {
      $punchMinutes = $this->timeToMinutes(Carbon::parse($row->punch_time)->format('H:i:s'));
      $bestSlot = null;
      $bestDiff = PHP_INT_MAX;

      foreach ($slots as $slotType => $targetTime) {
        if (in_array($slotType, $usedSlots, true)) continue;

        $diff = abs($punchMinutes - $this->timeToMinutes($targetTime));
        if ($diff < $bestDiff) {
          $bestDiff = $diff;
          $bestSlot = $slotType;
        }
      }

      if ($bestSlot !== null) {
        $usedSlots[] = $bestSlot;
        $results[] = [
          'zkbio_transaction_id' => (int)$row->transaction_id,
          'person_id'            => $personId,
          'emp_code'             => $empCode,
          'full_name'            => $row->full_name ?? '',
          'date'                 => $date,
          'mark_type'            => $bestSlot,
          'time'                 => Carbon::parse($row->punch_time)->format('H:i:s'),
          'area'                 => $row->area_alias ?: null,
          'punch_state_original' => (string)$row->punch_state,
        ];
      }
    }

    return collect($results);
  }

  private function timeToMinutes(string $time): int
  {
    [$h, $m] = explode(':', $time);
    return (int)$h * 60 + (int)$m;
  }

  private function buildExclusionSet(string $date): array
  {
    $vacations = DB::table('rrhh_vacaciones')
      ->where('status_deleted', 1)
      ->where('aprobacion_rrhh', 1)
      ->where('fecha_inicio', '<=', $date)
      ->where('fecha_fin', '>=', $date)
      ->pluck('empleado_id');

    // Ausentismo with tipo_descanso label for reporting
    $absenteeRows = DB::table('rrhh_ausentismo_laboral as al')
      ->leftJoin('rrhh_tipo_descanso as td', 'td.id', '=', 'al.id_tipo_descanso')
      ->where('al.status_deleted', 1)
      ->where('al.fecha_inicial', '<=', $date)
      ->where('al.fecha_fin', '>=', $date)
      ->select('al.empleado_id', DB::raw("COALESCE(td.descripcion, 'Ausentismo') AS tipo_label"))
      ->get();

    $absenteeMap = $absenteeRows->mapWithKeys(fn($r) => [(int)$r->empleado_id => 'ausentismo: ' . $r->tipo_label]);
    $absentees = $absenteeRows->pluck('empleado_id');

    $byExclusionTable = DB::table('attendance_exclusions')
      ->where('active', 1)
      ->pluck('person_id');

    $byPosition = DB::table('rrhh_persona as p')
      ->join('rrhh_cargo as c', 'c.id', '=', 'p.cargo_id')
      ->where('p.status_deleted', 1)
      ->where('c.no_attendance_required', 1)
      ->pluck('p.id');

    return $vacations->merge($absentees)->merge($byExclusionTable)->merge($byPosition)
      ->unique()
      ->mapWithKeys(function ($id) use ($vacations, $absenteeMap, $absentees, $byExclusionTable, $byPosition) {
        $id = (int)$id;
        if ($vacations->contains($id))        return [$id => 'vacaciones'];
        if ($absentees->contains($id))        return [$id => ($absenteeMap[$id] ?? 'ausentismo')];
        if ($byExclusionTable->contains($id)) return [$id => 'exclusion_manual'];
        if ($byPosition->contains($id))       return [$id => 'cargo'];
        return [$id => 'desconocido'];
      })
      ->toArray();
  }

  private function buildPermisosMap(string $date): array
  {
    return DB::table('rrhh_trabajador_permiso')
      ->where('status_deleted', 1)
      ->whereDate('fecha_inicio', '<=', $date)
      ->whereDate('fecha_fin', '>=', $date)
      ->select('partner_id', 'fecha_inicio', 'fecha_fin')
      ->get()
      ->groupBy('partner_id')
      ->map(function ($rows) use ($date) {
        return $rows->map(function ($r) use ($date) {
          $inicioDate = Carbon::parse($r->fecha_inicio)->toDateString();
          $finDate    = Carbon::parse($r->fecha_fin)->toDateString();
          $horaInicio = Carbon::parse($r->fecha_inicio)->format('H:i:s');
          $horaFin    = Carbon::parse($r->fecha_fin)->format('H:i:s');

          if ($inicioDate === $date && $finDate === $date) {
            // Mismo día: usar horas exactas; 00:00-00:00 es legacy full-day
            if ($horaInicio === '00:00:00' && $horaFin === '00:00:00') {
              $horaFin = '23:59:59';
            }
          } elseif ($inicioDate === $date) {
            // Primer día de rango multi-día
            $horaFin = '23:59:59';
          } elseif ($finDate === $date) {
            // Último día de rango multi-día
            $horaInicio = '00:00:00';
            if ($horaFin === '00:00:00') $horaFin = '23:59:59';
          } else {
            // Día intermedio: jornada completa
            $horaInicio = '00:00:00';
            $horaFin    = '23:59:59';
          }

          return ['hora_inicio' => $horaInicio, 'hora_fin' => $horaFin];
        })->toArray();
      })
      ->toArray();
  }

  private function buildPersonMap(): array
  {
    // NULLs first (ASC), status_id=22 last → last value wins the pluck key overwrite
    return DB::table('rrhh_persona')
      ->whereNotNull('vat')
      ->where('status_deleted', 1)
      ->orderByRaw('CASE WHEN status_id = 22 THEN 1 ELSE 0 END ASC')
      ->pluck('id', 'vat')
      ->toArray();
  }

  private function buildCodeMappings(): array
  {
    return DB::table('attendance_code_mappings')
      ->pluck('vat', 'emp_code')
      ->toArray();
  }

  private function buildScheduleMap(): array
  {
    return DB::table('rrhh_persona as p')
      ->leftJoin('work_schedules as ws', 'ws.id', '=', 'p.work_schedule_id')
      ->whereNotNull('p.vat')
      ->where('p.status_deleted', 1)
      ->select([
        'p.id as person_id',
        DB::raw("COALESCE(ws.checkin,   '08:00:00') AS checkin"),
        DB::raw("COALESCE(ws.lunch_out, '13:00:00') AS lunch_out"),
        DB::raw("COALESCE(ws.lunch_in,  '14:24:00') AS lunch_in"),
        DB::raw("COALESCE(ws.checkout,  '18:00:00') AS checkout"),
      ])
      ->get()
      ->keyBy('person_id')
      ->toArray();
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("SyncAttendanceJob permanently failed [{$this->date}]: {$exception->getMessage()}");
  }
}

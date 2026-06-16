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

  public int $tries   = 3;
  public int $timeout = 300;

  public function __construct(public readonly string $date)
  {
    $this->onQueue('attendance');
  }

  public function handle(): int
  {
    if (!env('SYNC_DBZKBIO_ENABLED', false)) {
      Log::info('SyncAttendanceJob: disabled via SYNC_DBZKBIO_ENABLED.');
      return 0;
    }

    $date    = Carbon::parse($this->date)->toDateString();
    $inserted = 0;

    try {
      $personMap   = $this->buildPersonMap();
      $rows        = $this->fetchTransactions($date);
      $grouped     = $rows->groupBy('emp_code');
      $scheduleMap = $this->buildScheduleMap();
      $onVacation  = $this->buildVacationSet($date);

      $grouped->each(function ($punches, string $empCode) use ($date, $personMap, $scheduleMap, $onVacation, &$inserted) {
        $personId = $personMap[$empCode] ?? null;

        if ($personId && isset($onVacation[$personId])) {
          Log::info("SyncAttendanceJob [{$date}]: emp_code={$empCode} está de vacaciones, se omite.");
          return;
        }

        $sorted  = $punches->sortBy('punch_time')->values();
        $records = $this->classifyPunches($sorted, $empCode, $date, $personMap, $scheduleMap);

        $data = $records->map(fn($r) => array_merge($r, [
          'synced_at'  => now()->toDateTimeString(),
          'created_at' => now()->toDateTimeString(),
          'updated_at' => now()->toDateTimeString(),
        ]))->toArray();

        if (empty($data)) {
          return;
        }

        AttendanceSync::upsert(
          $data,
          ['zkbio_transaction_id'],
          ['person_id', 'full_name', 'mark_type', 'time', 'area', 'punch_state_original', 'synced_at', 'updated_at']
        );

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
      ->join('db_owner.personnel_employee as e', 'e.id', '=', 't.emp_id')
      ->select([
        't.id as transaction_id',
        'e.emp_code',
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
    string $empCode,
    string $date,
    array $personMap,
    array $scheduleMap
  ): \Illuminate\Support\Collection {
    $personId   = $personMap[$empCode] ?? null;
    $schedule   = $personId ? ($scheduleMap[$personId] ?? null) : null;
    $isSaturday = Carbon::parse($date)->dayOfWeek === 6;

    $checkin  = $schedule->checkin   ?? '08:00:00';
    $lunchOut = $schedule->lunch_out ?? '13:00:00';
    $lunchIn  = $schedule->lunch_in  ?? '14:24:00';
    $checkout = $schedule->checkout  ?? '18:00:00';

    // Saturday: only 2 slots (check_in + check_out at lunch_out time = half day)
    $slots = $isSaturday
      ? ['check_in' => $checkin, 'check_out' => $lunchOut]
      : ['check_in' => $checkin, 'lunch_out' => $lunchOut, 'lunch_in' => $lunchIn, 'check_out' => $checkout];

    // Punch-first matching: each punch claims its nearest unassigned slot.
    // This correctly handles single-punch scenarios (e.g. only a 18:11 punch maps
    // to check_out, not check_in, because it's only 11 min away vs 611 min).
    $usedSlots = [];
    $results   = [];

    foreach ($punches->sortBy(fn($r) => $r->punch_time)->values() as $row) {
      $punchMinutes = $this->timeToMinutes(Carbon::parse($row->punch_time)->format('H:i:s'));
      $bestSlot     = null;
      $bestDiff     = PHP_INT_MAX;

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
        $results[]   = [
          'zkbio_transaction_id' => (int) $row->transaction_id,
          'person_id'            => $personId,
          'emp_code'             => $empCode,
          'full_name'            => $row->full_name ?? '',
          'date'                 => $date,
          'mark_type'            => $bestSlot,
          'time'                 => Carbon::parse($row->punch_time)->format('H:i:s'),
          'area'                 => $row->area_alias ?: null,
          'punch_state_original' => (string) $row->punch_state,
        ];
      }
    }

    return collect($results);
  }

  private function timeToMinutes(string $time): int
  {
    [$h, $m] = explode(':', $time);
    return (int) $h * 60 + (int) $m;
  }

  private function buildVacationSet(string $date): array
  {
    return DB::table('rrhh_vacaciones')
      ->where('status_deleted', 1)
      ->where('aprobacion_rrhh', 1)
      ->where('fecha_inicio', '<=', $date)
      ->where('fecha_fin', '>=', $date)
      ->pluck('empleado_id')
      ->flip()
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

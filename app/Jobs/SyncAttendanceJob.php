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
      $personMap = $this->buildPersonMap();
      $rows      = $this->fetchTransactions($date);
      $grouped   = $rows->groupBy('emp_code');

      $grouped->each(function ($punches, string $empCode) use ($date, $personMap, &$inserted) {
        $sorted  = $punches->sortBy('punch_time')->values();
        $records = $this->classifyPunches($sorted, $empCode, $date, $personMap);

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
    array $personMap
  ): \Illuminate\Support\Collection {
    $count    = $punches->count();
    $personId = $personMap[$empCode] ?? null;

    $types = match(true) {
      $count >= 4 => ['check_in', 'lunch_out', 'lunch_in', 'check_out'],
      $count === 3 => ['check_in', 'lunch_out', 'check_out'],
      default     => ['check_in', 'check_out'],
    };

    return $punches->take(count($types))->values()->map(function ($row, int $idx) use ($types, $empCode, $date, $personId) {
      $punched  = Carbon::parse($row->punch_time);
      $markType = $types[$idx];

      // Punch at or after 18:00 is always check_out, never lunch_in
      if ($markType === 'lunch_in' && $punched->hour >= 18) {
        $markType = 'check_out';
      }

      return [
        'zkbio_transaction_id' => (int) $row->transaction_id,
        'person_id'            => $personId,
        'emp_code'             => $empCode,
        'full_name'            => $row->full_name ?? '',
        'date'                 => $date,
        'mark_type'            => $markType,
        'time'                 => $punched->format('H:i:s'),
        'area'                 => $row->area_alias ?: null,
        'punch_state_original' => (string) $row->punch_state,
      ];
    });
  }

  private function buildPersonMap(): array
  {
    return DB::table('rrhh_persona')
      ->whereNotNull('vat')
      ->where('status_deleted', 1)
      ->pluck('id', 'vat')
      ->toArray();
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("SyncAttendanceJob permanently failed [{$this->date}]: {$exception->getMessage()}");
  }
}

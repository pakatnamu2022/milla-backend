<?php

namespace App\Jobs;

use App\Http\Services\gp\gestionhumana\asistencias\AttendanceSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAbsentAttendanceReportJob implements ShouldQueue
{
  use Queueable;

  public int $tries   = 1;
  public int $timeout = 120;

  public function __construct(
    public readonly ?string $date = null,
    public readonly ?int $partnerUserId = null,
    public readonly bool $skipSync = false,
  ) {
    $this->onQueue('attendance');
  }

  public function handle(AttendanceSyncService $service): void
  {
    $result = $service->sendAbsentReport($this->date, $this->partnerUserId, skipSync: $this->skipSync);
    Log::info('SendAbsentAttendanceReportJob', $result);
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('SendAbsentAttendanceReportJob: ' . $exception->getMessage());
  }
}

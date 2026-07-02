<?php

namespace App\Console\Commands;

use App\Http\Utils\Constants;
use App\Jobs\SendAbsentAttendanceReportJob;
use App\Jobs\SyncAttendanceJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAbsentAttendanceReportCommand extends Command
{
  protected $signature = 'attendance:send-absent-report
    {--date= : Date to check (YYYY-MM-DD, default: today)}';

  protected $description = 'Send attendance absence and tardiness report to each configured partner user';

  public function handle(): int
  {
    $date = $this->option('date')
      ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->toDateString()
      : now('America/Lima')->toDateString();

    $this->info("Syncing attendance for date: {$date}");
    SyncAttendanceJob::dispatchSync($date);
    $this->info('Sync complete. Dispatching report jobs...');

    foreach (Constants::ATTENDANCE_REPORT_USER_IDS as $userId) {
      $this->info("Dispatching SendAbsentAttendanceReportJob for date={$date} user_id={$userId}");
      SendAbsentAttendanceReportJob::dispatch($date, $userId, skipSync: true);
    }

    $this->info('All jobs dispatched successfully.');

    return Command::SUCCESS;
  }
}

<?php

namespace App\Console\Commands;

use App\Jobs\SendAbsentAttendanceReportJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAbsentAttendanceReportCommand extends Command
{
  protected $signature = 'attendance:send-absent-report
    {--date= : Date to check (YYYY-MM-DD, default: today)}';

  protected $description = 'Send absent attendance report by email for workers who have not checked in by 9:30 AM';

  public function handle(): int
  {
    $date = $this->option('date')
      ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->toDateString()
      : now('America/Lima')->toDateString();

    $this->info("Dispatching SendAbsentAttendanceReportJob for date: {$date}");
    SendAbsentAttendanceReportJob::dispatch($date);
    $this->info('Job dispatched successfully.');

    return Command::SUCCESS;
  }
}

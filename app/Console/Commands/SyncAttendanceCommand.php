<?php

namespace App\Console\Commands;

use App\Jobs\SyncAttendanceJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAttendanceCommand extends Command
{
  protected $signature = 'sync:attendance
    {--date= : Single date to sync (YYYY-MM-DD, default: today)}
    {--from= : Start of date range (YYYY-MM-DD)}
    {--to=   : End of date range (YYYY-MM-DD)}';

  protected $description = 'Sync attendance punches from ZKBioTime into attendance_sync';

  public function handle(): int
  {
    if ($this->option('from') || $this->option('to')) {
      return $this->handleRange();
    }

    $date = $this->option('date')
      ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->toDateString()
      : now()->toDateString();

    $this->info("Dispatching SyncAttendanceJob for date: {$date}");
    SyncAttendanceJob::dispatch($date);
    $this->info('Job dispatched successfully.');

    return Command::SUCCESS;
  }

  private function handleRange(): int
  {
    $from = $this->option('from') ? Carbon::createFromFormat('Y-m-d', $this->option('from')) : now()->startOfMonth();
    $to   = $this->option('to')   ? Carbon::createFromFormat('Y-m-d', $this->option('to'))   : now();

    if ($from->gt($to)) {
      $this->error('--from must be before or equal to --to.');
      return Command::FAILURE;
    }

    $days = 0;
    for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
      SyncAttendanceJob::dispatch($day->toDateString());
      $days++;
    }

    $this->info("Dispatched {$days} SyncAttendanceJob(s) from {$from->toDateString()} to {$to->toDateString()}.");
    return Command::SUCCESS;
  }
}

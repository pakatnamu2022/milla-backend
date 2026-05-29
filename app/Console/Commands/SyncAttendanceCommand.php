<?php

namespace App\Console\Commands;

use App\Jobs\SyncAttendanceJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAttendanceCommand extends Command
{
  protected $signature = 'sync:attendance {--date= : Date to sync in YYYY-MM-DD format (default: today)}';

  protected $description = 'Sync attendance punches from ZKBioTime into attendance_sync';

  public function handle(): int
  {
    $date = $this->option('date')
      ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->toDateString()
      : now()->toDateString();

    $this->info("Dispatching SyncAttendanceJob for date: {$date}");

    SyncAttendanceJob::dispatch($date);

    $this->info('Job dispatched successfully.');

    return Command::SUCCESS;
  }
}

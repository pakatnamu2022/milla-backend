<?php

namespace App\Jobs;

use App\Http\Services\Dashboard\AdoptionDashboardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WarmAdoptionCacheJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 1;
  public int $timeout = 180;

  public function __construct(private array $filters = [])
  {
    $this->onQueue('adoption-cache');
  }

  public function handle(AdoptionDashboardService $service): void
  {
    $service->warmAll($this->filters);
  }
}

<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ValidatesPendingJobs;
use App\Jobs\SyncShippingGuideDynamicsJob;
use App\Models\ap\comercial\ShippingGuides;
use Illuminate\Console\Command;

class SyncShippingGuideDynamicsCommand extends Command
{
  use ValidatesPendingJobs;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'shipping-guide:sync-dynamics {--id= : ID de la guía de remisión específica} {--all : Sincronizar todas las guías sin dyn_series} {--limit=50 : Número máximo de guías a procesar (default: 50)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sincroniza las guías de remisión consultando el PA de Dynamics para transferencias de inventario';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $shippingGuideId = $this->option('id');
    $all = $this->option('all');

    if (!$shippingGuideId && !$all) {
      $this->error('Debe especificar --id o --all');
      return Command::FAILURE;
    }

    if ($shippingGuideId && $all) {
      $this->error('No puede especificar --id y --all al mismo tiempo');
      return Command::FAILURE;
    }

    if ($shippingGuideId) {
      return $this->syncSingleShippingGuide((int)$shippingGuideId);
    }

    return $this->syncAllShippingGuides();
  }

  /**
   * Sincroniza una guía de remisión específica
   */
  protected function syncSingleShippingGuide(int $shippingGuideId): int
  {
    $shippingGuide = ShippingGuides::find($shippingGuideId);

    if (!$shippingGuide) {
      $this->error("Guía de remisión #{$shippingGuideId} no encontrada");
      return Command::FAILURE;
    }

    if (!$shippingGuide->document_number) {
      $this->error("La guía de remisión #{$shippingGuideId} no tiene número de documento asignado");
      return Command::FAILURE;
    }

    $this->info("Sincronizando datos de Dynamics para guía: {$shippingGuide->document_number}");

    SyncShippingGuideDynamicsJob::dispatch($shippingGuide->id);

    $this->info("Job despachado exitosamente");
    return Command::SUCCESS;
  }

  /**
   * Sincroniza todas las guías pendientes
   */
  protected function syncAllShippingGuides(): int
  {
    // Validar límite de jobs pendientes antes de despachar
    if (!$this->canDispatchMoreJobs(SyncShippingGuideDynamicsJob::class)) {
      return Command::SUCCESS;
    }

    $limit = (int) $this->option('limit');

    // Obtener guías que no tienen dyn_series sincronizado
    $shippingGuides = ShippingGuides::where(function ($query) {
      $query->whereNull('dyn_series')
        ->orWhere('dyn_series', '');
    })
      ->whereNotNull('document_number')
      ->where('status', true)
      ->orderBy('id')
      ->limit($limit)
      ->get();

    if ($shippingGuides->isEmpty()) {
      $this->info('No hay guías de remisión pendientes de sincronizar con Dynamics');
      return Command::SUCCESS;
    }

    $this->info("Despachando jobs para sincronizar {$shippingGuides->count()} guías de remisión");

    $bar = $this->output->createProgressBar($shippingGuides->count());
    $bar->start();

    foreach ($shippingGuides as $guide) {
      SyncShippingGuideDynamicsJob::dispatch($guide->id);
      $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    $this->info("Jobs despachados exitosamente");
    return Command::SUCCESS;
  }
}
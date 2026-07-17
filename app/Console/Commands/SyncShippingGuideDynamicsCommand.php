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
  protected $signature = 'shipping-guide:sync-dynamics {--id= : ID de la guía de remisión específica} {--all : Sincronizar todas las guías sin dyn_series}';

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

    $shippingGuides = ShippingGuides::whereNotNull('document_number')
      ->where(function ($q) {
        $q->where(function ($q2) {
          $q2->where('status', true)
            ->where('is_accounted', false);
        })
          ->orWhere(function ($q2) {
            $q2->where('status', false)
              ->where('is_annulled', false);
          })
          ->orWhere(function ($q2) {
            $q2->where('status', true)
              ->where('is_accounted', true)
              ->where('area_id', \App\Models\ap\ApMasters::AREA_COMERCIAL)
              ->whereNotIn('transfer_reason_id', [\App\Models\gp\maestroGeneral\SunatConcepts::TRANSFER_REASON_VENTA])
              ->where('issue_date', '>=', now()->startOfDay());
          });
      })
      ->orderBy('id')
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

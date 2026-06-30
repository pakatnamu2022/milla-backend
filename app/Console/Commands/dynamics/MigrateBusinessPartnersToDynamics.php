<?php

namespace App\Console\Commands\dynamics;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateBusinessPartnersToDynamics extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'business-partners:migrate-to-dynamics
                          {--type= : Filtrar por tipo (CLIENTE, PROVEEDOR, AMBOS)}
                          {--document= : Filtrar por número de documento específico}
                          {--limit= : Limitar el número de registros a migrar}
                          {--dry-run : Ejecutar en modo de prueba sin crear registros}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Migra todos los business partners (clientes y proveedores) a Dynamics verificando uno por uno';

  protected int $totalPartners = 0;
  protected int $migratedAsClient = 0;
  protected int $migratedAsSupplier = 0;
  protected int $skippedAsClient = 0;
  protected int $skippedAsSupplier = 0;
  protected int $failedPartners = 0;
  protected array $errors = [];

  /**
   * Execute the console command.
   */
  public function handle(DatabaseSyncService $syncService): int
  {
    $this->info('=======================================================');
    $this->info('  MIGRACIÓN DE BUSINESS PARTNERS A DYNAMICS');
    $this->info('=======================================================');
    $this->newLine();

    $isDryRun = $this->option('dry-run');
    $limit = $this->option('limit');
    $typeFilter = $this->option('type');
    $documentFilter = $this->option('document');

    if ($isDryRun) {
      $this->warn('⚠️  MODO DRY-RUN ACTIVADO - No se crearán registros');
      $this->newLine();
    }

    // Construir query
    $query = BusinessPartners::query();

    // Filtrar por tipo si se especifica
    if ($typeFilter) {
      $validTypes = [BusinessPartners::CLIENT, BusinessPartners::SUPPLIER, BusinessPartners::BOTH];
      if (!in_array($typeFilter, $validTypes)) {
        $this->error("❌ Tipo inválido. Use: CLIENTE, PROVEEDOR o AMBOS");
        return Command::FAILURE;
      }
      $query->where('type', $typeFilter);
      $this->info("🔍 Filtrando por tipo: {$typeFilter}");
    }

    // Filtrar por documento si se especifica
    if ($documentFilter) {
      $query->where('num_doc', $documentFilter);
      $this->info("📄 Filtrando por documento: {$documentFilter}");
    }

    if ($limit) {
      $query->limit((int)$limit);
      $this->info("🔢 Limitando a {$limit} registros");
    }

    $partners = $query->get();
    $this->totalPartners = $partners->count();

    if ($this->totalPartners === 0) {
      $this->warn('⚠️  No se encontraron business partners para migrar.');
      return Command::SUCCESS;
    }

    $this->info("👥 Total de business partners a procesar: {$this->totalPartners}");
    $this->newLine();

    // Confirmar antes de continuar
    if (!$isDryRun && !$this->confirm('¿Desea continuar con la migración?', true)) {
      $this->warn('❌ Migración cancelada por el usuario.');
      return Command::SUCCESS;
    }

    $this->newLine();
    $this->info('🚀 Iniciando migración...');
    $this->newLine();

    // Crear barra de progreso
    $progressBar = $this->output->createProgressBar($this->totalPartners);
    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
    $progressBar->setMessage('Iniciando...');

    foreach ($partners as $partner) {
      $progressBar->setMessage("Procesando: {$partner->full_name} ({$partner->num_doc}) - Tipo: {$partner->type}");

      try {
        $this->processBusinessPartner($partner, $syncService, $isDryRun);
      } catch (\Exception $e) {
        $this->failedPartners++;
        $this->errors[] = [
          'partner_id' => $partner->id,
          'partner_doc' => $partner->num_doc,
          'partner_name' => $partner->full_name,
          'type' => $partner->type,
          'error' => $e->getMessage(),
        ];

        Log::error("Error al migrar business partner ID {$partner->id}: {$e->getMessage()}");
      }

      $progressBar->advance();
    }

    $progressBar->finish();
    $this->newLine(2);

    // Mostrar resumen
    $this->displaySummary($isDryRun);

    return Command::SUCCESS;
  }

  /**
   * Procesa un business partner según su tipo
   */
  protected function processBusinessPartner(BusinessPartners $partner, DatabaseSyncService $syncService, bool $isDryRun): void
  {
    // Procesar según el tipo
    switch ($partner->type) {
      case BusinessPartners::CLIENT:
        // Solo cliente
        $this->syncAsClient($partner, $syncService, $isDryRun);
        break;

      case BusinessPartners::SUPPLIER:
        // Solo proveedor
        $this->syncAsSupplier($partner, $syncService, $isDryRun);
        break;

      case BusinessPartners::BOTH:
        // Ambos: cliente Y proveedor
        $this->syncAsClient($partner, $syncService, $isDryRun);
        $this->syncAsSupplier($partner, $syncService, $isDryRun);
        break;
    }
  }

  /**
   * Sincroniza como CLIENTE
   */
  protected function syncAsClient(BusinessPartners $partner, DatabaseSyncService $syncService, bool $isDryRun): void
  {
    // Verificar si ya existe en neInTbCliente
    $existingClient = DB::connection('dbtp')
      ->table('neInTbCliente')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('Cliente', $partner->num_doc)
      ->first();

    if ($existingClient) {
      $this->skippedAsClient++;
      return;
    }

    // No existe, sincronizarlo
    if (!$isDryRun) {
      // Sincronizar cliente
      $syncService->sync('business_partners', $partner->toArray(), 'create');

      // Sincronizar dirección de cliente
      $syncService->sync('business_partners_directions', $partner->toArray(), 'create');
    }

    $this->migratedAsClient++;
  }

  /**
   * Sincroniza como PROVEEDOR
   */
  protected function syncAsSupplier(BusinessPartners $partner, DatabaseSyncService $syncService, bool $isDryRun): void
  {
    // Verificar si ya existe en neInTbProveedor
    $existingSupplier = DB::connection('dbtp')
      ->table('neInTbProveedor')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('NumeroDocumento', $partner->num_doc)
      ->first();

    if ($existingSupplier) {
      $this->skippedAsSupplier++;
      return;
    }

    // No existe, sincronizarlo
    if (!$isDryRun) {
      // Sincronizar proveedor
      $syncService->sync('business_partners_ap_supplier', $partner->toArray(), 'create');

      // Sincronizar dirección de proveedor
      $syncService->sync('business_partners_directions_ap_supplier', $partner->toArray(), 'create');
    }

    $this->migratedAsSupplier++;
  }

  /**
   * Muestra el resumen de la migración
   */
  protected function displaySummary(bool $isDryRun): void
  {
    $this->info('=======================================================');
    $this->info('  RESUMEN DE MIGRACIÓN');
    $this->info('=======================================================');
    $this->newLine();

    $this->table(
      ['Métrica', 'Cantidad'],
      [
        ['Total business partners procesados', $this->totalPartners],
        ['', ''],
        ['📘 Migrados como CLIENTE', $this->migratedAsClient],
        ['📘 Omitidos como CLIENTE (ya existen)', $this->skippedAsClient],
        ['', ''],
        ['📙 Migrados como PROVEEDOR', $this->migratedAsSupplier],
        ['📙 Omitidos como PROVEEDOR (ya existen)', $this->skippedAsSupplier],
        ['', ''],
        ['❌ Registros con error', $this->failedPartners],
      ]
    );

    $this->newLine();

    // Explicación de contadores
    $this->info('💡 Nota: Los registros con type="AMBOS" se cuentan en ambas categorías (CLIENTE + PROVEEDOR)');
    $this->newLine();

    if ($this->failedPartners > 0) {
      $this->error("❌ Se encontraron {$this->failedPartners} errores durante la migración:");
      $this->newLine();
      $this->table(
        ['ID', 'Documento', 'Nombre', 'Tipo', 'Error'],
        array_map(function ($error) {
          return [
            $error['partner_id'],
            $error['partner_doc'],
            substr($error['partner_name'], 0, 30),
            $error['type'],
            substr($error['error'], 0, 40) . '...',
          ];
        }, $this->errors)
      );
    }

    if ($isDryRun) {
      $this->newLine();
      $this->warn('⚠️  MODO DRY-RUN - No se crearon registros reales');
    } else {
      $this->newLine();
      $this->info('✅ Migración completada exitosamente');
    }
  }
}

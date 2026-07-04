<?php

namespace App\Console\Commands\dynamics;

use App\Http\Resources\ap\postventa\gestionProductos\ProductArticleResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateProductsToDynamics extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'products:migrate-to-dynamics
                          {--dyn-code= : Filtrar por código de producto específico (dyn_code)}
                          {--limit= : Limitar el número de productos a migrar}
                          {--dry-run : Ejecutar en modo de prueba sin crear registros}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Migra todos los productos de la tabla products a Dynamics verificando uno por uno';

  protected int $totalProducts = 0;
  protected int $migratedProducts = 0;
  protected int $skippedProducts = 0;
  protected int $failedProducts = 0;
  protected array $errors = [];

  /**
   * Execute the console command.
   */
  public function handle(DatabaseSyncService $syncService): int
  {
    $this->info('===========================================');
    $this->info('  MIGRACIÓN DE PRODUCTOS A DYNAMICS');
    $this->info('===========================================');
    $this->newLine();

    $isDryRun = $this->option('dry-run');
    $limit = $this->option('limit');
    $dynCodeFilter = $this->option('dyn-code');

    if ($isDryRun) {
      $this->warn('⚠️  MODO DRY-RUN ACTIVADO - No se crearán registros');
      $this->newLine();
    }

    // Obtener todos los productos con dyn_code a partir del ID 3000
    $query = Products::with(['brand', 'category', 'articleClass', 'unitMeasurement'])
      ->whereNotNull('dyn_code')
      ->where('dyn_code', '!=', '')
      ->where('id', '>=', 3000);

    // Filtrar por dyn_code si se especifica
    if ($dynCodeFilter) {
      $query->where('dyn_code', $dynCodeFilter);
      $this->info("📄 Filtrando por dyn_code: {$dynCodeFilter}");
    }

    if ($limit) {
      $query->limit((int)$limit);
      $this->info("🔢 Limitando a {$limit} productos");
    }

    $products = $query->get();
    $this->totalProducts = $products->count();

    if ($this->totalProducts === 0) {
      $this->warn('⚠️  No se encontraron productos con dyn_code para migrar.');
      return Command::SUCCESS;
    }

    $this->info("📦 Total de productos a procesar: {$this->totalProducts}");
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
    $progressBar = $this->output->createProgressBar($this->totalProducts);
    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
    $progressBar->setMessage('Iniciando...');

    foreach ($products as $product) {
      $progressBar->setMessage("Procesando: {$product->name} ({$product->dyn_code})");

      try {
        // Verificar si ya existe en la BD intermedia
        $existingArticle = DB::connection('dbtp')
          ->table('neInTbArticulo')
          ->where('EmpresaId', Company::AP_DYNAMICS)
          ->where('Articulo', $product->dyn_code)
          ->first();

        if ($existingArticle) {
          // Ya existe, saltarlo
          $this->skippedProducts++;
          $progressBar->advance();
          continue;
        }

        // No existe, sincronizarlo
        if (!$isDryRun) {
          $this->syncProduct($product, $syncService);
          $this->migratedProducts++;
        } else {
          // En modo dry-run solo contar
          $this->migratedProducts++;
        }

      } catch (\Exception $e) {
        $this->failedProducts++;
        $this->errors[] = [
          'product_id' => $product->id,
          'product_code' => $product->dyn_code,
          'product_name' => $product->name,
          'error' => $e->getMessage(),
        ];

        Log::error("Error al migrar producto ID {$product->id}: {$e->getMessage()}");
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
   * Sincroniza un producto a Dynamics
   */
  protected function syncProduct(Products $product, DatabaseSyncService $syncService): void
  {
    // Usar el mismo resource que usa el Job
    $resource = new ProductArticleResource($product);
    $syncService->sync('article_product', $resource->toArray(request()), 'create');
  }

  /**
   * Muestra el resumen de la migración
   */
  protected function displaySummary(bool $isDryRun): void
  {
    $this->info('===========================================');
    $this->info('  RESUMEN DE MIGRACIÓN');
    $this->info('===========================================');
    $this->newLine();

    $this->table(
      ['Métrica', 'Cantidad'],
      [
        ['Total productos procesados', $this->totalProducts],
        ['Productos migrados', $this->migratedProducts],
        ['Productos omitidos (ya existen)', $this->skippedProducts],
        ['Productos fallidos', $this->failedProducts],
      ]
    );

    $this->newLine();

    if ($this->failedProducts > 0) {
      $this->error("❌ Se encontraron {$this->failedProducts} errores durante la migración:");
      $this->newLine();
      $this->table(
        ['ID', 'Código', 'Nombre', 'Error'],
        array_map(function ($error) {
          return [
            $error['product_id'],
            $error['product_code'],
            $error['product_name'],
            substr($error['error'], 0, 50) . '...',
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

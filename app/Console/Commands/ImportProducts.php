<?php

namespace App\Console\Commands;

use App\Models\ap\ApMasters;
use Illuminate\Console\Command;
use App\Http\Services\ap\postventa\gestionProductos\ProductsService;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;

class ImportProducts extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:products {file?} {--mode=update : Mode: update (safe, default) or reset (dangerous, commented for now)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Importar productos desde un archivo Excel';

  protected ProductsService $service;

  public function __construct(ProductsService $service)
  {
    parent::__construct();
    $this->service = $service;
  }

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $filePath = $this->argument('file') ?? storage_path('app/imports/products.xlsx');

    if (!file_exists($filePath)) {
      $this->error("El archivo {$filePath} no existe.");
      $this->info("Uso: php artisan import:products [ruta/al/archivo.xlsx]");
      $this->info("Por defecto busca en: storage/app/imports/products.xlsx");
      return 1;
    }

    $this->info("Leyendo datos desde: {$filePath}");

    try {
      $products = $this->getProductsFromExcel($filePath);

      if (empty($products)) {
        $this->error("No se encontraron datos para importar.");
        return 1;
      }

      $this->info("Total de registros a importar: " . count($products));

      // Modo de importación
      $mode = $this->option('mode') ?? 'update';

      // TODO: MODO RESET (Comentado para implementación futura)
      // if ($mode === 'reset') {
      //   // Validar que no existan relaciones con otras tablas
      //   // Si no hay relaciones, resetear el auto_increment y eliminar productos
      //   // $this->warn('ADVERTENCIA: El modo reset eliminará todos los productos y reseteará los IDs.');
      //   // if (!$this->confirm('¿Estás seguro de continuar con el modo RESET? Esta acción es irreversible.')) {
      //   //   $this->info('Importación cancelada.');
      //   //   return 0;
      //   // }
      //   // Implementación pendiente...
      //   $this->error('El modo reset aún no está implementado. Use --mode=update');
      //   return 1;
      // }

      $this->info("Modo de importación: {$mode}");

      if (!$this->confirm('¿Deseas continuar con la importación?')) {
        $this->info('Importación cancelada.');
        return 0;
      }

      $bar = $this->output->createProgressBar(count($products));
      $bar->start();

      $success = 0;
      $updated = 0;
      $created = 0;
      $errors = [];

      foreach ($products as $index => $productData) {
        try {
          // DEBUG: Mostrar el primer registro para ver qué datos llegan
          if ($index === 0) {
            $this->info("DEBUG - Primer registro:");
            $this->line(json_encode($productData, JSON_PRETTY_PRINT));
          }

          // Validar datos antes de importar
          $validator = Validator::make($productData, [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_category_id' => 'required|integer|exists:ap_masters,id',
            'brand_id' => 'required|integer|exists:ap_vehicle_brand,id',
            'unit_measurement_id' => 'required|integer|exists:unit_measurement,id',
            'ap_class_article_id' => 'required|integer|exists:ap_class_article,id',
          ]);

          if ($validator->fails()) {
            $errors[] = [
              'row' => $index + 2, // +2 porque +1 por índice base 0 y +1 por la fila de encabezados
              'code' => $productData['code'] ?? 'N/A',
              'error' => $validator->errors()->first()
            ];
            $bar->advance();
            continue;
          }

          // Construir dyn_code basado en category.code, brand.dyn_code y classArticle.id
          $dynCodeParts = [];

          // Obtener category code
          if (!empty($productData['product_category_id'])) {
            $category = ApMasters::find($productData['product_category_id']);
            if ($category && !empty($category->code)) {
              $dynCodeParts[] = $category->code;
            }
          }

          // Obtener brand dyn_code
          if (!empty($productData['brand_id'])) {
            $brand = ApVehicleBrand::find($productData['brand_id']);
            if ($brand && !empty($brand->dyn_code)) {
              $dynCodeParts[] = $brand->dyn_code;
            }
          }

          // Obtener classArticle id
          if (!empty($productData['ap_class_article_id'])) {
            $classArticle = ApClassArticle::find($productData['ap_class_article_id']);
            if ($classArticle) {
              $dynCodeParts[] = $classArticle->id;
            }
          }

          // Si no hay partes para el dyn_code, usar el código del producto
          $dynCode = !empty($dynCodeParts) ? implode('-', $dynCodeParts) : $productData['code'];

          // Preparar datos con valores por defecto
          $dataToStore = [
            'code' => $productData['code'],
            'dyn_code' => $dynCode . 'X', // Se agregará la X para que el servicio lo reemplace por el correlativo
            'name' => $productData['name'],
            'description' => $productData['description'] ?? null,
            'product_category_id' => $productData['product_category_id'],
            'brand_id' => $productData['brand_id'],
            'unit_measurement_id' => $productData['unit_measurement_id'],
            'ap_class_article_id' => $productData['ap_class_article_id'],
            'status' => 'ACTIVE',
            'current_stock' => 0,
            'minimum_stock' => 0,
            // Configuración de almacén por defecto
            'warehouses' => [
              [
                'warehouse_id' => 164, // CIX
                'initial_quantity' => 0,
                'minimum_stock' => 1,
                'maximum_stock' => 10,
              ],
              [
                'warehouse_id' => 165, // JAE
                'initial_quantity' => 0,
                'minimum_stock' => 1,
                'maximum_stock' => 10,
              ],
              [
                'warehouse_id' => 166, // PIU
                'initial_quantity' => 0,
                'minimum_stock' => 1,
                'maximum_stock' => 10,
              ],
              [
                'warehouse_id' => 167, // CAJ
                'initial_quantity' => 0,
                'minimum_stock' => 1,
                'maximum_stock' => 10,
              ]
            ],
          ];

          // Buscar si el producto ya existe por código
          $existingProduct = \App\Models\ap\postventa\gestionProductos\Products::where('code', $productData['code'])->first();

          if ($existingProduct) {
            // ACTUALIZAR: El producto ya existe
            $dataToStore['id'] = $existingProduct->id;

            // Obtener almacenes que ya tiene el producto
            $existingWarehouses = \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::where('product_id', $existingProduct->id)
              ->pluck('warehouse_id')
              ->toArray();

            // Filtrar solo los almacenes faltantes
            $warehousesToAdd = [];
            foreach ($dataToStore['warehouses'] as $warehouse) {
              if (!in_array($warehouse['warehouse_id'], $existingWarehouses)) {
                $warehousesToAdd[] = $warehouse;
              }
            }

            // Si hay almacenes faltantes, insertarlos
            if (!empty($warehousesToAdd)) {
              foreach ($warehousesToAdd as $warehouseData) {
                \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::create([
                  'product_id' => $existingProduct->id,
                  'warehouse_id' => $warehouseData['warehouse_id'],
                  'quantity' => $warehouseData['initial_quantity'] ?? 0,
                  'available_quantity' => $warehouseData['initial_quantity'] ?? 0,
                  'minimum_stock' => $warehouseData['minimum_stock'] ?? 0,
                  'maximum_stock' => $warehouseData['maximum_stock'] ?? null,
                ]);
              }
            }

            // No actualizar warehouses en el servicio (ya los manejamos aquí)
            unset($dataToStore['warehouses']);
            unset($dataToStore['current_stock']);
            unset($dataToStore['minimum_stock']);

            $this->service->update($dataToStore);
            $updated++;
          } else {
            // CREAR: El producto no existe
            $this->service->store($dataToStore);
            $created++;
          }

          $success++;
        } catch (\Exception $e) {
          $errors[] = [
            'row' => $index + 2,
            'code' => $productData['code'] ?? 'N/A',
            'error' => $e->getMessage()
          ];
        }

        $bar->advance();
      }

      $bar->finish();
      $this->newLine(2);

      // Mostrar resumen
      $this->info("Importación completada!");
      $this->info("Registros exitosos: {$success}");
      $this->info("  - Productos actualizados: {$updated}");
      $this->info("  - Productos creados: {$created}");
      $this->error("Registros con errores: " . count($errors));

      if (!empty($errors)) {
        $this->newLine();
        $this->error("Detalle de errores:");
        foreach ($errors as $error) {
          $this->line("  - Fila {$error['row']} (Código: {$error['code']}): {$error['error']}");
        }
      }

      return 0;
    } catch (\Exception $e) {
      $this->error("Error al leer el archivo: " . $e->getMessage());
      return 1;
    }
  }

  /**
   * Leer datos desde archivo Excel
   */
  private function getProductsFromExcel(string $filePath): array
  {
    $products = [];
    $skippedRows = 0;

    $data = Excel::toArray(new class implements ToCollection {
      public function collection(Collection $collection)
      {
        //
      }
    }, $filePath);

    if (empty($data) || empty($data[0])) {
      return [];
    }

    $rows = $data[0]; // Primera hoja

    if (count($rows) < 2) {
      return [];
    }

    // La primera fila contiene los encabezados
    $headers = $rows[0];

    // Convertir headers a array simple (en caso de que sea una colección)
    $headers = $headers instanceof Collection ? $headers->toArray() : $headers;

    // DEBUG: Mostrar los headers del Excel
    $this->info("DEBUG - Headers del Excel:");
    $this->line(json_encode($headers, JSON_PRETTY_PRINT));

    // Procesar cada fila de datos (omitir la primera que son los encabezados)
    for ($i = 1; $i < count($rows); $i++) {
      $row = $rows[$i];
      $row = $row instanceof Collection ? $row->toArray() : $row;

      // Combinar encabezados con valores
      if (count($row) === count($headers)) {
        $rowData = array_combine($headers, $row);

        // DEBUG: Mostrar la primera fila de datos
        if ($i === 1) {
          $this->info("DEBUG - Primera fila de datos (row 2 del Excel):");
          $this->line(json_encode($rowData, JSON_PRETTY_PRINT));
        }

        // Mapear campos del Excel a los campos esperados
        // IMPORTANTE: Ahora usamos 'codigo_producto' tal cual, no 'code_clean'

        // Limpiar y convertir valores a sus tipos correctos
        $code = isset($rowData['codigo_producto']) ? trim($rowData['codigo_producto']) : null;
        $name = isset($rowData['name']) ? trim($rowData['name']) : null;

        // Convertir IDs a enteros (trim primero para remover espacios)
        $categoryId = isset($rowData['product_category_id']) && trim($rowData['product_category_id']) !== ''
          ? (int)trim($rowData['product_category_id'])
          : null;

        $brandId = isset($rowData['brand_id']) && trim($rowData['brand_id']) !== ''
          ? (int)trim($rowData['brand_id'])
          : null;

        $unitMeasurementId = isset($rowData['unit_measurement_id']) && trim($rowData['unit_measurement_id']) !== ''
          ? (int)trim($rowData['unit_measurement_id'])
          : null;

        $classArticleId = isset($rowData['ap_class_article_id']) && trim($rowData['ap_class_article_id']) !== ''
          ? (int)trim($rowData['ap_class_article_id'])
          : null;

        $mappedData = [
          'code' => $code,
          'name' => $name,
          'description' => $name, // Usar name como descripción
          'product_category_id' => $categoryId,
          'brand_id' => $brandId,
          'unit_measurement_id' => $unitMeasurementId,
          'ap_class_article_id' => $classArticleId,
        ];

        // Solo agregar si tiene todos los campos requeridos
        if (!empty($mappedData['code'])
          && !empty($mappedData['name'])
          && $mappedData['product_category_id'] !== null
          && $mappedData['brand_id'] !== null
          && $mappedData['unit_measurement_id'] !== null
          && $mappedData['ap_class_article_id'] !== null
        ) {
          $products[] = $mappedData;
        } else {
          $skippedRows++;
        }
      }
    }

    if ($skippedRows > 0) {
      $this->warn("Se omitieron {$skippedRows} filas por tener campos vacíos o incompletos.");
    }

    return $products;
  }
}

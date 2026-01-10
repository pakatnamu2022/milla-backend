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
  protected $signature = 'import:products {file?}';

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

      if (!$this->confirm('¿Deseas continuar con la importación?')) {
        $this->info('Importación cancelada.');
        return 0;
      }

      $bar = $this->output->createProgressBar(count($products));
      $bar->start();

      $success = 0;
      $errors = [];

      foreach ($products as $index => $productData) {
        try {
          // Validar datos antes de importar
          $validator = Validator::make($productData, [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_category_id' => 'nullable|integer|exists:ap_masters,id',
            'brand_id' => 'nullable|integer|exists:ap_vehicle_brand,id',
            'unit_measurement_id' => 'required|integer|exists:unit_measurement,id',
            'ap_class_article_id' => 'nullable|integer|exists:ap_class_article,id',
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
            'nubefac_code' => $productData['code'], // Se usará el mismo código
            'name' => $productData['name'],
            'description' => $productData['description'] ?? null,
            'product_category_id' => $productData['product_category_id'] ?? null,
            'brand_id' => $productData['brand_id'] ?? null,
            'unit_measurement_id' => $productData['unit_measurement_id'],
            'ap_class_article_id' => $productData['ap_class_article_id'] ?? null,
            'cost_price' => 0,
            'sale_price' => 0,
            'tax_rate' => 18,
            'is_taxable' => 1,
            'sunat_code' => '',
            'status' => 'ACTIVE',
            'current_stock' => 0,
            'minimum_stock' => 0,
            // Configuración de almacén por defecto
            'warehouses' => [
              [
                'warehouse_id' => 164,
                'initial_quantity' => 0,
                'minimum_stock' => 1,
                'maximum_stock' => 10,
              ]
            ],
          ];

          // Llamar al servicio para guardar el producto
          $this->service->store($dataToStore);
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

    // Procesar cada fila de datos (omitir la primera que son los encabezados)
    for ($i = 1; $i < count($rows); $i++) {
      $row = $rows[$i];
      $row = $row instanceof Collection ? $row->toArray() : $row;

      // Combinar encabezados con valores
      if (count($row) === count($headers)) {
        $products[] = array_combine($headers, $row);
      }
    }

    return $products;
  }
}

<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UpdateProductNamesFromExcel extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'update:product-names {file?}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Actualizar nombres y descripciones de productos desde archivo Excel';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $filePath = $this->argument('file') ?? storage_path('app/imports/renombrar_productos.xlsx');

    if (!file_exists($filePath)) {
      $this->error("El archivo {$filePath} no existe.");
      $this->info("Uso: php artisan update:product-names [ruta/al/archivo.xlsx]");
      $this->info("Por defecto busca en: storage/app/imports/renombrar_productos.xlsx");
      return 1;
    }

    $this->info("Leyendo datos desde: {$filePath}");

    try {
      $productsData = $this->getProductsFromExcel($filePath);

      if (empty($productsData)) {
        $this->error("No se encontraron datos para actualizar.");
        return 1;
      }

      $this->info("Total de registros a procesar: " . count($productsData));

      if (!$this->confirm('¿Deseas continuar con la actualización?')) {
        $this->info('Actualización cancelada.');
        return 0;
      }

      $bar = $this->output->createProgressBar(count($productsData));
      $bar->start();

      $success = 0;
      $notFound = 0;
      $errors = [];

      foreach ($productsData as $index => $data) {
        try {
          $code = $data['code'];
          $name = $data['name'];
          $description = $data['description'];

          // Buscar el producto por código
          $product = Products::where('code', $code)->first();

          if ($product) {
            // Actualizar nombre y descripción
            $product->name = $name;
            $product->description = $description;
            $product->save();

            $success++;
          } else {
            $notFound++;
            $errors[] = [
              'row' => $index + 2, // +2 porque +1 por índice base 0 y +1 por la fila de encabezados
              'code' => $code,
              'error' => 'Producto no encontrado en la base de datos'
            ];
          }
        } catch (\Exception $e) {
          $errors[] = [
            'row' => $index + 2,
            'code' => $data['code'] ?? 'N/A',
            'error' => $e->getMessage()
          ];
        }

        $bar->advance();
      }

      $bar->finish();
      $this->newLine(2);

      // Mostrar resumen
      $this->info("Actualización completada!");
      $this->info("Productos actualizados: {$success}");
      $this->warn("Productos no encontrados: {$notFound}");

      if (!empty($errors)) {
        $this->newLine();
        $this->error("Detalle de productos no encontrados o con errores:");
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

    // DEBUG: Mostrar el contenido de las primeras filas
    $this->info("DEBUG - Primeras filas del Excel:");
    for ($i = 0; $i < min(3, count($rows)); $i++) {
      $row = $rows[$i];
      $row = $row instanceof Collection ? $row->toArray() : $row;
      $this->line("Fila " . ($i + 1) . ": " . json_encode($row));
    }

    // Procesar cada fila de datos
    // Asumimos que la primera fila puede ser encabezados o datos
    // Vamos a detectar si la primera fila tiene "Artículo" y "Descripción"
    $firstRow = $rows[0] instanceof Collection ? $rows[0]->toArray() : $rows[0];
    $hasHeaders = false;

    // Detectar si la primera fila contiene encabezados
    if (isset($firstRow[0]) && isset($firstRow[1])) {
      $firstRowCol0 = strtolower(trim($firstRow[0]));
      $firstRowCol1 = strtolower(trim($firstRow[1]));

      if (
        (strpos($firstRowCol0, 'articulo') !== false || strpos($firstRowCol0, 'código') !== false || strpos($firstRowCol0, 'codigo') !== false) &&
        (strpos($firstRowCol1, 'descripcion') !== false || strpos($firstRowCol1, 'descripción') !== false)
      ) {
        $hasHeaders = true;
      }
    }

    $startRow = $hasHeaders ? 1 : 0;

    $this->info("DEBUG - ¿Tiene encabezados? " . ($hasHeaders ? 'Sí' : 'No'));
    $this->info("DEBUG - Comenzando desde la fila: " . ($startRow + 1));

    // Procesar cada fila de datos
    for ($i = $startRow; $i < count($rows); $i++) {
      $row = $rows[$i];
      $row = $row instanceof Collection ? $row->toArray() : $row;

      // Columna A (índice 0) = Artículo (código)
      // Columna B (índice 1) = Descripción
      $code = isset($row[0]) ? trim($row[0]) : null;
      $description = isset($row[1]) ? trim($row[1]) : null;

      // Validar que el código no esté vacío
      if (!empty($code)) {
        $products[] = [
          'code' => $code,
          'name' => $description ?? $code, // Si no hay descripción, usar el código como nombre
          'description' => $description ?? $code, // Si no hay descripción, usar el código
        ];
      } else {
        $skippedRows++;
      }
    }

    if ($skippedRows > 0) {
      $this->warn("Se omitieron {$skippedRows} filas por tener el código vacío.");
    }

    return $products;
  }
}
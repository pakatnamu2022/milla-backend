<?php

namespace App\Console\Commands\ap\postVenta;

use App\Http\Services\ap\comercial\VehiclesService;
use App\Http\Services\DocumentValidation\DocumentValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportVehiclePlates extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'import:vehicle-plates {file?} {--sede_id=13 : ID de la sede a usar para todos los vehículos}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Importar vehículos a partir de un listado de placas (PLACAS.xlsx), validando cada placa contra la API externa';

  protected VehiclesService $vehiclesService;
  protected DocumentValidationService $documentValidationService;

  public function __construct(VehiclesService $vehiclesService, DocumentValidationService $documentValidationService)
  {
    parent::__construct();
    $this->vehiclesService = $vehiclesService;
    $this->documentValidationService = $documentValidationService;
  }

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $filePath = $this->argument('file') ?? storage_path('app/imports/PLACAS.xlsx');

    if (!file_exists($filePath)) {
      $this->error("El archivo {$filePath} no existe.");
      $this->info("Uso: php artisan import:vehicle-plates [ruta/al/archivo.xlsx]");
      $this->info("Por defecto busca en: storage/app/imports/PLACAS.xlsx");
      return 1;
    }

    $sedeId = (int)$this->option('sede_id');

    $this->info("Leyendo datos desde: {$filePath}");

    $rows = $this->getPlatesFromExcel($filePath);

    if (empty($rows)) {
      $this->error("No se encontraron placas para importar.");
      return 1;
    }

    $this->info("Total de placas a procesar: " . count($rows));

    if (!$this->confirm('¿Deseas continuar con la importación?')) {
      $this->info('Importación cancelada.');
      return 0;
    }

    $bar = $this->output->createProgressBar(count($rows));
    $bar->start();

    $validCount = 0;
    $correctedCount = 0;
    $duplicates = [];
    $errors = [];
    $seenPlates = [];

    foreach ($rows as $row) {
      $plate = $row['plate'];
      $excelRow = $row['excel_row'];

      if (isset($seenPlates[$plate])) {
        $duplicates[] = ['row' => $excelRow, 'plate' => $plate];
        $bar->advance();
        continue;
      }
      $seenPlates[$plate] = true;

      $result = $this->documentValidationService->validateDocument('plate', $plate, [], true);
      $info = $result['data'] ?? null;

      // Verificar si la API respondió correctamente
      $apiSuccess = !empty($result['success']);
      $hasVin = !empty($info['vin']);
      $hasEngineNumber = !empty($info['engine_number']);

      // Solo marcar como corrección si:
      // - La API falló, o
      // - No tiene VIN, o
      // - No tiene ni VIN ni número de motor
      $isCorrection = !$apiSuccess || !$hasVin || (!$hasVin && !$hasEngineNumber);

      if ($isCorrection) {
        $vehicleData = [
          'plate' => $plate,
          'vin' => $this->generateCorrectionVin($row['item']),
          'engine_number' => $this->generateCorrectionEngineNumber($row['item']),
          'sede_id' => $sedeId,
        ];
      } else {
        // Si tiene VIN pero no tiene número de motor, usar el VIN como número de motor
        $engineNumber = $hasEngineNumber
          ? strtoupper(trim($info['engine_number']))
          : strtoupper(trim($info['vin']));

        $vehicleData = [
          'plate' => $plate,
          'vin' => strtoupper(trim($info['vin'])),
          'engine_number' => $engineNumber,
          'sede_id' => $sedeId,
        ];
      }

      $validator = Validator::make($vehicleData, [
        'plate' => 'sometimes|nullable|string|max:10|unique:ap_vehicles,plate',
        'vin' => 'required|string|max:20|min:17|unique:ap_vehicles,vin',
        'engine_number' => 'required|string|max:50|unique:ap_vehicles,engine_number',
        'sede_id' => 'required|integer|exists:config_sede,id',
      ]);

      if ($validator->fails()) {
        $errors[] = [
          'row' => $excelRow,
          'plate' => $plate,
          'error' => $validator->errors()->first(),
        ];
        $bar->advance();
        continue;
      }

      try {
        $this->vehiclesService->storeReplacement($vehicleData);
        if ($isCorrection) {
          $correctedCount++;
        } else {
          $validCount++;
        }
      } catch (Throwable $e) {
        $errors[] = [
          'row' => $excelRow,
          'plate' => $plate,
          'error' => $e->getMessage(),
        ];
      }

      $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);

    $this->info("Importación completada!");
    $this->info("Placas válidas creadas: {$validCount}");
    $this->info("Placas para corregir creadas: {$correctedCount}");
    $this->info("Placas duplicadas omitidas: " . count($duplicates));
    $this->error("Registros con errores: " . count($errors));

    if (!empty($duplicates)) {
      $this->newLine();
      $this->warn("Placas duplicadas omitidas:");
      foreach ($duplicates as $duplicate) {
        $this->line("  - Fila {$duplicate['row']} (Placa: {$duplicate['plate']})");
      }
    }

    if (!empty($errors)) {
      $this->newLine();
      $this->error("Detalle de errores:");
      foreach ($errors as $error) {
        $this->line("  - Fila {$error['row']} (Placa: {$error['plate']}): {$error['error']}");
      }
    }

    return 0;
  }

  /**
   * Leer placas desde archivo Excel (columna A = ITEM, columna B = MATRICULA)
   */
  private function getPlatesFromExcel(string $filePath): array
  {
    $plates = [];

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

    // Omitir la primera fila (encabezados: ITEM, MATRICULA)
    for ($i = 1; $i < count($rows); $i++) {
      $row = $rows[$i];
      $row = $row instanceof Collection ? $row->toArray() : $row;

      $item = $row[0] ?? ($i + 1);
      $plateRaw = $row[1] ?? null;
      $plate = is_string($plateRaw) || is_numeric($plateRaw) ? strtoupper(trim((string)$plateRaw)) : null;

      if (empty($plate)) {
        continue;
      }

      $plates[] = [
        'excel_row' => $i + 1,
        'item' => $item,
        'plate' => $plate,
      ];
    }

    return $plates;
  }

  /**
   * Genera una placa única de tipo "para corregir" (máx. 10 caracteres)
   */
  private function generateCorrectionPlate(mixed $item): string
  {
    return 'COR' . str_pad((string)$item, 6, '0', STR_PAD_LEFT);
  }

  /**
   * Genera un VIN único de 17 caracteres para registros "para corregir"
   */
  private function generateCorrectionVin(mixed $item): string
  {
    $base = 'COR' . str_pad((string)$item, 6, '0', STR_PAD_LEFT);
    return $base . strtoupper(Str::random(17 - strlen($base)));
  }

  /**
   * Genera un número de motor único para registros "para corregir"
   */
  private function generateCorrectionEngineNumber(mixed $item): string
  {
    return 'MOT' . str_pad((string)$item, 6, '0', STR_PAD_LEFT) . strtoupper(Str::random(6));
  }
}

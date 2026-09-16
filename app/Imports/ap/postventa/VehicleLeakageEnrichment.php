<?php

namespace App\Imports\ap\postventa;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\postventa\taller\ApWorkOrder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;

class VehicleLeakageEnrichment
{
  private array $results = [
    'processed' => 0,
    'enriched'  => 0,
    'not_found' => 0,
    'errors'    => [],
  ];

  private const CHUNK_SIZE = 50;
  private const HEADER_ROW = 3; // Fila donde están las cabeceras
  private const NEW_COLUMNS = [
    'Nombres',
    'Celular',
    'Correo',
    'Tipo Ultimo Servicio'
  ];

  /**
   * Procesa el archivo Excel agregando las 4 columnas enriquecidas
   *
   * @param string $filePath Ruta del archivo Excel a procesar
   * @return array Resultados del procesamiento
   * @throws \Exception
   */
  public function process(string $filePath): array
  {
    try {
      // Aumentar límite de memoria a 4GB para todo el proceso
      ini_set('memory_limit', '4G');

      Log::info('Iniciando procesamiento de archivo Excel', [
        'file' => $filePath,
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage_before' => $this->formatBytes(memory_get_usage(true))
      ]);

      // Cargar el archivo Excel con configuración optimizada
      $reader = IOFactory::createReader('Xlsx');
      $reader->setReadDataOnly(false);

      $spreadsheet = $reader->load($filePath);
      $sheet = $spreadsheet->getActiveSheet();

      Log::info('Archivo cargado en memoria', [
        'memory_usage_after_load' => $this->formatBytes(memory_get_usage(true))
      ]);

      // Obtener la última columna y fila
      $highestColumn = $sheet->getHighestColumn();
      $highestRow = $sheet->getHighestRow();
      $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

      Log::info('Dimensiones del archivo', [
        'rows' => $highestRow,
        'columns' => $highestColumnIndex
      ]);

      // Buscar la columna "Rango" que contiene el VIN
      $vinColumnIndex = $this->findVinColumn($sheet, $highestColumnIndex);

      if (!$vinColumnIndex) {
        throw new \Exception('No se encontró la columna "Rango" con los VIN en el archivo Excel');
      }

      // Agregar los encabezados de las nuevas columnas
      $this->addNewColumnHeaders($sheet, $highestColumnIndex);

      // Procesar las filas por chunks para optimizar memoria
      $this->processRowsInChunks($sheet, $highestRow, $vinColumnIndex, $highestColumnIndex);

      Log::info('Procesamiento completado, guardando archivo', [
        'memory_usage_before_save' => $this->formatBytes(memory_get_usage(true))
      ]);

      // Guardar el archivo modificado
      $writer = new Xlsx($spreadsheet);
      $writer->save($filePath);

      // Liberar memoria
      $spreadsheet->disconnectWorksheets();
      unset($spreadsheet, $writer, $reader);
      gc_collect_cycles();

      Log::info('Archivo guardado y memoria liberada', [
        'memory_usage_after' => $this->formatBytes(memory_get_usage(true))
      ]);

      return $this->results;

    } catch (\Exception $e) {
      Log::error('Error en VehicleLeakageEnrichment: ' . $e->getMessage(), [
        'file' => $filePath,
        'memory_usage' => $this->formatBytes(memory_get_usage(true)),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  /**
   * Formatea bytes a formato legible
   *
   * @param int $bytes
   * @return string
   */
  private function formatBytes(int $bytes): string
  {
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
  }

  /**
   * Busca la columna que contiene el VIN en el encabezado (fila 3)
   * Busca específicamente "Rango[vin]" o variaciones similares
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   * @param int $maxColumnIndex
   * @return int|null
   */
  private function findVinColumn($sheet, int $maxColumnIndex): ?int
  {
    for ($col = 1; $col <= $maxColumnIndex; $col++) {
      $cellValue = $sheet->getCellByColumnAndRow($col, self::HEADER_ROW)->getValue();

      if (!$cellValue) {
        continue;
      }

      $cellValue = trim($cellValue);

      // Buscar "Rango[vin]" o variaciones (case insensitive)
      if (stripos($cellValue, 'Rango[vin]') !== false ||
          stripos($cellValue, 'vin') !== false) {
        Log::info("Columna VIN encontrada: '{$cellValue}' en columna {$col}");
        return $col;
      }
    }

    // Si no se encuentra, registrar todos los encabezados para debug
    $headers = [];
    for ($col = 1; $col <= $maxColumnIndex; $col++) {
      $headers[] = $sheet->getCellByColumnAndRow($col, self::HEADER_ROW)->getValue();
    }
    Log::error('No se encontró columna VIN. Encabezados encontrados: ' . json_encode($headers));

    return null;
  }

  /**
   * Agrega los encabezados de las nuevas columnas en la fila 3
   * Copia el formato de las columnas existentes
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   * @param int $startColumnIndex
   */
  private function addNewColumnHeaders($sheet, int $startColumnIndex): void
  {
    // Obtener el estilo de la primera columna de encabezado como referencia
    $referenceCell = $sheet->getCellByColumnAndRow(1, self::HEADER_ROW);
    $referenceStyle = $referenceCell->getStyle();

    $columnIndex = $startColumnIndex + 1;

    foreach (self::NEW_COLUMNS as $columnName) {
      $cell = $sheet->getCellByColumnAndRow($columnIndex, self::HEADER_ROW);

      // Establecer el valor
      $cell->setValue($columnName);

      // Copiar el estilo de la celda de referencia
      $sheet->duplicateStyle($referenceStyle, Coordinate::stringFromColumnIndex($columnIndex) . self::HEADER_ROW);

      $columnIndex++;
    }
  }

  /**
   * Procesa las filas del Excel por chunks
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   * @param int $highestRow
   * @param int $vinColumnIndex
   * @param int $startColumnIndex
   */
  private function processRowsInChunks($sheet, int $highestRow, int $vinColumnIndex, int $startColumnIndex): void
  {
    $processedChunks = 0;

    // Empezar desde la fila 4 (después de las cabeceras en fila 3)
    $dataStartRow = self::HEADER_ROW + 1;

    for ($startRow = $dataStartRow; $startRow <= $highestRow; $startRow += self::CHUNK_SIZE) {
      $endRow = min($startRow + self::CHUNK_SIZE - 1, $highestRow);

      // Recolectar VINs del chunk actual
      $vins = [];
      for ($row = $startRow; $row <= $endRow; $row++) {
        $vin = $sheet->getCellByColumnAndRow($vinColumnIndex, $row)->getValue();
        if ($vin) {
          $vins[$row] = trim($vin);
        }
      }

      if (empty($vins)) {
        continue;
      }

      // Cargar datos de vehículos y clientes en batch para este chunk
      $vehiclesData = $this->loadVehiclesDataInBatch(array_values($vins));

      // Procesar cada fila del chunk
      foreach ($vins as $rowNumber => $vin) {
        $this->processRow($sheet, $rowNumber, $vin, $vehiclesData, $startColumnIndex);
      }

      // Liberar memoria del chunk procesado inmediatamente
      unset($vins, $vehiclesData);

      $processedChunks++;

      // Forzar recolección de basura cada 5 chunks (cada 250 filas con CHUNK_SIZE=50)
      if ($processedChunks % 5 === 0) {
        gc_collect_cycles();

        Log::info('Progreso del procesamiento', [
          'rows_processed' => $this->results['processed'],
          'rows_enriched' => $this->results['enriched'],
          'rows_not_found' => $this->results['not_found'],
          'memory_usage' => $this->formatBytes(memory_get_usage(true)),
          'progress_percent' => round(($startRow / $highestRow) * 100, 2) . '%'
        ]);
      }
    }
  }

  /**
   * Carga datos de vehículos y sus relaciones en batch
   *
   * @param array $vins
   * @return array
   */
  private function loadVehiclesDataInBatch(array $vins): array
  {
    $vehicles = Vehicles::whereIn('vin', $vins)
      ->with([
        'customer' => function ($query) {
          $query->select('id', 'full_name', 'phone', 'email');
        }
      ])
      ->get()
      ->keyBy('vin');

    $vehicleIds = $vehicles->pluck('id')->toArray();

    // Cargar últimas órdenes de trabajo para todos los vehículos del batch
    $workOrders = [];
    if (!empty($vehicleIds)) {
      $workOrders = ApWorkOrder::whereIn('vehicle_id', $vehicleIds)
        ->whereNull('deleted_at')
        ->where('status_id', '!=', ApMasters::CANCELED_WORK_ORDER_ID)
        ->with(['items.typePlanning' => function ($query) {
          $query->select('id', 'description');
        }])
        ->get()
        ->groupBy('vehicle_id')
        ->map(function ($orders) {
          // Ordenar por fecha de apertura descendente y tomar la primera (más reciente)
          return $orders->sortByDesc('opening_date')->first();
        });
    }

    return [
      'vehicles' => $vehicles,
      'workOrders' => $workOrders,
    ];
  }

  /**
   * Procesa una fila individual
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   * @param int $row
   * @param string $vin
   * @param array $vehiclesData
   * @param int $startColumnIndex
   */
  private function processRow($sheet, int $row, string $vin, array $vehiclesData, int $startColumnIndex): void
  {
    try {
      $this->results['processed']++;

      $vehicle = $vehiclesData['vehicles']->get($vin);

      if (!$vehicle) {
        $this->setRowValues($sheet, $row, $startColumnIndex, ['-', '-', '-', '-']);
        $this->results['not_found']++;
        return;
      }

      // Obtener datos del cliente
      $customer = $vehicle->customer;
      $nombres = $customer ? ($customer->full_name ?? '-') : '-';
      $celular = $customer ? ($customer->phone ?? '-') : '-';
      $correo = $customer ? ($customer->email ?? '-') : '-';

      // Obtener última orden de trabajo
      $workOrder = $vehiclesData['workOrders']->get($vehicle->id);
      $tipoUltimoServicio = '-';

      if ($workOrder) {
        $firstItem = $workOrder->items->first();
        if ($firstItem && $firstItem->typePlanning) {
          $tipoUltimoServicio = $firstItem->typePlanning->description ?? '-';
        }
      }

      // Escribir los valores en las nuevas columnas
      $this->setRowValues($sheet, $row, $startColumnIndex, [
        $nombres,
        $celular,
        $correo,
        $tipoUltimoServicio
      ]);

      $this->results['enriched']++;

    } catch (\Exception $e) {
      $this->results['errors'][] = "Fila {$row} (VIN: {$vin}): " . $e->getMessage();
      $this->setRowValues($sheet, $row, $startColumnIndex, ['-', '-', '-', '-']);
    }
  }

  /**
   * Establece los valores en las columnas de la fila
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   * @param int $row
   * @param int $startColumnIndex
   * @param array $values
   */
  private function setRowValues($sheet, int $row, int $startColumnIndex, array $values): void
  {
    $columnIndex = $startColumnIndex + 1;

    foreach ($values as $value) {
      $sheet->setCellValueByColumnAndRow($columnIndex, $row, $value);
      $columnIndex++;
    }
  }

  /**
   * Obtiene los resultados del procesamiento
   *
   * @return array
   */
  public function getResults(): array
  {
    return $this->results;
  }
}
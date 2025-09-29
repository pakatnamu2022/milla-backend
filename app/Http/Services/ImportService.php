<?php

namespace App\Http\Services;

use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Http\UploadedFile;

class ImportService
{
  public function importFromExcel(UploadedFile $file, $importClass): array
  {
    try {
      $import = new $importClass();
      $collection = Excel::toCollection($import, $file);

      // Procesar la colección y convertir a JSON
      $processedData = [];

      foreach ($collection->first() as $row) {
        if (method_exists($import, 'transformRow')) {
          $processedData[] = $import->transformRow($row->toArray());
        } else {
          $processedData[] = $row->toArray();
        }
      }

      return [
        'success' => true,
        'data' => $processedData,
        'total_rows' => count($processedData),
        'message' => 'Archivo importado exitosamente'
      ];

    } catch (Exception $e) {
      return [
        'success' => false,
        'data' => [],
        'error' => $e->getMessage(),
        'message' => 'Error al procesar el archivo'
      ];
    }
  }

  public function validateFile(UploadedFile $file, array $allowedExtensions = ['xlsx', 'xls', 'csv']): true
  {
    $extension = $file->getClientOriginalExtension();

    if (!in_array(strtolower($extension), $allowedExtensions)) {
      throw new Exception('Formato de archivo no permitido. Permitidos: ' . implode(', ', $allowedExtensions));
    }

    if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
      throw new Exception('El archivo es demasiado grande. Máximo 10MB permitido.');
    }

    return true;
  }

  public function processImportData(array $data, $modelClass): array
  {
    $model = app($modelClass);
    $results = [
      'processed' => 0,
      'errors' => 0,
      'error_details' => []
    ];

    foreach ($data as $index => $rowData) {
      try {
        if (method_exists($model, 'validateImportRow')) {
          $model->validateImportRow($rowData);
        }

        $model::create($rowData);
        $results['processed']++;

      } catch (Exception $e) {
        $results['errors']++;
        $results['error_details'][] = [
          'row' => $index + 1,
          'error' => $e->getMessage(),
          'data' => $rowData
        ];
      }
    }

    return $results;
  }
}

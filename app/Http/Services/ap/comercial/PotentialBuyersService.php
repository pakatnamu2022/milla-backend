<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PotentialBuyersResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ImportService;
use App\Imports\ap\comercial\PotentialBuyersDercoImport;
use App\Imports\ap\comercial\PotentialBuyersSocialNetworksImport;
use App\Jobs\ValidatePotentialBuyersDocuments;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\comercial\PotentialBuyers;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PotentialBuyersService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PotentialBuyers::class,
      $request,
      PotentialBuyers::filters,
      PotentialBuyers::sorts,
      PotentialBuyersResource::class,
    );
  }

  public function find($id)
  {
    $businessPartner = PotentialBuyers::where('id', $id)->first();
    if (!$businessPartner) {
      throw new Exception('Cliente potencial no encontrado');
    }
    return $businessPartner;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      if ($data['type'] === 'VISITA') {
        $data['registration_date'] = now();
      }

      $TypeDocument = ApCommercialMasters::findOrFail($data['document_type_id']);
      $NumCharDoc = strlen($data['num_doc']);
      if ($TypeDocument->code != $NumCharDoc) {
        throw new Exception("El número de documento debe tener {$TypeDocument->code} caracteres para el tipo de documento seleccionado");
      }

      $businessPartner = PotentialBuyers::create($data);
      DB::commit();
      return new PotentialBuyersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new PotentialBuyersResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $TypeDocument = ApCommercialMasters::findOrFail($data['document_type_id']);
      $NumCharDoc = strlen($data['num_doc']);
      if ($TypeDocument->code != $NumCharDoc) {
        throw new Exception("El número de documento debe tener {$TypeDocument->code} caracteres para el tipo de documento seleccionado");
      }

      $businessPartner = $this->find($data['id']);
      $businessPartner->update($data);
      DB::commit();
      return new PotentialBuyersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);
      $businessPartner->delete();
      DB::commit();
      return response()->json(['message' => 'Cliente potencial eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function importFromExcelDerco(UploadedFile $file)
  {
    DB::beginTransaction();
    try {
      // Validar que el archivo no sea nulo
      if (!$file || !$file->isValid()) {
        return [
          'success' => false,
          'message' => 'Archivo no válido o no encontrado',
          'error' => 'El archivo enviado no es válido'
        ];
      }

      $importService = new ImportService();

      // Validar archivo
      $importService->validateFile($file);

      // Importar datos del Excel
      $importResult = $importService->importFromExcel($file, PotentialBuyersDercoImport::class);

      if (!$importResult['success']) {
        return [
          'success' => false,
          'message' => $importResult['message'],
          'error' => $importResult['error']
        ];
      }

      $totalRows = count($importResult['data']);
      $imported = 0;
      $duplicated = 0;
      $errors = 0;
      $errorDetails = [];
      $duplicatedRecords = [];
      $createdIds = [];

      // Procesar los datos importados con validación de duplicados y longitud de documento
      foreach ($importResult['data'] as $index => $rowData) {
        try {
          // Verificar si ya existe el DNI en el mes actual
          $exists = PotentialBuyers::whereBetween('registration_date', [
            now()->subMonth()->startOfMonth(),
            now()->endOfMonth()
          ])
            ->whereIn('use', [0, 1]) // Considerar solo use 0 y 1 (recien subido o asignado, 2 = descartado)
            ->where('num_doc', $rowData['num_doc'])
            ->exists();

          if ($exists) {
            $duplicated++;
            $duplicatedRecords[] = [
              'row' => $index + 1,
              'num_doc' => $rowData['num_doc'],
              'full_name' => $rowData['full_name'] ?? 'N/A'
            ];
            continue;
          }

          // Validar longitud del documento y asignar status_num_doc
          $numDoc = trim($rowData['num_doc']);
          $docLength = strlen($numDoc);
          $documentTypeId = $rowData['document_type_id'];

          // Inicializar status_num_doc
          $statusNumDoc = 'PENDIENTE';
          $countDigitTypeDoc = (int)ApCommercialMasters::find($documentTypeId)->code;

          // Validar si la longitud del documento no coincide con el tipo
          if ($docLength != $countDigitTypeDoc) {
            $statusNumDoc = 'ERRADO';
          }

          // Agregar status_num_doc al array de datos
          $rowData['status_num_doc'] = $statusNumDoc;

          // Crear el registro
          $buyer = PotentialBuyers::create($rowData);
          $createdIds[] = $buyer->id;
          $imported++;

        } catch (Exception $e) {
          $errors++;
          $errorDetails[] = [
            'row' => $index + 1,
            'error' => $e->getMessage(),
            'data' => $rowData
          ];
        }
      }

      DB::commit();

      // Despachar un job individual por cada registro creado
      foreach ($createdIds as $buyerId) {
        ValidatePotentialBuyersDocuments::dispatch([$buyerId]);
      }

      // Construir mensaje descriptivo
      $message = "Importación completada: {$imported} de {$totalRows} registros importados exitosamente";
      if ($duplicated > 0) {
        $message .= ", {$duplicated} ya están registrados en el mes actual";
      }
      if ($errors > 0) {
        $message .= ", {$errors} con errores";
      }

      return [
        'success' => true,
        'message' => $message,
        'summary' => [
          'total_rows' => $totalRows,
          'imported' => $imported,
          'duplicated' => $duplicated,
          'errors' => $errors,
          'duplicated_records' => $duplicatedRecords,
          'error_details' => $errorDetails
        ]
      ];

    } catch (Exception $e) {
      DB::rollBack();
      return [
        'success' => false,
        'message' => 'Error en la importación',
        'error' => $e->getMessage()
      ];
    }
  }

  public function importFromExcelSocialNetworks(UploadedFile $file): array
  {
    DB::beginTransaction();
    try {
      // Validar que el archivo no sea nulo
      if (!$file || !$file->isValid()) {
        return [
          'success' => false,
          'message' => 'Archivo no válido o no encontrado',
          'error' => 'El archivo enviado no es válido'
        ];
      }

      $importService = new ImportService();

      // Validar archivo
      $importService->validateFile($file);

      // Importar datos del Excel usando PotentialBuyersSocialNetworksImport
      $importResult = $importService->importFromExcel($file, PotentialBuyersSocialNetworksImport::class);

      if (!$importResult['success']) {
        return [
          'success' => false,
          'message' => $importResult['message'],
          'error' => $importResult['error']
        ];
      }

      $totalRows = count($importResult['data']);
      $imported = 0;
      $duplicated = 0;
      $errors = 0;
      $errorDetails = [];
      $duplicatedRecords = [];
      $createdIds = [];

      // Procesar los datos importados con validación de duplicados y longitud de documento
      foreach ($importResult['data'] as $index => $rowData) {
        try {
          // Verificar si ya existe el documento en el mes actual
          $exists = PotentialBuyers::whereBetween('registration_date', [
            now()->subMonth()->startOfMonth(),
            now()->endOfMonth()
          ])
            ->whereIn('use', [0, 1]) // Considerar solo use 0 y 1 (recien subido o asignado, 3 = descartado)
            ->where('num_doc', $rowData['num_doc'])
            ->exists();

          if ($exists) {
            $duplicated++;
            $duplicatedRecords[] = [
              'row' => $index + 1,
              'num_doc' => $rowData['num_doc'],
              'full_name' => $rowData['full_name'] ?? 'N/A'
            ];
            continue;
          }

          // Validar longitud del documento y asignar status_num_doc
          $numDoc = trim($rowData['num_doc']);
          $docLength = strlen($numDoc);
          $documentTypeId = $rowData['document_type_id'];

          // Inicializar status_num_doc
          $statusNumDoc = 'PENDIENTE';
          $countDigitTypeDoc = (int)ApCommercialMasters::find($documentTypeId)->code;

          // Validar si la longitud del documento no coincide con el tipo
          if ($docLength != $countDigitTypeDoc) {
            $statusNumDoc = 'ERRADO';
          }

          // Agregar status_num_doc al array de datos
          $rowData['status_num_doc'] = $statusNumDoc;

          // Crear el registro
          $buyer = PotentialBuyers::create($rowData);
          $createdIds[] = $buyer->id;
          $imported++;

        } catch (Exception $e) {
          $errors++;
          $errorDetails[] = [
            'row' => $index + 1,
            'error' => $e->getMessage(),
            'data' => $rowData
          ];
        }
      }

      DB::commit();

      // Despachar un job individual por cada registro creado
      foreach ($createdIds as $buyerId) {
        ValidatePotentialBuyersDocuments::dispatch([$buyerId]);
      }

      // Construir mensaje descriptivo
      $message = "Importación de redes sociales completada: {$imported} de {$totalRows} registros importados exitosamente";
      if ($duplicated > 0) {
        $message .= ", {$duplicated} ya están registrados en el mes actual";
      }
      if ($errors > 0) {
        $message .= ", {$errors} con errores";
      }

      return [
        'success' => true,
        'message' => $message,
        'summary' => [
          'total_rows' => $totalRows,
          'imported' => $imported,
          'duplicated' => $duplicated,
          'errors' => $errors,
          'duplicated_records' => $duplicatedRecords,
          'error_details' => $errorDetails
        ]
      ];

    } catch (Exception $e) {
      DB::rollBack();
      return [
        'success' => false,
        'message' => 'Error en la importación de redes sociales',
        'error' => $e->getMessage()
      ];
    }
  }

  public function assignWorkersToUnassigned(): array
  {
    DB::beginTransaction();
    try {
      // Obtener todos los registros del mes anterior y mes actual
      $unassignedBuyers = PotentialBuyers::whereBetween('registration_date', [
        now()->subMonth()->startOfMonth(),
        now()->endOfMonth()
      ])
        ->where('use', 0)
        ->get();

      if ($unassignedBuyers->isEmpty()) {
        return [
          'success' => true,
          'message' => 'No hay registros en el periodo actual',
          'summary' => [
            'total' => 0,
            'assigned' => 0,
            'unassigned' => 0
          ]
        ];
      }

      $currentYear = date('Y');
      $currentMonth = date('m');
      $workersCache = [];
      $distributionCounter = [];
      $assigned = 0;
      $unassigned = 0;

      foreach ($unassignedBuyers as $buyer) {
        try {
          // Verificar que tenga sede_id y vehicle_brand_id
          if (empty($buyer->sede_id) || empty($buyer->vehicle_brand_id)) {
            $unassigned++;
            continue;
          }

          // Crear clave única para esta combinación de sede + marca
          $cacheKey = "{$buyer->sede_id}_{$buyer->vehicle_brand_id}";

          // Si no están en cache, obtener los asesores de ApAssignBrandConsultant
          if (!isset($workersCache[$cacheKey])) {
            $workerIds = ApAssignBrandConsultant::where('sede_id', $buyer->sede_id)
              ->where('brand_id', $buyer->vehicle_brand_id)
              ->where('year', $currentYear)
              ->where('month', $currentMonth)
              ->where('status', 1)
              ->pluck('worker_id')
              ->toArray();

            if (empty($workerIds)) {
              $workersCache[$cacheKey] = [];
              $unassigned++;
              continue;
            }

            // Obtener datos de los workers
            $workers = Worker::whereIn('id', $workerIds)
              ->pluck('id')
              ->toArray();

            $workersCache[$cacheKey] = $workers;
            $distributionCounter[$cacheKey] = 0;
          }

          // Si no hay asesores disponibles para esta combinación
          if (empty($workersCache[$cacheKey])) {
            $unassigned++;
            continue;
          }

          // Obtener el asesor actual según round-robin
          $workers = $workersCache[$cacheKey];
          $currentIndex = $distributionCounter[$cacheKey] % count($workers);
          $assignedWorkerId = $workers[$currentIndex];

          // Actualizar el buyer con el worker_id
          $buyer->update(['worker_id' => $assignedWorkerId]);

          // Incrementar el contador para el siguiente registro
          $distributionCounter[$cacheKey]++;
          $assigned++;

        } catch (Exception $e) {
          $unassigned++;
          continue;
        }
      }

      DB::commit();

      return [
        'success' => true,
        'message' => "Asignación completada: {$assigned} registros asignados, {$unassigned} no pudieron ser asignados",
        'summary' => [
          'total' => $unassignedBuyers->count(),
          'assigned' => $assigned,
          'unassigned' => $unassigned
        ]
      ];

    } catch (Exception $e) {
      DB::rollBack();
      return [
        'success' => false,
        'message' => 'Error en la asignación de asesores',
        'error' => $e->getMessage()
      ];
    }
  }
}

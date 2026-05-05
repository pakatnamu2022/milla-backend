<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PotentialBuyersResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Http\Services\common\ImportService;
use App\Imports\ap\comercial\PotentialBuyersDercoImport;
use App\Imports\ap\comercial\PotentialBuyersSocialNetworksImport;
use App\Jobs\ValidatePotentialBuyersDocuments;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\PotentialBuyers;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PotentialBuyersService extends BaseService
{
  protected $exportService;

  public function __construct(
    ExportService $exportService
  )
  {
    $this->exportService = $exportService;
  }

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

  public function myPotentialBuyers(Request $request, $workerId, $requestWorkerId, $canViewAllUsers)
  {
    $workerIdToUse = $workerId;
    if ($canViewAllUsers && $requestWorkerId) {
      $workerIdToUse = $requestWorkerId;
    }

    $query = PotentialBuyers::where('worker_id', $workerIdToUse)
      ->where('use', PotentialBuyers::CREATED);

    return $this->getFilteredResults(
      $query,
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
      $data['user_id'] = auth()->id();

      $TypeDocument = ApMasters::findOrFail($data['document_type_id']);
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
      $TypeDocument = ApMasters::findOrFail($data['document_type_id']);
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

  public function discard($id, $comment, $reasonDiscardingId)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);
      $businessPartner->use = PotentialBuyers::DISCARTED; // Marcar como descartado
      if ($comment || $reasonDiscardingId) {
        $businessPartner->comment = Str::upper(Str::ascii($comment));
        $businessPartner->reason_discarding_id = $reasonDiscardingId;
      }
      $businessPartner->save();
      DB::commit();
      return ['message' => 'Cliente potencial descartado correctamente'];
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

      // Validar el número de columnas del archivo
      // Formato esperado: creado | modelo | version | nro_documento | nombres | apellidos | celular | correo | campana | codigo_de_tienda | marca | tipo_documento | tipo | sector_ingresos_id | area_id (al menos 11 columnas)
      $import = new PotentialBuyersDercoImport();
      $collection = Excel::toCollection($import, $file);

      if ($collection->isNotEmpty() && $collection->first()->isNotEmpty()) {
        $firstRow = $collection->first()->first();
        $columnCount = count($firstRow);

        // Se esperan al menos 11 columnas
        if ($columnCount <= 11) {
          DB::rollBack();
          return [
            'success' => false,
            'message' => 'Formato de archivo incorrecto',
            'error' => "El archivo debe tener al menos 11 columnas. Se encontraron {$columnCount} columnas. Formato esperado: creado | modelo | version | nro_documento | nombres | apellidos | celular | correo | campana | codigo_de_tienda | marca | tipo_documento | tipo | sector_ingresos_id | area_id"
          ];
        }
      }

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

      // Variables para la asignación de asesores con balance inteligente
      $currentYear = date('Y');
      $currentMonth = date('m');
      $workersCache = [];
      $workersWithCounts = []; // Array con [id, count] ordenado por count

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
          $countDigitTypeDoc = (int)ApMasters::find($documentTypeId)->code;

          // Validar si la longitud del documento no coincide con el tipo
          if ($docLength != $countDigitTypeDoc) {
            $statusNumDoc = 'ERRADO';
          }

          // Agregar status_num_doc al array de datos
          $rowData['status_num_doc'] = $statusNumDoc;
          $rowData['user_id'] = auth()->id();

          // Asignar asesor usando balance inteligente basado en conteo actual
          if (!empty($rowData['sede_id']) && !empty($rowData['vehicle_brand_id'])) {
            // Crear clave única para esta combinación de sede + marca
            $cacheKey = "{$rowData['sede_id']}_{$rowData['vehicle_brand_id']}";

            // Si no están en cache, obtener los asesores de ApAssignBrandConsultant
            if (!isset($workersCache[$cacheKey])) {
              $workerIds = ApAssignBrandConsultant::where('sede_id', $rowData['sede_id'])
                ->where('brand_id', $rowData['vehicle_brand_id'])
                ->where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 1)
                ->pluck('worker_id')
                ->toArray();

              if (!empty($workerIds)) {
                // Obtener datos de los workers
                $workers = Worker::whereIn('id', $workerIds)
                  ->pluck('id')
                  ->toArray();

                // Consultar cuántos leads tiene cada asesor HOY para esta combinación
                // Usa created_at (fecha de creación en BD) no registration_date (fecha del Excel)
                $leadCounts = PotentialBuyers::where('sede_id', $rowData['sede_id'])
                  ->where('vehicle_brand_id', $rowData['vehicle_brand_id'])
                  ->whereDate('created_at', today())
                  ->whereIn('worker_id', $workers)
                  ->groupBy('worker_id')
                  ->selectRaw('worker_id, COUNT(*) as count')
                  ->pluck('count', 'worker_id')
                  ->toArray();

                // Crear lista de asesores con su conteo actual
                $workersWithCountsArray = [];
                foreach ($workers as $workerId) {
                  $workersWithCountsArray[] = [
                    'id' => $workerId,
                    'count' => $leadCounts[$workerId] ?? 0 // Si no tiene leads, es 0
                  ];
                }

                // Ordenar por count ASC (el que tiene menos primero)
                usort($workersWithCountsArray, function ($a, $b) {
                  return $a['count'] <=> $b['count'];
                });

                $workersCache[$cacheKey] = $workers;
                $workersWithCounts[$cacheKey] = $workersWithCountsArray;
              } else {
                $workersCache[$cacheKey] = [];
                $workersWithCounts[$cacheKey] = [];
              }
            }

            // Si hay asesores disponibles para esta combinación, asignar al que tiene menos leads
            if (!empty($workersCache[$cacheKey])) {
              // Asignar siempre al primer asesor de la lista (que tiene menos leads)
              $assignedWorkerId = $workersWithCounts[$cacheKey][0]['id'];

              // Asignar el worker_id al registro
              $rowData['worker_id'] = $assignedWorkerId;

              // Incrementar el contador local para este asesor
              $workersWithCounts[$cacheKey][0]['count']++;

              // Re-ordenar la lista para mantener el balance
              usort($workersWithCounts[$cacheKey], function ($a, $b) {
                return $a['count'] <=> $b['count'];
              });
            }
          }

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
        $message .= ", {$duplicated} registros estan duplicados en el archivo o ya están registrados en el mes actual";
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
        'message' => $e->getMessage(),
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

      // Validar el número de columnas del archivo
      // Formato esperado: marca | modelo | version | tipo_doc | documento_cliente | nombre | apellido | celular | email | sede | fecha | campana (11 columnas)
      $import = new PotentialBuyersSocialNetworksImport();
      $collection = Excel::toCollection($import, $file);

      if ($collection->isNotEmpty() && $collection->first()->isNotEmpty()) {
        $firstRow = $collection->first()->first();

        // Filtrar columnas completamente vacías (null o string vacío)
        $filteredRow = $firstRow->filter(function ($value) {
          return !is_null($value) && trim($value) !== '';
        });

        $columnCount = count($filteredRow);

        // Se esperan 11 columnas (marca hasta campana)
        if ($columnCount !== 11) {
          DB::rollBack();
          return [
            'success' => false,
            'message' => 'Formato de archivo incorrecto',
            'error' => "El archivo debe tener exactamente 11 columnas. Se encontraron {$columnCount} columnas. Formato esperado: marca | modelo | version | tipo_doc | documento_cliente | nombre | apellido | celular | email | sede | fecha | campana"
          ];
        }
      }

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

      // Variables para la asignación de asesores con balance inteligente
      $currentYear = date('Y');
      $currentMonth = date('m');
      $workersCache = [];
      $workersWithCounts = []; // Array con [id, count] ordenado por count

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
          $countDigitTypeDoc = (int)ApMasters::find($documentTypeId)->code;

          // Validar si la longitud del documento no coincide con el tipo
          if ($docLength != $countDigitTypeDoc) {
            $statusNumDoc = 'ERRADO';
          }

          // Agregar status_num_doc al array de datos
          $rowData['status_num_doc'] = $statusNumDoc;
          $rowData['user_id'] = auth()->id();

          // Asignar asesor usando balance inteligente basado en conteo actual
          if (!empty($rowData['sede_id']) && !empty($rowData['vehicle_brand_id'])) {
            // Crear clave única para esta combinación de sede + marca
            $cacheKey = "{$rowData['sede_id']}_{$rowData['vehicle_brand_id']}";

            // Si no están en cache, obtener los asesores de ApAssignBrandConsultant
            if (!isset($workersCache[$cacheKey])) {
              $workerIds = ApAssignBrandConsultant::where('sede_id', $rowData['sede_id'])
                ->where('brand_id', $rowData['vehicle_brand_id'])
                ->where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('status', 1)
                ->pluck('worker_id')
                ->toArray();

              if (!empty($workerIds)) {
                // Obtener datos de los workers
                $workers = Worker::whereIn('id', $workerIds)
                  ->pluck('id')
                  ->toArray();

                // Consultar cuántos leads tiene cada asesor HOY para esta combinación
                // Usa created_at (fecha de creación en BD) no registration_date (fecha del Excel)
                $leadCounts = PotentialBuyers::where('sede_id', $rowData['sede_id'])
                  ->where('vehicle_brand_id', $rowData['vehicle_brand_id'])
                  ->whereDate('created_at', today())
                  ->whereIn('worker_id', $workers)
                  ->groupBy('worker_id')
                  ->selectRaw('worker_id, COUNT(*) as count')
                  ->pluck('count', 'worker_id')
                  ->toArray();

                // Crear lista de asesores con su conteo actual
                $workersWithCountsArray = [];
                foreach ($workers as $workerId) {
                  $workersWithCountsArray[] = [
                    'id' => $workerId,
                    'count' => $leadCounts[$workerId] ?? 0 // Si no tiene leads, es 0
                  ];
                }

                // Ordenar por count ASC (el que tiene menos primero)
                usort($workersWithCountsArray, function ($a, $b) {
                  return $a['count'] <=> $b['count'];
                });

                $workersCache[$cacheKey] = $workers;
                $workersWithCounts[$cacheKey] = $workersWithCountsArray;
              } else {
                $workersCache[$cacheKey] = [];
                $workersWithCounts[$cacheKey] = [];
              }
            }

            // Si hay asesores disponibles para esta combinación, asignar al que tiene menos leads
            if (!empty($workersCache[$cacheKey])) {
              // Asignar siempre al primer asesor de la lista (que tiene menos leads)
              $assignedWorkerId = $workersWithCounts[$cacheKey][0]['id'];

              // Asignar el worker_id al registro
              $rowData['worker_id'] = $assignedWorkerId;

              // Incrementar el contador local para este asesor
              $workersWithCounts[$cacheKey][0]['count']++;

              // Re-ordenar la lista para mantener el balance
              usort($workersWithCounts[$cacheKey], function ($a, $b) {
                return $a['count'] <=> $b['count'];
              });
            }
          }

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
        $message .= ", {$duplicated} registros estan duplicados en el archivo o ya están registrados en el mes actual";
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
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
      ];
    }
  }

  public function assignWorkersToUnassigned($date_from, $date_to, $all = false): array
  {
    DB::beginTransaction();
    try {
      // Validar que las fechas estén dentro del mes actual
      $startOfMonth = now()->startOfMonth();
      $endOfMonth = now()->endOfMonth();

      $dateFrom = Carbon::parse($date_from);
      $dateTo = Carbon::parse($date_to);

      if ($dateFrom->lt($startOfMonth) || $dateTo->gt($endOfMonth)) {
        return [
          'success' => false,
          'message' => 'Las fechas deben estar dentro del mes actual',
          'error' => "El rango permitido es desde {$startOfMonth->format('Y-m-d')} hasta {$endOfMonth->format('Y-m-d')}"
        ];
      }

      if ($dateFrom->gt($dateTo)) {
        return [
          'success' => false,
          'message' => 'La fecha inicial no puede ser mayor a la fecha final',
          'error' => 'Rango de fechas inválido'
        ];
      }

      // Si $all = true, validar que NO exista ningún registro con use = 1 en el rango
      if ($all) {
        $takenLeadsCount = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
          ->where('use', 1)
          ->where('type', PotentialBuyers::LEADS)
          ->count();

        if ($takenLeadsCount > 0) {
          return [
            'success' => false,
            'message' => 'Ya se han tomado leads en este rango, por esa razón ya no se puede redistribuir',
            'error' => "Se encontraron {$takenLeadsCount} leads con use=1 en el rango de fechas especificado"
          ];
        }

        // Si no hay leads tomados, LIMPIAR todas las asignaciones del rango primero
        PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
          ->where('use', 0)
          ->where('type', PotentialBuyers::LEADS)
          ->update(['worker_id' => null]);

        // Luego obtener todos los leads sin asignar para redistribuir desde cero
        $unassignedBuyers = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
          ->where('use', 0)
          ->whereNull('worker_id')
          ->where('type', PotentialBuyers::LEADS)
          ->get();
      } else {
        // Si $all = false, solo tomar los que tienen worker_id NULL
        $unassignedBuyers = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
          ->whereNull('worker_id')
          ->where('type', PotentialBuyers::LEADS)
          ->get();
      }

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
      $workersWithCounts = []; // Array con [id, count] ordenado por count
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
              $workersWithCounts[$cacheKey] = [];
              $unassigned++;
              continue;
            }

            // Obtener datos de los workers
            $workers = Worker::whereIn('id', $workerIds)
              ->pluck('id')
              ->toArray();

            // Consultar cuántos leads tiene cada asesor en el rango especificado para esta combinación
            // Usa created_at (fecha de creación en BD) para balance inteligente
            $leadCounts = PotentialBuyers::where('sede_id', $buyer->sede_id)
              ->where('vehicle_brand_id', $buyer->vehicle_brand_id)
              ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
              ->whereIn('worker_id', $workers)
              ->groupBy('worker_id')
              ->selectRaw('worker_id, COUNT(*) as count')
              ->pluck('count', 'worker_id')
              ->toArray();

            // Crear lista de asesores con su conteo actual
            $workersWithCountsArray = [];
            foreach ($workers as $workerId) {
              $workersWithCountsArray[] = [
                'id' => $workerId,
                'count' => $leadCounts[$workerId] ?? 0 // Si no tiene leads, es 0
              ];
            }

            // Ordenar por count ASC (el que tiene menos primero)
            usort($workersWithCountsArray, function ($a, $b) {
              return $a['count'] <=> $b['count'];
            });

            $workersCache[$cacheKey] = $workers;
            $workersWithCounts[$cacheKey] = $workersWithCountsArray;
          }

          // Si no hay asesores disponibles para esta combinación
          if (empty($workersCache[$cacheKey])) {
            $unassigned++;
            continue;
          }

          // Asignar al asesor que tiene MENOS leads
          $assignedWorkerId = $workersWithCounts[$cacheKey][0]['id'];

          // Actualizar el buyer con el worker_id
          $buyer->update(['worker_id' => $assignedWorkerId]);

          // Incrementar el contador local para este asesor
          $workersWithCounts[$cacheKey][0]['count']++;

          // Re-ordenar la lista para mantener el balance
          usort($workersWithCounts[$cacheKey], function ($a, $b) {
            return $a['count'] <=> $b['count'];
          });

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

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, PotentialBuyers::class);
  }
}

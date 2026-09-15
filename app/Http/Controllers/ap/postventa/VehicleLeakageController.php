<?php

namespace App\Http\Controllers\ap\postventa;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVehicleLeakageFile;
use App\Models\ap\postventa\VehicleLeakageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VehicleLeakageController extends Controller
{
  /**
   * Encola un archivo Excel de fugado/fuga temprana para procesamiento en background
   * Retorna job_id para consultar el estado posteriormente
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function processLeakageFile(Request $request): JsonResponse
  {
    try {
      // Validar que se haya enviado un archivo
      $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:xlsx,xls|max:51200', // Máximo 50MB
      ], [
        'file.required' => 'Debe enviar un archivo Excel',
        'file.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls)',
        'file.max' => 'El archivo no debe superar los 50MB',
      ]);

      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'Error de validación',
          'errors' => $validator->errors()
        ], 422);
      }

      $file = $request->file('file');
      $originalName = $file->getClientOriginalName();
      $fileName = 'temp_' . time() . '_' . $originalName;

      // Guardar temporalmente el archivo
      $tempPath = storage_path('app/temp');
      if (!file_exists($tempPath)) {
        mkdir($tempPath, 0755, true);
      }

      $filePath = $tempPath . '/' . $fileName;
      $file->move($tempPath, $fileName);

      // Crear registro del job en la BD
      $jobRecord = VehicleLeakageJob::create([
        'user_id' => $request->user()->id ?? null,
        'original_filename' => $originalName,
        'temp_file_path' => $filePath,
        'status' => 'pending',
      ]);

      Log::info('Archivo encolado para procesamiento', [
        'job_id' => $jobRecord->id,
        'original_name' => $originalName,
        'temp_file' => $fileName,
        'size' => filesize($filePath)
      ]);

      // Despachar el job a la cola
      ProcessVehicleLeakageFile::dispatch($jobRecord->id);

      return response()->json([
        'success' => true,
        'message' => 'Archivo encolado para procesamiento. Use el job_id para consultar el estado.',
        'data' => [
          'job_id' => $jobRecord->id,
          'status' => $jobRecord->status,
          'original_filename' => $jobRecord->original_filename,
        ]
      ], 202); // 202 Accepted

    } catch (\Exception $e) {
      Log::error('Error al encolar archivo de fugado/fuga temprana: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
      ]);

      // Limpiar archivo temporal si existe
      if (isset($filePath) && file_exists($filePath)) {
        @unlink($filePath);
      }

      return response()->json([
        'success' => false,
        'message' => 'Error al encolar el archivo: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lista todos los jobs del usuario autenticado
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function listJobs(Request $request): JsonResponse
  {
    try {
      $userId = $request->user()->id ?? null;

      // Filtrar por status si se proporciona
      $statusFilter = $request->query('status');

      $query = VehicleLeakageJob::query()
        ->when($userId, function ($q) use ($userId) {
          return $q->where('user_id', $userId);
        })
        ->when($statusFilter, function ($q) use ($statusFilter) {
          return $q->where('status', $statusFilter);
        })
        ->orderBy('created_at', 'desc');

      // Paginación opcional
      $perPage = $request->query('per_page', 20);
      $jobs = $query->paginate($perPage);

      // Formatear la respuesta
      $data = $jobs->map(function ($job) {
        $item = [
          'job_id' => $job->id,
          'original_filename' => $job->original_filename,
          'status' => $job->status,
          'created_at' => $job->created_at,
          'started_at' => $job->started_at,
          'completed_at' => $job->completed_at,
        ];

        // Agregar resultados si está completado
        if ($job->isCompleted()) {
          $item['results'] = $job->results;
          $item['download_url'] = route('vehicle-leakage.download', $job->id);
        }

        // Agregar mensaje de error si falló
        if ($job->isFailed()) {
          $item['error_message'] = $job->error_message;
        }

        return $item;
      });

      return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
          'total' => $jobs->total(),
          'per_page' => $jobs->perPage(),
          'current_page' => $jobs->currentPage(),
          'last_page' => $jobs->lastPage(),
        ]
      ]);

    } catch (\Exception $e) {
      Log::error('Error al listar jobs de vehicle leakage: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Error al obtener la lista de archivos procesados'
      ], 500);
    }
  }

  /**
   * Consulta el estado de un job de procesamiento
   *
   * @param int $jobId
   * @return JsonResponse
   */
  public function checkStatus(int $jobId): JsonResponse
  {
    try {
      $jobRecord = VehicleLeakageJob::findOrFail($jobId);

      $response = [
        'success' => true,
        'data' => [
          'job_id' => $jobRecord->id,
          'status' => $jobRecord->status,
          'original_filename' => $jobRecord->original_filename,
          'created_at' => $jobRecord->created_at,
          'started_at' => $jobRecord->started_at,
          'completed_at' => $jobRecord->completed_at,
        ]
      ];

      // Si está completado, agregar resultados y link de descarga
      if ($jobRecord->isCompleted()) {
        $response['data']['results'] = $jobRecord->results;
        $response['data']['download_url'] = route('vehicle-leakage.download', $jobRecord->id);
      }

      // Si falló, agregar mensaje de error
      if ($jobRecord->isFailed()) {
        $response['data']['error_message'] = $jobRecord->error_message;
      }

      return response()->json($response);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Job no encontrado'
      ], 404);
    }
  }

  /**
   * Descarga el archivo procesado
   *
   * @param int $jobId
   * @return BinaryFileResponse|JsonResponse
   */
  public function downloadFile(int $jobId)
  {
    try {
      $jobRecord = VehicleLeakageJob::findOrFail($jobId);

      if (!$jobRecord->isCompleted()) {
        return response()->json([
          'success' => false,
          'message' => 'El archivo aún no está procesado. Estado actual: ' . $jobRecord->status
        ], 400);
      }

      $fullPath = storage_path('app/' . $jobRecord->processed_file_path);

      if (!file_exists($fullPath)) {
        return response()->json([
          'success' => false,
          'message' => 'El archivo procesado no existe o fue eliminado'
        ], 404);
      }

      $outputName = 'enriquecido_' . $jobRecord->original_filename;

      return response()->download($fullPath, $outputName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Job no encontrado'
      ], 404);
    }
  }

}
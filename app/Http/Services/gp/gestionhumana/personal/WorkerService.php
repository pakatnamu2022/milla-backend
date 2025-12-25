<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Resources\PersonBirthdayResource;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\personal\WorkerSignature;
use App\Models\gp\gestionsistema\DigitalFile;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkerService extends BaseService
{
  protected DigitalFileService $digitalFileService;

  private const FILE_PATHS = [
    'worker_signature' => '/gp/gestionhumana/personal/firmas/',
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Worker::class,
      $request,
      Worker::filters,
      Worker::sorts,
      WorkerResource::class,
    );
  }

  public function find(string $id)
  {
    $worker = Worker::find($id);
    if (!$worker) {
      throw new Exception("Trabajador no encontrado");
    }
    return $worker;
  }

  public function show(string $id)
  {
    $worker = $this->find($id);
    return new WorkerResource($worker);
  }

  public function listBirthdays(Request $request)
  {
    $query = Worker::query()
      ->working()
      ->select('*')
      ->selectRaw("
            DATEDIFF(
                DATE_ADD(
                    fecha_nacimiento,
                    INTERVAL (YEAR(CURDATE()) - YEAR(fecha_nacimiento)) +
                    (DATE_FORMAT(fecha_nacimiento, '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')) YEAR
                ),
                CURDATE()
            ) as days_to_birthday
        ")
      ->orderBy('days_to_birthday');

    return $this->getFilteredResults(
      $query,
      $request,
      Worker::filters,
      Worker::sorts,
      PersonBirthdayResource::class
    );
  }

  public function revalidate()
  {
    $details = EvaluationPersonDetail::all()->pluck('person_id');
    $workers = Worker::where('status_id', 22)
      ->whereNotIn('id', $details)
      ->where(function ($query) {
        $query
          ->whereNull('supervisor_id')
          ->orWhere(fn($q) => $q->whereNull('jefe_id')
            ->whereNotNull('supervisor_id')
          );
      })->get();

    foreach ($workers as $worker) {
      if ($worker->jefe_id && !$worker->supervisor_id) {
        $supervisor = Worker::find($worker->jefe_id);
        if ($supervisor) {
          $worker->supervisor_id = $supervisor->id;
          $worker->save();
        }
      } elseif ($worker->supervisor_id && !$worker->jefe_id) {
        $jefe = Worker::find($worker->supervisor_id);
        if ($jefe) {
          $worker->jefe_id = $jefe->id;
          $worker->save();
        }
      } else {
        $position = $worker->position;
        if ($position) {
          $supervisorPosition = $position->cargo_id;
          if ($supervisorPosition) {
            $supervisorWorker = Worker::where('cargo_id', $supervisorPosition)
              ->where('status_id', 22)
              ->first();
            if ($supervisorWorker) {
              $worker->supervisor_id = $supervisorWorker->id;
              $worker->jefe_id = $supervisorWorker->id;
              $worker->save();
            }
          }
        }
      }
    }

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutCategoriesAndObjectives()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'objectives', 'competences', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Si tiene EvaluationPersonDetail, debe ser excluido
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Si no tiene categoría jerárquica
        if (!$hasCategory) {
          $worker->inclusion_reason = 'No tiene categoría jerárquica';
          $worker->has_category = false;
          $worker->has_objectives = false;
          $worker->has_competences = false;
          return true;
        }

        $category = $worker->position->hierarchicalCategory;
        $hasObjectives = $worker->objectives->count() > 0;
        $hasCompetences = $worker->competences->count() > 0;

        $worker->has_category = true;
        $worker->has_objectives = $hasObjectives;
        $worker->has_competences = $hasCompetences;

        // Si tiene categoría pero no tiene competencias
        if (!$hasCompetences) {
          $worker->inclusion_reason = 'No tiene competencias';
          return true;
        }

        // Si la categoría requiere objetivos (hasObjectives = true) pero no los tiene
        if ($category->hasObjectives && !$hasObjectives) {
          $worker->inclusion_reason = 'No tiene objetivos';
          return true;
        }

        // Si la categoría no requiere objetivos (hasObjectives = false) es normal, no incluir
        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutObjectives()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'objectives', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Excluir si tiene EvaluationPersonDetail
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Solo incluir si tiene categoría que requiere objetivos pero no los tiene
        if ($hasCategory) {
          $category = $worker->position->hierarchicalCategory;
          $hasObjectives = $worker->objectives->count() > 0;

          if ($category->hasObjectives && !$hasObjectives) {
            $worker->inclusion_reason = 'No tiene objetivos';
            $worker->has_category = true;
            $worker->has_objectives = false;
            return true;
          }
        }

        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutCategories()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'evaluationDetails'])
      ->get()
      ->groupBy(function ($worker) {
        // Agrupar por nombre completo (normalizado)
        return trim(strtolower($worker->nombre_completo));
      })
      ->map(function ($group) {
        // Tomar solo el primer registro de cada grupo
        return $group->first();
      })
      ->filter(function ($worker) {
        // Excluir si tiene EvaluationPersonDetail
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Solo incluir si NO tiene categoría jerárquica
        if (!$hasCategory) {
          $worker->inclusion_reason = 'No tiene categoría jerárquica';
          $worker->has_category = false;
          return true;
        }

        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutCompetences()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'competences', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Excluir si tiene EvaluationPersonDetail
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Solo incluir si tiene categoría pero no tiene competencias
        if ($hasCategory) {
          $hasCompetences = $worker->competences->count() > 0;

          if (!$hasCompetences) {
            $worker->inclusion_reason = 'No tiene competencias';
            $worker->has_category = true;
            $worker->has_competences = false;
            return true;
          }
        }

        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function assignObjectivesToWorkers()
  {
    DB::beginTransaction();

    try {
      $workersProcessed = [];
      $objectivesAssigned = 0;

      // Buscar workers que tienen categoría pero no tienen objetivos y que requieren objetivos
      $workers = Worker::where('status_id', 22)
        ->with(['position.hierarchicalCategory.objectives', 'objectives', 'evaluationDetails'])
        ->get()
        ->filter(function ($worker) {
          // Excluir si tiene EvaluationPersonDetail
          if ($worker->evaluationDetails->count() > 0) {
            return false;
          }

          $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

          if ($hasCategory) {
            $category = $worker->position->hierarchicalCategory;
            $hasObjectives = $worker->objectives->count() > 0;

            // Solo incluir si la categoría requiere objetivos pero no los tiene
            return $category->hasObjectives && !$hasObjectives;
          }

          return false;
        });

      foreach ($workers as $worker) {
        $category = $worker->position->hierarchicalCategory;
        $objectivesForCategory = $category->objectives; // Esto ya filtra solo los activos por la relación en HierarchicalCategory

        $workerData = [
          'id' => $worker->id,
          'name' => $worker->nombre_completo,
          'position' => $worker->position->name,
          'hierarchical_category' => $category->name,
          'objectives_assigned' => []
        ];

        foreach ($objectivesForCategory as $objective) {
          // Crear el registro en EvaluationCategoryObjectiveDetail
          $objectiveDetail = EvaluationCategoryObjectiveDetail::create([
            'objective_id' => $objective->id,
            'category_id' => $category->id,
            'person_id' => $worker->id,
            'goal' => $objective->goalReference, // Usar la meta de referencia del objetivo
            'weight' => $objective->fixedWeight ?? 1, // Usar el peso fijo o 1 por defecto
            'fixedWeight' => true,
            'active' => true
          ]);

          $workerData['objectives_assigned'][] = [
            'objective_id' => $objective->id,
            'objective_name' => $objective->name,
            'goal_reference' => $objective->goalReference,
            'weight' => $objective->fixedWeight ?? 1
          ];

          $objectivesAssigned++;
        }

        $workersProcessed[] = $workerData;
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => "Se asignaron objetivos exitosamente",
        'summary' => [
          'workers_processed' => count($workersProcessed),
          'objectives_assigned' => $objectivesAssigned
        ],
        'data' => $workersProcessed
      ]);

    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
    }
  }

  /**
   * @param Request $request
   * @return JsonResponse
   * @throws Exception
   */
  public function myConsultants(Request $request): JsonResponse
  {
    $worker = $this->getAuthenticatedWorkerWithArea();
    $assignmentsIds = $this->getConsultantAssignments(
      $worker,
      $request->validated('month'),
      $request->validated('year')
    );

    return $this->getFilteredResults(
      Worker::whereIn('id', $assignmentsIds),
      $request,
      Worker::filters,
      Worker::sorts,
      WorkerResource::class,
    );
  }

  /**
   * Get authenticated worker with position and area loaded
   * @return Worker
   * @throws Exception
   */
  public function getAuthenticatedWorkerWithArea(): Worker
  {
    $userId = auth()->user()->partner_id;
    $worker = Worker::with('position.area')->find($userId);

    if (!$worker) {
      throw new Exception("Trabajador no encontrado para el usuario autenticado");
    }

    if (!$worker->position) {
      throw new Exception("El trabajador autenticado no tiene una posición asignada");
    }

    if (!$worker->position->area) {
      throw new Exception("El trabajador autenticado no tiene un área asignada");
    }

    return $worker;
  }

  /**
   * Get consultant assignments based on worker area
   * @param Worker $worker
   * @param int $month
   * @param int $year
   * @return Collection
   */
  public function getConsultantAssignments(Worker $worker, int $month, int $year): Collection
  {
    $isTicsArea = $worker->position->area->id === Constants::TICS_AREA_ID;

    $query = ApAssignmentLeadership::where('month', $month)
      ->where('year', $year);

    if (!$isTicsArea) {
      $query->where('boss_id', $worker->id);
    }

    return $query->pluck('worker_id');
  }

  public function update($data)
  {
    try {
      DB::beginTransaction();

      // Buscar la persona
      $person = Worker::find($data['id']);
      if (!$person) {
        throw new Exception('Persona no encontrada');
      }

      // Extraer firma en base64 del array
      $workerSignature = $data['worker_signature'] ?? null;
      unset($data['worker_signature']);

      // Actualizar datos de la persona
      $person->update($data);

      // Procesar y guardar firma si existe
      if ($workerSignature) {
        $this->processWorkerSignature($person, $workerSignature);
      }

      DB::commit();

      return new WorkerResource($person);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Procesa una firma de trabajador en base64 y la guarda en Digital Ocean
   */
  private function processWorkerSignature($person, string $base64Signature): void
  {
    // Convertir base64 a UploadedFile
    $signatureFile = Helpers::base64ToUploadedFile($base64Signature, "worker_{$person->id}_signature.png");

    // Determinar la ruta
    $path = self::FILE_PATHS['worker_signature'];
    $model = 'worker_signature';

    // Subir archivo usando DigitalFileService
    $digitalFile = $this->digitalFileService->store($signatureFile, $path, 'public', $model);

    // Buscar si ya existe una firma para este trabajador
    $workerSignature = WorkerSignature::where('worker_id', $person->id)->first();

    if ($workerSignature) {
      // Si existe, eliminar la firma anterior de Digital Ocean
      if ($workerSignature->signature_url) {
        $oldDigitalFile = DigitalFile::where('url', $workerSignature->signature_url)->first();
        if ($oldDigitalFile) {
          $this->digitalFileService->destroy($oldDigitalFile->id);
        }
      }

      // Actualizar con la nueva URL
      $workerSignature->signature_url = $digitalFile->url;
      $workerSignature->save();
    } else {
      // Crear nuevo registro de firma
      WorkerSignature::create([
        'worker_id' => $person->id,
        'signature_url' => $digitalFile->url,
        'company_id' => $person->sede->empresa_id ?? null,
      ]);
    }
  }
}

<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\DetailedDevelopmentPlanResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\DetailedDevelopmentPlan;
use App\Models\gp\gestionhumana\evaluacion\DevelopmentPlanTask;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function response;

class DetailedDevelopmentPlanService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            DetailedDevelopmentPlan::class,
            $request,
            DetailedDevelopmentPlan::filters,
            DetailedDevelopmentPlan::sorts,
            DetailedDevelopmentPlanResource::class,
        );
    }

    public function find($id)
    {
        $detailedDevelopmentPlan = DetailedDevelopmentPlan::with([
            'tasks',
            'objectivesCompetences.objectiveDetail',
            'objectivesCompetences.competenceDetail'
        ])->where('id', $id)->first();
        if (!$detailedDevelopmentPlan) {
            throw new Exception('Plan de desarrollo detallado no encontrado');
        }
        return $detailedDevelopmentPlan;
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Extraer las tareas si existen
            $tasks = $data['tasks'] ?? [];
            unset($data['tasks']);

            // Extraer objetivos y competencias si existen
            $objectivesCompetences = $data['objectives_competences'] ?? [];
            unset($data['objectives_competences']);

            // Crear el plan de desarrollo
            $detailedDevelopmentPlan = DetailedDevelopmentPlan::create($data);

            // Crear las tareas asociadas
            if (!empty($tasks)) {
                foreach ($tasks as $taskData) {
                    $detailedDevelopmentPlan->tasks()->create([
                        'description' => $taskData['description'],
                        'end_date' => $taskData['end_date'],
                        'fulfilled' => $taskData['fulfilled'] ?? false,
                    ]);
                }
            }

            // Crear objetivos y competencias asociados
            if (!empty($objectivesCompetences)) {
                foreach ($objectivesCompetences as $objCompData) {
                    $detailedDevelopmentPlan->objectivesCompetences()->create([
                        'objective_detail_id' => $objCompData['objective_detail_id'] ?? null,
                        'competence_detail_id' => $objCompData['competence_detail_id'] ?? null,
                    ]);
                }
            }

            // Recargar con relaciones
            $detailedDevelopmentPlan->load([
                'tasks',
                'objectivesCompetences.objectiveDetail',
                'objectivesCompetences.competenceDetail'
            ]);

            return new DetailedDevelopmentPlanResource($detailedDevelopmentPlan);
        });
    }

    public function show($id)
    {
        return new DetailedDevelopmentPlanResource($this->find($id));
    }

    public function update($data)
    {
        return DB::transaction(function () use ($data) {
            $detailedDevelopmentPlan = $this->find($data['id']);

            // Extraer las tareas si existen
            $tasks = $data['tasks'] ?? null;
            unset($data['tasks']);

            // Extraer objetivos y competencias si existen
            $objectivesCompetences = $data['objectives_competences'] ?? null;
            unset($data['objectives_competences']);
            unset($data['id']);

            // Actualizar el plan de desarrollo
            $detailedDevelopmentPlan->update($data);

            // Si se enviaron tareas, sincronizarlas
            if ($tasks !== null) {
                // Obtener IDs de tareas enviadas
                $sentTaskIds = collect($tasks)->pluck('id')->filter()->toArray();

                // Eliminar tareas que ya no están en el request
                $detailedDevelopmentPlan->tasks()
                    ->whereNotIn('id', $sentTaskIds)
                    ->delete();

                // Crear o actualizar tareas
                foreach ($tasks as $taskData) {
                    if (isset($taskData['id'])) {
                        // Actualizar tarea existente
                        $task = DevelopmentPlanTask::find($taskData['id']);
                        if ($task && $task->detailed_development_plan_id === $detailedDevelopmentPlan->id) {
                            $task->update([
                                'description' => $taskData['description'],
                                'end_date' => $taskData['end_date'],
                                'fulfilled' => $taskData['fulfilled'] ?? false,
                            ]);
                        }
                    } else {
                        // Crear nueva tarea
                        $detailedDevelopmentPlan->tasks()->create([
                            'description' => $taskData['description'],
                            'end_date' => $taskData['end_date'],
                            'fulfilled' => $taskData['fulfilled'] ?? false,
                        ]);
                    }
                }
            }

            // Si se enviaron objetivos y competencias, sincronizarlos
            if ($objectivesCompetences !== null) {
                // Obtener IDs enviados
                $sentObjCompIds = collect($objectivesCompetences)->pluck('id')->filter()->toArray();

                // Eliminar los que ya no están en el request
                $detailedDevelopmentPlan->objectivesCompetences()
                    ->whereNotIn('id', $sentObjCompIds)
                    ->delete();

                // Crear o actualizar
                foreach ($objectivesCompetences as $objCompData) {
                    if (isset($objCompData['id'])) {
                        // Actualizar existente
                        $objComp = $detailedDevelopmentPlan->objectivesCompetences()->find($objCompData['id']);
                        if ($objComp) {
                            $objComp->update([
                                'objective_detail_id' => $objCompData['objective_detail_id'] ?? null,
                                'competence_detail_id' => $objCompData['competence_detail_id'] ?? null,
                            ]);
                        }
                    } else {
                        // Crear nuevo
                        $detailedDevelopmentPlan->objectivesCompetences()->create([
                            'objective_detail_id' => $objCompData['objective_detail_id'] ?? null,
                            'competence_detail_id' => $objCompData['competence_detail_id'] ?? null,
                        ]);
                    }
                }
            }

            // Recargar con relaciones
            $detailedDevelopmentPlan->load([
                'tasks',
                'objectivesCompetences.objectiveDetail',
                'objectivesCompetences.competenceDetail'
            ]);

            return new DetailedDevelopmentPlanResource($detailedDevelopmentPlan);
        });
    }

    public function destroy($id)
    {
        $detailedDevelopmentPlan = $this->find($id);
        DB::transaction(function () use ($detailedDevelopmentPlan) {
            // Las tareas se eliminarán automáticamente por soft delete cascade
            $detailedDevelopmentPlan->delete();
        });
        return response()->json(['message' => 'Plan de desarrollo detallado eliminado correctamente']);
    }
}
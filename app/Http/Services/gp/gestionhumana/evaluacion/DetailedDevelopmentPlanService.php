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
        $detailedDevelopmentPlan = DetailedDevelopmentPlan::with('tasks')->where('id', $id)->first();
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

            // Recargar con tareas
            $detailedDevelopmentPlan->load('tasks');

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

            // Recargar con tareas
            $detailedDevelopmentPlan->load('tasks');

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
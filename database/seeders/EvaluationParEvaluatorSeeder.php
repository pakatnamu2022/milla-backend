<?php

namespace Database\Seeders;

use App\Models\gp\gestionhumana\evaluacion\EvaluationParEvaluator;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EvaluationParEvaluatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar registros existentes
        EvaluationParEvaluator::truncate();

        // Obtener todos los workers activos (status_id = 22)
        $workers = Worker::where('status_id', 22)
            ->where('status_deleted', 1)
            ->where('b_empleado', 1)
            ->pluck('id')
            ->toArray();

        if (count($workers) < 2) {
            $this->command->info('Se necesitan al menos 2 trabajadores activos para asignar evaluadores pares.');
            return;
        }

        $createdCount = 0;

        foreach ($workers as $workerId) {
            // Obtener lista de posibles compañeros (todos menos el worker actual)
            $possibleMates = array_diff($workers, [$workerId]);

            if (empty($possibleMates)) {
                continue;
            }

            // Seleccionar un compañero aleatorio
            $mateId = $possibleMates[array_rand($possibleMates)];

            // Crear el registro
            EvaluationParEvaluator::create([
                'worker_id' => $workerId,
                'mate_id' => $mateId,
            ]);

            $createdCount++;
        }

        $this->command->info("Seeder completado. Se crearon {$createdCount} asignaciones de evaluadores pares.");
    }
}

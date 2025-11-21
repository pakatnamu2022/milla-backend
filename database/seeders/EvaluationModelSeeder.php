<?php

namespace Database\Seeders;

use App\Models\gp\gestionhumana\evaluacion\EvaluationModel;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EvaluationModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar modelos existentes
        EvaluationModel::truncate();

        // Obtener todas las categorías activas (no excluidas de evaluación)
        $categories = HierarchicalCategory::where('excluded_from_evaluation', false)
            ->pluck('id')
            ->toArray();

        if (empty($categories)) {
            $this->command->info('No hay categorías activas para asignar modelos de evaluación.');
            return;
        }

        // Mezclar las categorías para distribución aleatoria
        shuffle($categories);

        $totalCategories = count($categories);
        $firstGroup = (int) ceil($totalCategories * 0.7); // 70%

        // Dividir categorías en dos grupos
        $categoriesGroup1 = array_slice($categories, 0, $firstGroup);
        $categoriesGroup2 = array_slice($categories, $firstGroup);

        // Crear primer modelo: 60-30-10-0 (70% de categorías)
        if (!empty($categoriesGroup1)) {
            $model1 = EvaluationModel::create([
                'categories' => implode(',', $categoriesGroup1),
                'leadership_weight' => 60,
                'self_weight' => 30,
                'par_weight' => 10,
                'report_weight' => 0,
            ]);

            $this->command->info("Modelo 1 creado (60-30-10-0) con " . count($categoriesGroup1) . " categorías");
        }

        // Crear segundo modelo: 60-20-10-10 (30% de categorías)
        if (!empty($categoriesGroup2)) {
            $model2 = EvaluationModel::create([
                'categories' => implode(',', $categoriesGroup2),
                'leadership_weight' => 60,
                'self_weight' => 20,
                'par_weight' => 10,
                'report_weight' => 10,
            ]);

            $this->command->info("Modelo 2 creado (60-20-10-10) con " . count($categoriesGroup2) . " categorías");
        }

        $this->command->info("Seeder completado exitosamente.");
    }
}

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailService;

echo "=== PRUEBA DE ACTUALIZACIÓN DE REFERENCIAS ===" . PHP_EOL . PHP_EOL;

// 1. Verificar estado inicial
echo "1. Estado inicial:" . PHP_EOL;
$orphanedBefore = DB::table('gh_evaluation_person as ep')
    ->leftJoin('gh_evaluation_person_cycle_detail as pcd', 'ep.person_cycle_detail_id', '=', 'pcd.id')
    ->where('ep.evaluation_id', 1)
    ->where(function($query) {
        $query->whereNotNull('pcd.deleted_at')
              ->orWhereNull('pcd.id');
    })
    ->whereNull('ep.deleted_at')
    ->select('ep.id', 'ep.person_id', 'ep.person_cycle_detail_id', 'ep.result', 'pcd.objective_id', 'pcd.deleted_at')
    ->get();

echo "  - EvaluationPerson huérfanos: " . $orphanedBefore->count() . PHP_EOL;

if ($orphanedBefore->count() > 0) {
    echo "  - Ejemplos:" . PHP_EOL;
    foreach ($orphanedBefore->take(5) as $o) {
        echo "    * ID: {$o->id} | person_id: {$o->person_id} | detail_id: {$o->person_cycle_detail_id} | objective_id: {$o->objective_id} | result: {$o->result}" . PHP_EOL;

        // Buscar si existe un detail activo para este mismo objetivo
        $activeDetail = DB::table('gh_evaluation_person_cycle_detail')
            ->where('person_id', $o->person_id)
            ->where('cycle_id', 1)
            ->where('objective_id', $o->objective_id)
            ->whereNull('deleted_at')
            ->first();

        if ($activeDetail) {
            echo "      → Existe detail ACTIVO con ID: {$activeDetail->id}" . PHP_EOL;
        } else {
            echo "      → NO existe detail activo (objetivo removido)" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "2. Ejecutando revalidación con actualización de referencias..." . PHP_EOL;

$service = app(EvaluationPersonCycleDetailService::class);
$result = $service->revalidateAllPersonsInCycle(1);

echo "  - Registros actualizados/limpiados: {$result['orphaned_records_cleaned']}" . PHP_EOL;

echo PHP_EOL . "3. Estado después de la actualización:" . PHP_EOL;

$orphanedAfter = DB::table('gh_evaluation_person as ep')
    ->leftJoin('gh_evaluation_person_cycle_detail as pcd', 'ep.person_cycle_detail_id', '=', 'pcd.id')
    ->where('ep.evaluation_id', 1)
    ->where(function($query) {
        $query->whereNotNull('pcd.deleted_at')
              ->orWhereNull('pcd.id');
    })
    ->whereNull('ep.deleted_at')
    ->count();

echo "  - EvaluationPerson huérfanos: {$orphanedAfter}" . PHP_EOL;

if ($orphanedAfter == 0) {
    echo "  ✓ Todos los huérfanos fueron actualizados o eliminados" . PHP_EOL;
}

// 4. Verificar que los datos de evaluación se preservaron
echo PHP_EOL . "4. Verificación de preservación de datos:" . PHP_EOL;

$beforeIds = $orphanedBefore->pluck('id')->toArray();
if (count($beforeIds) > 0) {
    $preservedRecords = DB::table('gh_evaluation_person')
        ->whereIn('id', array_slice($beforeIds, 0, 5))
        ->whereNull('deleted_at')
        ->get();

    echo "  - Registros verificados: " . count($preservedRecords) . " de 5 ejemplos" . PHP_EOL;

    foreach ($preservedRecords as $record) {
        $detail = DB::table('gh_evaluation_person_cycle_detail')
            ->where('id', $record->person_cycle_detail_id)
            ->first();

        $detailStatus = $detail ? ($detail->deleted_at ? "ELIMINADO" : "ACTIVO") : "NO EXISTE";
        echo "    * EvalPerson ID: {$record->id} | result: {$record->result} | detail_id: {$record->person_cycle_detail_id} ({$detailStatus})" . PHP_EOL;
    }
}

echo PHP_EOL . "5. Resumen:" . PHP_EOL;
echo "  - Huérfanos encontrados: " . $orphanedBefore->count() . PHP_EOL;
echo "  - Actualizados/eliminados: {$result['orphaned_records_cleaned']}" . PHP_EOL;
echo "  - Huérfanos restantes: {$orphanedAfter}" . PHP_EOL;
echo "  - Resultado: " . ($orphanedAfter == 0 ? "✓ EXITOSO" : "❌ FALLÓ") . PHP_EOL;

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailService;

echo "=== PRUEBA COMPLETA: CREAR ESCENARIO Y ACTUALIZAR REFERENCIAS ===" . PHP_EOL . PHP_EOL;

// 1. Crear un escenario de prueba
echo "1. Creando escenario de prueba..." . PHP_EOL;

// Encontrar un person_id existente con details activos
$activePerson = DB::table('gh_evaluation_person_cycle_detail')
    ->where('cycle_id', 1)
    ->whereNull('deleted_at')
    ->first();

if (!$activePerson) {
    die("No hay datos para probar" . PHP_EOL);
}

$personId = $activePerson->person_id;
$cycleId = $activePerson->cycle_id;

echo "  - Usando person_id: {$personId}, cycle_id: {$cycleId}" . PHP_EOL;

// Obtener los details actuales de esta persona
$currentDetails = DB::table('gh_evaluation_person_cycle_detail')
    ->where('person_id', $personId)
    ->where('cycle_id', $cycleId)
    ->whereNull('deleted_at')
    ->get();

echo "  - Details actuales: " . count($currentDetails) . PHP_EOL;

// 2. Simular el escenario: crear EvaluationPerson apuntando a details activos
echo PHP_EOL . "2. Creando EvaluationPerson de prueba..." . PHP_EOL;

$testEvalPersonIds = [];
foreach ($currentDetails->take(2) as $detail) {
    // Crear un EvaluationPerson apuntando a este detail
    $evalPerson = DB::table('gh_evaluation_person')->insertGetId([
        'person_id' => $detail->person_id,
        'chief_id' => $detail->chief_id,
        'chief' => $detail->chief,
        'person_cycle_detail_id' => $detail->id,
        'evaluation_id' => 1,
        'result' => 85.50,  // Datos importantes que queremos preservar
        'compliance' => 90.00,
        'qualification' => 4,
        'comment' => 'Evaluación de prueba con datos importantes',
        'wasEvaluated' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $testEvalPersonIds[] = $evalPerson;
    echo "  - Creado EvaluationPerson ID: {$evalPerson} → detail_id: {$detail->id} (objective: {$detail->objective_id})" . PHP_EOL;
}

// 3. Simular eliminación de details (lo que causa el problema)
echo PHP_EOL . "3. Eliminando (soft delete) los details..." . PHP_EOL;

$oldDetailIds = [];
foreach ($currentDetails->take(2) as $detail) {
    DB::table('gh_evaluation_person_cycle_detail')
        ->where('id', $detail->id)
        ->update(['deleted_at' => now()]);
    $oldDetailIds[] = $detail->id;
    echo "  - Detail ID {$detail->id} marcado como eliminado" . PHP_EOL;
}

// 4. Crear nuevos details activos (regeneración)
echo PHP_EOL . "4. Creando nuevos details activos (simulando regeneración)..." . PHP_EOL;

$newDetailIds = [];
foreach ($currentDetails->take(2) as $oldDetail) {
    $newDetailId = DB::table('gh_evaluation_person_cycle_detail')->insertGetId([
        'person_id' => $oldDetail->person_id,
        'chief_id' => $oldDetail->chief_id,
        'position_id' => $oldDetail->position_id,
        'sede_id' => $oldDetail->sede_id,
        'area_id' => $oldDetail->area_id,
        'cycle_id' => $oldDetail->cycle_id,
        'category_id' => $oldDetail->category_id,
        'objective_id' => $oldDetail->objective_id,
        'isAscending' => $oldDetail->isAscending,
        'person' => $oldDetail->person,
        'chief' => $oldDetail->chief,
        'position' => $oldDetail->position,
        'sede' => $oldDetail->sede,
        'area' => $oldDetail->area,
        'category' => $oldDetail->category,
        'objective' => $oldDetail->objective,
        'goal' => $oldDetail->goal,
        'weight' => $oldDetail->weight,
        'metric' => $oldDetail->metric,
        'end_date_objectives' => $oldDetail->end_date_objectives,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $newDetailIds[] = $newDetailId;
    echo "  - Nuevo detail ID {$newDetailId} creado (objective: {$oldDetail->objective_id})" . PHP_EOL;
}

// 5. Verificar que hay huérfanos
echo PHP_EOL . "5. Verificando que los EvaluationPerson quedaron huérfanos..." . PHP_EOL;

$orphaned = DB::table('gh_evaluation_person as ep')
    ->leftJoin('gh_evaluation_person_cycle_detail as pcd', 'ep.person_cycle_detail_id', '=', 'pcd.id')
    ->whereIn('ep.id', $testEvalPersonIds)
    ->where(function($query) {
        $query->whereNotNull('pcd.deleted_at')
              ->orWhereNull('pcd.id');
    })
    ->whereNull('ep.deleted_at')
    ->select('ep.id', 'ep.result', 'ep.compliance', 'ep.comment', 'ep.person_cycle_detail_id')
    ->get();

echo "  - EvaluationPerson huérfanos: " . count($orphaned) . PHP_EOL;
foreach ($orphaned as $o) {
    echo "    * ID: {$o->id} | result: {$o->result} | compliance: {$o->compliance} | detail_id: {$o->person_cycle_detail_id}" . PHP_EOL;
}

// 6. Ejecutar la revalidación que debe actualizar las referencias
echo PHP_EOL . "6. Ejecutando revalidación para actualizar referencias..." . PHP_EOL;

$service = app(EvaluationPersonCycleDetailService::class);
$result = $service->revalidateAllPersonsInCycle($cycleId);

echo "  - Registros actualizados/limpiados: {$result['orphaned_records_cleaned']}" . PHP_EOL;

// 7. Verificar que las referencias fueron actualizadas
echo PHP_EOL . "7. Verificando que las referencias fueron actualizadas..." . PHP_EOL;

$updatedEvalPersons = DB::table('gh_evaluation_person')
    ->whereIn('id', $testEvalPersonIds)
    ->whereNull('deleted_at')
    ->get();

echo "  - EvaluationPerson que siguen activos: " . count($updatedEvalPersons) . PHP_EOL;

$success = true;
foreach ($updatedEvalPersons as $ep) {
    $detail = DB::table('gh_evaluation_person_cycle_detail')
        ->where('id', $ep->person_cycle_detail_id)
        ->first();

    $detailStatus = $detail ? ($detail->deleted_at ? "ELIMINADO ❌" : "ACTIVO ✓") : "NO EXISTE ❌";
    $dataPreserved = ($ep->result == 85.50 && $ep->compliance == 90.00) ? "✓" : "❌";

    echo "    * EvalPerson ID: {$ep->id}" . PHP_EOL;
    echo "      - detail_id: {$ep->person_cycle_detail_id} ({$detailStatus})" . PHP_EOL;
    echo "      - result: {$ep->result}, compliance: {$ep->compliance} (preservado: {$dataPreserved})" . PHP_EOL;

    if ($detail && !$detail->deleted_at && $ep->result == 85.50) {
        echo "      ✓ Referencias actualizadas y datos preservados correctamente" . PHP_EOL;
    } else {
        $success = false;
        echo "      ❌ FALLÓ: Referencias no actualizadas o datos perdidos" . PHP_EOL;
    }
}

// 8. Limpiar datos de prueba
echo PHP_EOL . "8. Limpiando datos de prueba..." . PHP_EOL;

DB::table('gh_evaluation_person')->whereIn('id', $testEvalPersonIds)->delete();
DB::table('gh_evaluation_person_cycle_detail')->whereIn('id', array_merge($oldDetailIds, $newDetailIds))->forceDelete();

echo "  ✓ Datos de prueba eliminados" . PHP_EOL;

echo PHP_EOL . "=== RESULTADO FINAL: " . ($success ? "✓ EXITOSO" : "❌ FALLÓ") . " ===" . PHP_EOL;

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\gp\gestionsistema\View;

echo "=== Consulta ACTUAL del CrudPermissionsSeeder ===\n";
$currentQuery = View::where(function ($query) {
    $query->whereNull('status_deleted')
        ->orWhere('status_deleted', '=', 1)
        ->whereNotNull('route');
});

echo "SQL: " . $currentQuery->toSql() . "\n";
echo "Total: " . $currentQuery->count() . "\n\n";

echo "=== Consulta CORREGIDA ===\n";
$correctedQuery = View::where(function ($query) {
    $query->whereNull('status_deleted')
        ->orWhere('status_deleted', '=', 1);
})->whereNotNull('route');

echo "SQL: " . $correctedQuery->toSql() . "\n";
echo "Total: " . $correctedQuery->count() . "\n\n";

echo "=== Vistas de Viaticos (company_id=3) ===\n";
$viaticosAP = View::where('company_id', 3)
    ->where(function ($query) {
        $query->whereNull('status_deleted')
            ->orWhere('status_deleted', '=', 1);
    })
    ->whereNotNull('route')
    ->get(['id', 'descripcion', 'route', 'parent_id', 'status_deleted']);

echo json_encode($viaticosAP->toArray(), JSON_PRETTY_PRINT) . "\n";

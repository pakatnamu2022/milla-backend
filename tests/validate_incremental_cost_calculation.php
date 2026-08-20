<?php

/**
 * Script de validación numérica para el cálculo incremental de costos
 *
 * Este script valida que el método incremental produzca exactamente los mismos
 * resultados que el método de rebuild completo usando datos REALES de producción.
 *
 * Ejecutar: php tests/validate_incremental_cost_calculation.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;

// Productos a validar (datos reales de producción)
$testCases = [
    ['product_id' => 1938, 'warehouse_id' => 166, 'expected_movements' => 388],
    ['product_id' => 23, 'warehouse_id' => 164, 'expected_movements' => 343],
    ['product_id' => 28, 'warehouse_id' => 164, 'expected_movements' => 340],
];

$service = app(ProductWarehouseStockService::class);
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  VALIDACIÓN NUMÉRICA: Método Incremental vs Rebuild Completo\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

foreach ($testCases as $testCase) {
    $productId = $testCase['product_id'];
    $warehouseId = $testCase['warehouse_id'];

    echo "\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo "Producto ID: {$productId}, Almacén ID: {$warehouseId}\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo "\n";

    // PASO 1: Obtener estado actual
    $currentStock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->first();

    if (!$currentStock) {
        echo "⊘ SKIPPED: No existe stock para este producto/almacén\n";
        continue;
    }

    echo "Estado actual en BD:\n";
    echo "  • Stock:          {$currentStock->quantity}\n";
    echo "  • Costo promedio: {$currentStock->average_cost}\n";
    echo "  • Último costo:   {$currentStock->cost_price}\n";
    echo "\n";

    // PASO 2: Obtener historial usando método existente
    echo "Obteniendo historial completo (método actual)...\n";

    try {
        $historyData = $service->getStockMovementHistory($productId, $warehouseId);
    } catch (Exception $e) {
        echo "✗ ERROR: {$e->getMessage()}\n";
        $failedTests++;
        continue;
    }

    if (!$historyData['success']) {
        echo "✗ ERROR: No se pudo obtener historial de movimientos\n";
        $failedTests++;
        continue;
    }

    $totalMovements = count($historyData['history']);
    echo "  ✓ Total de movimientos: {$totalMovements}\n";
    echo "  ✓ Stock calculado:      {$historyData['calculated_final_stock']}\n";
    echo "  ✓ Costo promedio calc.: {$historyData['calculated_final_average_cost']}\n";
    echo "\n";

    // PASO 3: Simular cálculo INCREMENTAL paso a paso
    echo "Simulando cálculo INCREMENTAL (método nuevo)...\n";

    $runningStock = 0;
    $runningAverageCost = 0;
    $movementsProcessed = 0;
    $inboundCount = 0;
    $outboundCount = 0;
    $discrepanciesFound = false;

    foreach ($historyData['history'] as $index => $item) {
        $isInbound = $item['is_inbound'];
        $quantity = (float)$item['quantity'];
        $unitCost = (float)($item['unit_cost_in_pen'] ?? 0);

        $stockBefore = $runningStock;
        $costBefore = $runningAverageCost;

        if ($isInbound) {
            $inboundCount++;
            // INBOUND: Aplicar fórmula de costo promedio ponderado
            $runningStock += $quantity;

            if ($unitCost > 0) {
                if ($stockBefore + $quantity > 0) {
                    $runningAverageCost = (($stockBefore * $costBefore) + ($quantity * $unitCost)) / ($stockBefore + $quantity);
                    $runningAverageCost = round($runningAverageCost, 2);
                } else {
                    $runningAverageCost = $unitCost;
                }
            }
        } else {
            $outboundCount++;
            // OUTBOUND: Solo reducir stock, costo promedio NO cambia
            $runningStock -= $quantity;
            if ($runningStock < 0) {
                $runningStock = 0;
            }
        }

        $movementsProcessed++;

        // Comparar con el valor esperado
        $expectedStock = (float)$item['stock_after_movement'];
        $expectedCost = (float)$item['average_cost_after_movement'];

        $stockDiff = abs($runningStock - $expectedStock);
        $costDiff = abs($runningAverageCost - $expectedCost);

        // Tolerancia de 0.01 para diferencias de redondeo
        if ($stockDiff > 0.01 || $costDiff > 0.01) {
            $movementNum = $index + 1;
            echo "\n";
            echo "  ✗ DISCREPANCIA en movimiento #{$movementNum}:\n";
            echo "    Tipo:  " . ($isInbound ? 'ENTRADA' : 'SALIDA') . "\n";
            echo "    Fecha: {$item['movement_date']}\n";
            echo "    Cant:  {$quantity}\n";
            echo "    Costo: {$unitCost}\n";
            echo "    Stock incremental: {$runningStock} vs Esperado: {$expectedStock} (diff: {$stockDiff})\n";
            echo "    Costo incremental: {$runningAverageCost} vs Esperado: {$expectedCost} (diff: {$costDiff})\n";
            echo "\n";
            $discrepanciesFound = true;
            break; // Detener en la primera discrepancia
        }
    }

    if ($discrepanciesFound) {
        echo "✗ FALLÓ: Los cálculos incrementales NO coinciden\n";
        $failedTests++;
        $totalTests++;
        continue;
    }

    echo "  ✓ Movimientos procesados: {$movementsProcessed}\n";
    echo "  ✓ Entradas (INBOUND):     {$inboundCount}\n";
    echo "  ✓ Salidas (OUTBOUND):     {$outboundCount}\n";
    echo "  ✓ Stock final:            {$runningStock}\n";
    echo "  ✓ Costo promedio final:   {$runningAverageCost}\n";
    echo "\n";

    // PASO 4: Comparación final
    echo "Comparación FINAL:\n";
    echo "┌─────────────────────────┬──────────────────┬──────────────────┬────────────┐\n";
    echo "│ Métrica                 │ Incremental      │ Rebuild Completo │ Diferencia │\n";
    echo "├─────────────────────────┼──────────────────┼──────────────────┼────────────┤\n";

    $stockDiffFinal = abs($runningStock - $historyData['calculated_final_stock']);
    $costDiffFinal = abs($runningAverageCost - $historyData['calculated_final_average_cost']);

    printf("│ Stock final             │ %16.4f │ %16.4f │ %10.4f │\n",
        $runningStock,
        $historyData['calculated_final_stock'],
        $stockDiffFinal
    );

    printf("│ Costo promedio final    │ %16.2f │ %16.2f │ %10.2f │\n",
        $runningAverageCost,
        $historyData['calculated_final_average_cost'],
        $costDiffFinal
    );

    echo "└─────────────────────────┴──────────────────┴──────────────────┴────────────┘\n";
    echo "\n";

    // Validar con precisión de 2 decimales
    $stockMatches = (round($runningStock, 4) == round($historyData['calculated_final_stock'], 4));
    $costMatches = (round($runningAverageCost, 2) == round($historyData['calculated_final_average_cost'], 2));

    if ($stockMatches && $costMatches) {
        echo "✓ ÉXITO: El método incremental produce EXACTAMENTE los mismos resultados\n";
        echo "         ({$totalMovements} movimientos históricos procesados)\n";
        $passedTests++;
    } else {
        echo "✗ FALLÓ: Las diferencias finales exceden la tolerancia\n";
        $failedTests++;
    }

    $totalTests++;
}

// RESUMEN FINAL
echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE VALIDACIÓN\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "Total de tests ejecutados: {$totalTests}\n";
echo "Tests exitosos:            {$passedTests}\n";
echo "Tests fallidos:            {$failedTests}\n";
echo "\n";

if ($failedTests == 0 && $totalTests > 0) {
    echo "✓✓✓ TODOS LOS TESTS PASARON ✓✓✓\n";
    echo "\n";
    echo "El método incremental ha sido validado con éxito usando datos reales.\n";
    echo "Se puede proceder con la implementación completa.\n";
    echo "\n";
    exit(0);
} else {
    echo "✗✗✗ ALGUNOS TESTS FALLARON ✗✗✗\n";
    echo "\n";
    echo "ADVERTENCIA: El método incremental NO produce resultados idénticos.\n";
    echo "Revisa la lógica de cálculo antes de proceder.\n";
    echo "\n";
    exit(1);
}
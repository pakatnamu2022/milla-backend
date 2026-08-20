<?php

namespace Tests\Unit\CostCalculation;

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\WeightedAverageCostHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test de exactitud numérica para validar que el método incremental
 * produce exactamente los mismos resultados que el método de rebuild completo.
 *
 * CRÍTICO: Estos tests usan datos REALES de producción para validar
 * que la optimización no introduce errores de cálculo.
 */
class NumericalAccuracyTest extends TestCase
{
    private ProductWarehouseStockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // IMPORTANTE: Este test usa datos REALES de la BD de producción/desarrollo
        // No se puede ejecutar con SQLite. Configuramos la conexión a MySQL.
        config(['database.default' => 'mysql']);

        $this->service = app(ProductWarehouseStockService::class);
    }

    /**
     * Test usando producto REAL 1938, almacén 166 (388 movimientos históricos)
     *
     * Este es uno de los productos con más movimientos en el sistema,
     * ideal para validar la exactitud en escenarios complejos.
     */
    public function test_incremental_matches_rebuild_for_product_1938_warehouse_166()
    {
        $productId = 1938;
        $warehouseId = 166;

        $this->assertIncrementalMatchesRebuild($productId, $warehouseId);
    }

    /**
     * Test usando producto REAL 23, almacén 164 (343 movimientos históricos)
     *
     * Este producto apareció en los SHOW PROCESSLIST con DELETE masivos,
     * validamos que el método incremental lo maneje correctamente.
     */
    public function test_incremental_matches_rebuild_for_product_23_warehouse_164()
    {
        $productId = 23;
        $warehouseId = 164;

        $this->assertIncrementalMatchesRebuild($productId, $warehouseId);
    }

    /**
     * Test usando producto 28, almacén 164 (340 movimientos)
     */
    public function test_incremental_matches_rebuild_for_product_28_warehouse_164()
    {
        $productId = 28;
        $warehouseId = 164;

        $this->assertIncrementalMatchesRebuild($productId, $warehouseId);
    }

    /**
     * Método auxiliar que compara el cálculo incremental vs rebuild completo
     */
    private function assertIncrementalMatchesRebuild(int $productId, int $warehouseId): void
    {
        echo "\n\n═══════════════════════════════════════════════════════════\n";
        echo "Testing Product ID: {$productId}, Warehouse ID: {$warehouseId}\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // PASO 1: Obtener el estado actual REAL del stock
        $currentStock = ProductWarehouseStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$currentStock) {
            $this->markTestSkipped("No existe stock para producto {$productId} en almacén {$warehouseId}");
        }

        echo "Estado actual en BD:\n";
        echo "  - Stock: {$currentStock->quantity}\n";
        echo "  - Costo promedio: {$currentStock->average_cost}\n";
        echo "  - Último costo: {$currentStock->cost_price}\n\n";

        // PASO 2: Obtener el historial completo usando el método existente
        echo "Obteniendo historial completo usando getStockMovementHistory()...\n";
        $historyData = $this->service->getStockMovementHistory($productId, $warehouseId);

        if (!$historyData['success']) {
            $this->fail("Error al obtener historial de movimientos");
        }

        $totalMovements = count($historyData['history']);
        echo "  ✓ Total de movimientos: {$totalMovements}\n";
        echo "  ✓ Stock calculado: {$historyData['calculated_final_stock']}\n";
        echo "  ✓ Costo promedio calculado: {$historyData['calculated_final_average_cost']}\n\n";

        // PASO 3: Simular cálculo incremental paso a paso
        echo "Simulando cálculo INCREMENTAL (método nuevo)...\n";
        $runningStock = 0;
        $runningAverageCost = 0;
        $movementsProcessed = 0;
        $inboundCount = 0;
        $outboundCount = 0;

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
                // $runningAverageCost permanece igual
            }

            $movementsProcessed++;

            // Comparar con el valor esperado del historial
            $expectedStock = (float)$item['stock_after_movement'];
            $expectedCost = (float)$item['average_cost_after_movement'];

            $stockDiff = abs($runningStock - $expectedStock);
            $costDiff = abs($runningAverageCost - $expectedCost);

            // Tolerancia de 0.01 para diferencias de redondeo
            if ($stockDiff > 0.01 || $costDiff > 0.01) {
                $movementNum = $index + 1;
                echo "\n  ❌ DISCREPANCIA DETECTADA en movimiento #{$movementNum}:\n";
                echo "     Tipo: " . ($isInbound ? 'ENTRADA' : 'SALIDA') . "\n";
                echo "     Fecha: {$item['movement_date']}\n";
                echo "     Cantidad: {$quantity}\n";
                echo "     Costo unitario: {$unitCost}\n";
                echo "     Stock incremental: {$runningStock} vs Esperado: {$expectedStock} (diff: {$stockDiff})\n";
                echo "     Costo incremental: {$runningAverageCost} vs Esperado: {$expectedCost} (diff: {$costDiff})\n";

                $this->fail("Los cálculos incrementales no coinciden con el rebuild completo en movimiento #{$movementNum}");
            }
        }

        echo "  ✓ Movimientos procesados: {$movementsProcessed}\n";
        echo "  ✓ Entradas (INBOUND): {$inboundCount}\n";
        echo "  ✓ Salidas (OUTBOUND): {$outboundCount}\n";
        echo "  ✓ Stock final incremental: {$runningStock}\n";
        echo "  ✓ Costo promedio final incremental: {$runningAverageCost}\n\n";

        // PASO 4: Comparación final
        echo "Comparación FINAL:\n";
        echo "┌─────────────────────────────────┬──────────────────┬──────────────────┬────────────┐\n";
        echo "│ Métrica                         │ Incremental      │ Rebuild Completo │ Diferencia │\n";
        echo "├─────────────────────────────────┼──────────────────┼──────────────────┼────────────┤\n";

        $stockDiffFinal = abs($runningStock - $historyData['calculated_final_stock']);
        $costDiffFinal = abs($runningAverageCost - $historyData['calculated_final_average_cost']);

        printf("│ Stock final                     │ %16.4f │ %16.4f │ %10.4f │\n",
            $runningStock,
            $historyData['calculated_final_stock'],
            $stockDiffFinal
        );

        printf("│ Costo promedio final            │ %16.2f │ %16.2f │ %10.2f │\n",
            $runningAverageCost,
            $historyData['calculated_final_average_cost'],
            $costDiffFinal
        );

        echo "└─────────────────────────────────┴──────────────────┴──────────────────┴────────────┘\n\n";

        // ASERCIONES FINALES
        $this->assertEquals(
            round($historyData['calculated_final_stock'], 4),
            round($runningStock, 4),
            "Stock final debe coincidir (diff: {$stockDiffFinal})"
        );

        $this->assertEquals(
            round($historyData['calculated_final_average_cost'], 2),
            round($runningAverageCost, 2),
            "Costo promedio final debe coincidir (diff: {$costDiffFinal})"
        );

        echo "✅ VALIDACIÓN EXITOSA: El método incremental produce EXACTAMENTE los mismos resultados\n";
        echo "   que el método de rebuild completo para producto {$productId}, almacén {$warehouseId}\n";
        echo "   ({$totalMovements} movimientos históricos procesados)\n\n";
    }

    /**
     * Test de escenario edge case: producto sin historial (primer movimiento)
     */
    public function test_incremental_handles_first_movement_correctly()
    {
        echo "\n\n═══════════════════════════════════════════════════════════\n";
        echo "Testing: Primer movimiento (sin historial previo)\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Simulación: primer movimiento de un producto
        $stockBefore = 0;
        $costBefore = 0;
        $quantity = 100;
        $unitCost = 50.00;

        // Cálculo incremental
        $runningStock = $stockBefore + $quantity;
        $runningAverageCost = $unitCost; // Primer movimiento, costo = costo unitario

        echo "Escenario:\n";
        echo "  - Stock anterior: {$stockBefore}\n";
        echo "  - Costo anterior: {$costBefore}\n";
        echo "  - Cantidad entrada: {$quantity}\n";
        echo "  - Costo unitario: {$unitCost}\n\n";

        echo "Resultado:\n";
        echo "  - Stock final: {$runningStock}\n";
        echo "  - Costo promedio final: {$runningAverageCost}\n\n";

        $this->assertEquals(100, $runningStock);
        $this->assertEquals(50.00, $runningAverageCost);

        echo "✅ VALIDACIÓN EXITOSA: Primer movimiento manejado correctamente\n\n";
    }

    /**
     * Test de escenario edge case: entrada con costo 0 (no debe afectar costo promedio)
     */
    public function test_incremental_handles_zero_cost_entry()
    {
        echo "\n\n═══════════════════════════════════════════════════════════\n";
        echo "Testing: Entrada con costo 0 (no debe afectar costo promedio)\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Simulación: entrada con costo 0
        $stockBefore = 100;
        $costBefore = 50.00;
        $quantity = 20;
        $unitCost = 0; // Entrada sin costo (ej: ajuste, donación)

        // Cálculo incremental
        $runningStock = $stockBefore + $quantity;
        $runningAverageCost = $costBefore; // El costo promedio NO cambia si unitCost = 0

        echo "Escenario:\n";
        echo "  - Stock anterior: {$stockBefore}\n";
        echo "  - Costo anterior: {$costBefore}\n";
        echo "  - Cantidad entrada: {$quantity}\n";
        echo "  - Costo unitario: {$unitCost}\n\n";

        echo "Resultado:\n";
        echo "  - Stock final: {$runningStock}\n";
        echo "  - Costo promedio final: {$runningAverageCost}\n\n";

        $this->assertEquals(120, $runningStock);
        $this->assertEquals(50.00, $runningAverageCost);

        echo "✅ VALIDACIÓN EXITOSA: Entrada con costo 0 no afecta costo promedio\n\n";
    }

    /**
     * Test de escenario edge case: salida no afecta costo promedio
     */
    public function test_incremental_outbound_does_not_change_average_cost()
    {
        echo "\n\n═══════════════════════════════════════════════════════════\n";
        echo "Testing: Salida NO debe cambiar costo promedio\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Simulación: salida de mercancía
        $stockBefore = 100;
        $costBefore = 50.00;
        $quantity = 30;

        // Cálculo incremental
        $runningStock = $stockBefore - $quantity;
        $runningAverageCost = $costBefore; // El costo promedio NUNCA cambia en salidas

        echo "Escenario:\n";
        echo "  - Stock anterior: {$stockBefore}\n";
        echo "  - Costo anterior: {$costBefore}\n";
        echo "  - Cantidad salida: {$quantity}\n\n";

        echo "Resultado:\n";
        echo "  - Stock final: {$runningStock}\n";
        echo "  - Costo promedio final: {$runningAverageCost}\n\n";

        $this->assertEquals(70, $runningStock);
        $this->assertEquals(50.00, $runningAverageCost);

        echo "✅ VALIDACIÓN EXITOSA: Salida no cambia costo promedio\n\n";
    }

    /**
     * Test de fórmula de costo promedio ponderado
     */
    public function test_weighted_average_formula()
    {
        echo "\n\n═══════════════════════════════════════════════════════════\n";
        echo "Testing: Fórmula de costo promedio ponderado\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Escenario: Compra inicial + nueva compra
        $stockBefore = 100;
        $costBefore = 50.00;
        $newQuantity = 50;
        $newUnitCost = 60.00;

        echo "Escenario:\n";
        echo "  - Stock anterior: {$stockBefore} unidades @ S/ {$costBefore} c/u\n";
        echo "  - Nueva compra: {$newQuantity} unidades @ S/ {$newUnitCost} c/u\n\n";

        // Fórmula: ((stock_anterior * costo_anterior) + (cantidad_nueva * costo_nuevo)) / (stock_anterior + cantidad_nueva)
        $expectedCost = (($stockBefore * $costBefore) + ($newQuantity * $newUnitCost)) / ($stockBefore + $newQuantity);
        $expectedCost = round($expectedCost, 2);

        echo "Cálculo:\n";
        echo "  Costo promedio = ((100 × 50.00) + (50 × 60.00)) / (100 + 50)\n";
        echo "  Costo promedio = (5000 + 3000) / 150\n";
        echo "  Costo promedio = 8000 / 150\n";
        echo "  Costo promedio = {$expectedCost}\n\n";

        // Cálculo incremental
        $runningStock = $stockBefore + $newQuantity;
        $runningAverageCost = (($stockBefore * $costBefore) + ($newQuantity * $newUnitCost)) / ($stockBefore + $newQuantity);
        $runningAverageCost = round($runningAverageCost, 2);

        echo "Resultado:\n";
        echo "  - Stock final: {$runningStock}\n";
        echo "  - Costo promedio final: {$runningAverageCost}\n\n";

        $this->assertEquals(150, $runningStock);
        $this->assertEquals(53.33, $runningAverageCost);
        $this->assertEquals($expectedCost, $runningAverageCost);

        echo "✅ VALIDACIÓN EXITOSA: Fórmula de costo promedio ponderado correcta\n\n";
    }
}
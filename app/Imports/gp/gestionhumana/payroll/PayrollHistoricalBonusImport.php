<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\GpMasters;
use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa el histórico mensual de bono/comisión por trabajador+mes hacia gh_payroll_bonuses,
 * para que PayrollCalculation::calcularPromedioUltimos6Meses() (usada por gratificación/CTS)
 * promedie el bono real en vez de S/ 0.00 — confirmado que la tabla estaba vacía en producción
 * (caso DNI 42330495: bono promedio real S/ 660.39/862.68 según el Excel fuente, el sistema
 * devolvía 0 por falta de estos datos).
 *
 * Columnas esperadas (ver PayrollHistoricalBonusTemplateExport):
 * DNI | TRABAJADOR | ANIO | MES | BONO
 *
 * Mismo patrón que PayrollHistoricalCalculationImport: formato "largo", crea el PayrollPeriod si
 * no existe todavía, no dispara ningún recálculo (el promedio se recalcula en caliente).
 */
class PayrollHistoricalBonusImport implements ToCollection, WithHeadingRow
{
    public int $companyId;

    /** @var array<int, string> Filas con error (DNI no encontrado, datos inválidos, etc.) */
    public array $skipped = [];

    /** @var array<int, string> Periodos (year-month) creados porque no existían todavía */
    public array $periodsCreated = [];

    public int $rowsProcessed = 0;

    private ?int $bonusTypeId = null;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->bonusTypeId = GpMasters::where('type', 'PAYROLL_BONUS')
            ->where('code', 'BONO_CONDUCTOR')
            ->value('id');
    }

    public function collection(Collection $rows)
    {
        if (!$this->bonusTypeId) {
            $this->skipped[] = 'Falta sembrar el catálogo de tipos de bono (PayrollBonusTypeSeeder).';
            return;
        }

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            $dni = trim((string) ($row['dni'] ?? ''));
            $year = (int) ($row['anio'] ?? 0);
            $month = (int) ($row['mes'] ?? 0);

            if ($dni === '' || $year <= 0 || $month < 1 || $month > 12) {
                $this->skipped[] = "Fila {$line}: DNI, año o mes inválido";
                continue;
            }

            $worker = Worker::where('vat', $dni)->first();
            if (!$worker) {
                $this->skipped[] = "Fila {$line}: no se encontró trabajador con DNI {$dni}";
                continue;
            }

            $amount = (float) ($row['bono'] ?? 0);
            if ($amount <= 0) {
                // Fila vacía en la plantilla (trabajador+mes sin bono) — se omite en silencio,
                // no cuenta como error.
                continue;
            }

            $period = PayrollPeriod::firstOrCreate(
                ['company_id' => $this->companyId, 'year' => $year, 'month' => $month],
                [
                    'code' => PayrollPeriod::generateCode($year, $month),
                    'name' => PayrollPeriod::generateName($year, $month),
                    'start_date' => sprintf('%04d-%02d-01', $year, $month),
                    'end_date' => \Carbon\Carbon::create($year, $month, 1)->endOfMonth(),
                    'status' => PayrollPeriod::STATUS_CALCULATED,
                ]
            );

            if ($period->wasRecentlyCreated) {
                $this->periodsCreated[] = "{$year}-{$month}";
            }

            PayrollBonus::updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'period_id' => $period->id,
                    'type_id' => $this->bonusTypeId,
                ],
                [
                    'amount' => $amount,
                    'status' => 1,
                ]
            );

            $this->rowsProcessed++;
        }
    }

    public function headingRow(): int
    {
        return 1;
    }
}

<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

/**
 * Importa bonificaciones (gh_payroll_bonuses) desde el Excel matriz generado por
 * PayrollBonusTemplateExport (fila 2 = cabecera con DNI en A y una columna "MM/AAAA" por
 * periodo desde B, fila 3 en adelante = una fila por trabajador), para una empresa y tipo de bono
 * fijos (elegidos en el modal antes de subir el archivo). El periodo de cada columna se crea
 * automáticamente si no existe todavía (mismo criterio que PayrollHistoricalBonusImport).
 */
class PayrollBonusImport implements ToCollection
{
    private int $companyId;
    private int $typeId;
    private array $periodCache = [];
    private array $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => [],
        'rows_processed' => 0,
        'skipped' => 0,
    ];

    public function __construct(int $companyId, int $typeId)
    {
        $this->companyId = $companyId;
        $this->typeId = $typeId;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->count() < 2) {
            $this->results['errors'][] = 'El archivo no tiene el formato esperado (falta la cabecera de periodos).';
            return;
        }

        // Fila 0: título (ignorar). Fila 1: cabeceras → A = DNI, B en adelante = un periodo
        // "MM/AAAA" por columna. Fila 2 en adelante: un trabajador por fila.
        $headerRow = $rows[1]->toArray();
        $columnPeriods = $this->parseHeaderPeriods($headerRow);

        if (empty($columnPeriods)) {
            $this->results['errors'][] = 'El archivo no tiene columnas de periodo válidas (formato MM/AAAA) en la cabecera.';
            return;
        }

        for ($i = 2; $i < $rows->count(); $i++) {
            $rowNumber = $i + 1; // +1 para numeración de Excel
            $rowArray = $rows[$i]->toArray();

            $dni = isset($rowArray[0]) ? trim((string) $rowArray[0]) : null;

            if (empty($dni)) {
                continue;
            }

            $dniNormalized = str_pad($dni, 8, '0', STR_PAD_LEFT);
            $worker = Worker::withoutGlobalScope('working')
                ->where('vat', $dniNormalized)
                ->first();

            if (!$worker) {
                $this->results['errors'][] = "Fila {$rowNumber}: no se encontró trabajador con DNI {$dniNormalized}";
                continue;
            }

            foreach ($columnPeriods as $columnIndex => $period) {
                $amount = $rowArray[$columnIndex] ?? null;
                if ($amount === null || $amount === '') {
                    continue;
                }

                $amountValue = $this->parseDecimal($amount);
                if ($amountValue <= 0) {
                    // Celda vacía en la plantilla (trabajador sin bono ese mes) — se omite en
                    // silencio, no cuenta como error.
                    continue;
                }

                try {
                    $this->saveBonus($worker->id, $period['year'], $period['month'], $amountValue);
                    $this->results['rows_processed']++;
                } catch (Exception $e) {
                    $this->results['errors'][] = "Fila {$rowNumber} ({$period['month']}/{$period['year']}): " . $e->getMessage();
                }
            }
        }
    }

    /**
     * @return array<int, array{year:int,month:int}> indexado por posición de columna (0-based)
     */
    private function parseHeaderPeriods(array $headerRow): array
    {
        $periods = [];
        foreach ($headerRow as $columnIndex => $label) {
            if ($columnIndex < 1) {
                continue; // A = DNI
            }
            $label = trim((string) $label);
            if ($label === '' || !preg_match('/^(\d{1,2})\/(\d{4})$/', $label, $matches)) {
                continue;
            }
            $month = (int) $matches[1];
            $year = (int) $matches[2];
            if ($month < 1 || $month > 12) {
                continue;
            }
            $periods[$columnIndex] = ['year' => $year, 'month' => $month];
        }

        return $periods;
    }

    private function saveBonus(int $workerId, int $year, int $month, float $amount): void
    {
        $period = $this->resolvePeriod($year, $month);

        DB::beginTransaction();
        try {
            $record = PayrollBonus::updateOrCreate(
                [
                    'worker_id' => $workerId,
                    'period_id' => $period->id,
                    'type_id' => $this->typeId,
                ],
                [
                    'amount' => $amount,
                    'status' => 1,
                ]
            );

            if ($record->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function resolvePeriod(int $year, int $month): PayrollPeriod
    {
        $key = "{$year}-{$month}";
        if (isset($this->periodCache[$key])) {
            return $this->periodCache[$key];
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

        $this->periodCache[$key] = $period;

        return $period;
    }

    private function parseDecimal($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^\d.,\-]/', '', trim($value));
            $value = str_replace(',', '', $value);
        }

        return (float) number_format((float) $value, 2, '.', '');
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

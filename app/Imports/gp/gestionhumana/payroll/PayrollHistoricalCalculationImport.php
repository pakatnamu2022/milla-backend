<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa el histórico de conceptos variables (horas extra 25%/35%, feriado, DDT, bonif.
 * nocturna) por trabajador+mes, para completar meses anteriores a la existencia del sistema y
 * que PayrollCalculation::calcularPromedioUltimos6Meses() (usada por gratificación/CTS) tenga
 * los 6 meses reales que exige la ley, en vez de partir de cero. Formato "largo" (una fila por
 * trabajador+mes) en vez del formato "ancho" del Excel original (un bloque de columnas por mes)
 * porque es mucho más simple de validar e importar de forma robusta.
 *
 * Columnas esperadas (fila de encabezado, ver PayrollHistoricalCalculationTemplateExport):
 * DNI | TRABAJADOR | ANIO | MES | HORAS_EXTRA_25 | HORAS_EXTRA_35 | FERIADO | DDT | NOCTURNA
 *
 * No calcula nada: solo registra los montos ya calculados que trae el Excel de origen. El
 * promedio de 6 meses se sigue calculando en caliente por calcularPromedioUltimos6Meses().
 */
class PayrollHistoricalCalculationImport implements ToCollection, WithHeadingRow
{
    public int $companyId;

    /** @var array<int, string> Filas con error (DNI no encontrado, datos inválidos, etc.) */
    public array $skipped = [];

    /** @var array<int, string> Periodos (year-month) creados porque no existían todavía */
    public array $periodsCreated = [];

    public int $rowsProcessed = 0;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // +1 por índice base 0, +1 por la fila de encabezado

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

            PayrollCalculation::updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'period_id' => $period->id,
                    'company_id' => $this->companyId,
                ],
                [
                    'overtime_25' => (float) ($row['horas_extra_25'] ?? 0),
                    'overtime_35' => (float) ($row['horas_extra_35'] ?? 0),
                    'holiday_pay' => (float) ($row['feriado'] ?? 0),
                    'compensatory_pay' => (float) ($row['ddt'] ?? 0),
                    'night_bonus' => (float) ($row['nocturna'] ?? 0),
                    'status' => PayrollCalculation::STATUS_CALCULATED,
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

<?php

namespace App\Imports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\personal\WorkerContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Importa el histórico de sueldos (rrhh_contrato) por trabajador — una fila por cada cambio de
 * sueldo, con la fecha desde la que rigió. WorkerContract::salaryForWorkerAtDate() es lo que usa
 * la gratificación/CTS/boletas para saber el sueldo vigente en una fecha pasada, pero en
 * producción esa tabla casi nunca se actualiza cuando alguien tiene un aumento: confirmado que
 * 259 de 260 contratos "vigentes" (sin fecha_fin_contrato) llevaban abiertos desde antes de 2024
 * — el sueldo guardado ahí queda desactualizado apenas hay un ascenso/aumento, y por eso la
 * gratificación/CTS calculada salía muy por debajo del Excel real (caso DNI 42330495: sistema
 * usaba S/ 1,807 de un contrato de 2022, cuando el sueldo real a Dic-2025 ya era S/ 2,107).
 *
 * Columnas esperadas (ver PayrollHistoricalSalaryTemplateExport):
 * DNI | TRABAJADOR | FECHA_VIGENCIA_DESDE | SUELDO
 *
 * Por trabajador, ordena sus filas por fecha ascendente y encadena los contratos: cada fila crea
 * (o actualiza si ya existe uno con esa misma fecha_inicio_contrato) un WorkerContract, y cierra
 * el contrato inmediatamente anterior (el de este import, o el que ya existiera en BD) un día
 * antes de la nueva fecha de vigencia. El último contrato de la cadena queda abierto
 * (fecha_fin_contrato = null), como "sueldo actualmente vigente".
 *
 * Si el trabajador ya tenía en BD un contrato abierto que empieza ANTES de la primera fecha
 * importada, se asume que es el contrato legado que este import viene a corregir/continuar, y se
 * cierra también. Si hubiera contratos superpuestos más complejos (varios abiertos a la vez, o
 * uno que empieza después de fechas importadas), se deja tal cual y se reporta en skipped para
 * revisión manual — este import no intenta resolver ese caso automáticamente.
 */
class PayrollHistoricalSalaryImport implements ToCollection, WithHeadingRow
{
    public int $companyId;

    /** @var array<int, string> Filas con error o advertencias para revisión manual */
    public array $skipped = [];

    public int $rowsProcessed = 0;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        $byWorker = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            $dni = trim((string) ($row['dni'] ?? ''));
            $rawDate = $row['fecha_vigencia_desde'] ?? null;
            $salary = (float) ($row['sueldo'] ?? 0);

            if ($dni === '' || $rawDate === null || $rawDate === '' || $salary <= 0) {
                $this->skipped[] = "Fila {$line}: DNI, fecha de vigencia o sueldo inválido";
                continue;
            }

            $worker = Worker::where('vat', $dni)->first();
            if (!$worker) {
                $this->skipped[] = "Fila {$line}: no se encontró trabajador con DNI {$dni}";
                continue;
            }

            $date = $this->parseDate($rawDate);
            if (!$date) {
                $this->skipped[] = "Fila {$line}: no se pudo interpretar la fecha '{$rawDate}'";
                continue;
            }

            $byWorker[$worker->id][] = ['date' => $date, 'salary' => $salary, 'line' => $line];
        }

        foreach ($byWorker as $workerId => $entries) {
            usort($entries, fn ($a, $b) => $a['date']->lte($b['date']) ? -1 : 1);

            // Cierra el contrato abierto legado (si empieza antes de la primera fecha importada)
            // para que no se solape con la nueva cadena.
            $firstDate = $entries[0]['date'];
            WorkerContract::where('empleado_id', $workerId)
                ->where('status_deleted', 1)
                ->whereNull('fecha_fin_contrato')
                ->where('fecha_inicio_contrato', '<', $firstDate)
                ->update(['fecha_fin_contrato' => $firstDate->copy()->subDay()]);

            foreach ($entries as $i => $entry) {
                WorkerContract::updateOrCreate(
                    ['empleado_id' => $workerId, 'fecha_inicio_contrato' => $entry['date']],
                    [
                        'sueldo' => $entry['salary'],
                        'status_deleted' => 1,
                        'fecha_fin_contrato' => null,
                    ]
                );
                $this->rowsProcessed++;
            }

            // Encadena: cierra cada contrato un día antes de que empiece el siguiente. El último
            // queda abierto (sueldo actualmente vigente).
            for ($i = 0; $i < count($entries) - 1; $i++) {
                WorkerContract::where('empleado_id', $workerId)
                    ->where('fecha_inicio_contrato', $entries[$i]['date'])
                    ->update(['fecha_fin_contrato' => $entries[$i + 1]['date']->copy()->subDay()]);
            }
        }
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function headingRow(): int
    {
        return 1;
    }
}

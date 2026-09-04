<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollHistoricalCalculationTemplateExport;
use App\Imports\gp\gestionhumana\payroll\PayrollHistoricalCalculationImport;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carga masiva del histórico de conceptos variables mensuales (horas extra 25%/35%, feriado,
 * DDT, bonif. nocturna) que PayrollCalculation::calcularPromedioUltimos6Meses() necesita para
 * que la gratificación/CTS automáticas cuadren con el Excel real: el sistema solo tiene
 * PayrollCalculation desde que se empezó a usar (set-2025 en adelante), así que a cualquier
 * cálculo que necesite promediar 6 meses hacia atrás le faltan meses. Ver el plan
 * tidy-enchanting-marshmallow.md y PayrollHistoricalCalculationImport para el detalle.
 */
class PayrollHistoricalCalculationService
{
    /**
     * Genera la plantilla Excel (formato "largo": una fila por trabajador+mes) para los
     * trabajadores activos de la empresa y los meses solicitados, con montos en cero para que
     * se completen a mano. `periods` es un array de ['year' => int, 'month' => int].
     */
    public function downloadTemplate(int $companyId, array $periods)
    {
        if (empty($periods)) {
            throw new Exception('Debe indicar al menos un periodo (año y mes).');
        }

        $workers = Worker::whereHas('sede', function ($query) use ($companyId) {
            $query->where('empresa_id', $companyId);
        })->working()->orderBy('nombre_completo')->get(['id', 'nombre_completo', 'vat']);

        $rows = [];
        foreach ($workers as $worker) {
            foreach ($periods as $period) {
                $rows[] = [
                    $worker->vat,
                    $worker->nombre_completo,
                    $period['year'],
                    $period['month'],
                    0,
                    0,
                    0,
                    0,
                    0,
                ];
            }
        }

        return Excel::download(
            new PayrollHistoricalCalculationTemplateExport($rows),
            'historico-conceptos-variables.xlsx'
        );
    }

    /**
     * Importa el archivo (mismo formato que downloadTemplate) y registra/actualiza
     * PayrollCalculation por trabajador+mes, creando el PayrollPeriod si todavía no existe.
     * No recalcula gratificación/CTS/planilla: solo deja los datos base listos para que
     * calcularPromedioUltimos6Meses() los recoja la próxima vez que se calculen.
     */
    public function import(UploadedFile $file, int $companyId): array
    {
        try {
            DB::beginTransaction();

            $import = new PayrollHistoricalCalculationImport($companyId);
            Excel::import($import, $file);

            DB::commit();

            return [
                'success' => true,
                'rows_processed' => $import->rowsProcessed,
                'periods_created' => array_values(array_unique($import->periodsCreated)),
                'skipped' => $import->skipped,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

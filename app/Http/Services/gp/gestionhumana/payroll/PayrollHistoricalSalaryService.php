<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollHistoricalSalaryTemplateExport;
use App\Imports\gp\gestionhumana\payroll\PayrollHistoricalSalaryImport;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

/**
 * Carga masiva del histórico de sueldos (rrhh_contrato) — ver PayrollHistoricalSalaryImport para
 * el detalle de por qué hace falta: en producción esa tabla casi nunca se actualiza cuando hay un
 * aumento, así que WorkerContract::salaryForWorkerAtDate() devuelve sueldos desactualizados y la
 * gratificación/CTS calculada sale por debajo de lo real.
 */
class PayrollHistoricalSalaryService
{
    /**
     * Genera la plantilla Excel con una fila por trabajador activo de la empresa y su sueldo
     * actual (rrhh_persona.sueldo) como referencia, lista para agregar filas adicionales con el
     * histórico de cambios de sueldo (una fila por fecha de vigencia).
     */
    public function downloadTemplate(int $companyId)
    {
        $workers = Worker::whereHas('sede', function ($query) use ($companyId) {
            $query->where('empresa_id', $companyId);
        })->working()->orderBy('nombre_completo')->get(['id', 'nombre_completo', 'vat', 'sueldo']);

        $rows = [];
        foreach ($workers as $worker) {
            $rows[] = [
                $worker->vat,
                $worker->nombre_completo,
                '',
                (float) ($worker->sueldo ?? 0),
            ];
        }

        return Excel::download(
            new PayrollHistoricalSalaryTemplateExport($rows),
            'historico-sueldos.xlsx'
        );
    }

    /**
     * Importa el archivo (mismo formato que downloadTemplate) y registra/actualiza el historial
     * de contratos (rrhh_contrato) por trabajador.
     */
    public function import(UploadedFile $file, int $companyId): array
    {
        try {
            DB::beginTransaction();

            $import = new PayrollHistoricalSalaryImport($companyId);
            Excel::import($import, $file);

            DB::commit();

            return [
                'success' => true,
                'rows_processed' => $import->rowsProcessed,
                'periods_created' => [],
                'skipped' => $import->skipped,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

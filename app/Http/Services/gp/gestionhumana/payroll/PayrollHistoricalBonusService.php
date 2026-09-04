<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollHistoricalBonusTemplateExport;
use App\Imports\gp\gestionhumana\payroll\PayrollHistoricalBonusImport;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carga masiva del histórico mensual de bono/comisión (hoy "BONO_CONDUCTOR") que
 * PayrollCalculation::calcularPromedioUltimos6Meses() necesita para promediar el bono real de
 * los últimos 6 meses en la base computable de gratificación/CTS. gh_payroll_bonuses solo tiene
 * datos desde que se empiece a cargar manualmente/por integración — mientras tanto, este import
 * completa el histórico desde el Excel de origen (por empresa, empezando por TP; las demás
 * empresas se cargarán cuando entreguen su propio archivo).
 */
class PayrollHistoricalBonusService
{
    /**
     * Genera la plantilla Excel (formato "largo") para los trabajadores activos de la empresa y
     * los meses solicitados, con el bono en cero para completar a mano.
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
                ];
            }
        }

        return Excel::download(
            new PayrollHistoricalBonusTemplateExport($rows),
            'historico-bono-conductores.xlsx'
        );
    }

    /**
     * Importa el archivo (mismo formato que downloadTemplate) y registra/actualiza
     * PayrollBonus por trabajador+mes, creando el PayrollPeriod si todavía no existe.
     */
    public function import(UploadedFile $file, int $companyId): array
    {
        try {
            DB::beginTransaction();

            $import = new PayrollHistoricalBonusImport($companyId);
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

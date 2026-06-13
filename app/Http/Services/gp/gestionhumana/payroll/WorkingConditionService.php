<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollWorkingConditionResource;
use App\Http\Services\BaseService;
use App\Imports\gp\gestionhumana\payroll\WorkingConditionImport;
use App\Models\gp\gestionhumana\payroll\PayrollWorkingCondition;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class WorkingConditionService extends BaseService
{
  public function list(Request $request)
  {
    $query = PayrollWorkingCondition::query()
      ->join('gh_payroll_periods', 'gh_working_conditions.period_id', '=', 'gh_payroll_periods.id')
      ->select('gh_working_conditions.*')
      ->orderBy('gh_payroll_periods.year', 'desc')
      ->orderBy('gh_payroll_periods.month', 'desc');

    return $this->getFilteredResults(
      $query,
      $request,
      PayrollWorkingCondition::filters,
      PayrollWorkingCondition::sorts,
      PayrollWorkingConditionResource::class,
    );
  }

  /**
   * Importa condiciones de trabajo desde un archivo Excel.
   *
   * Estructura del archivo:
   * - Fila 1: título general (ignorar)
   * - Fila 2: cabeceras → columna B = DNI, columna C = C.T
   * - Fila 3 en adelante: registros de datos
   *
   * Reglas de mapeo:
   * - DNI (columna B): tratado como string con relleno de ceros a 8 dígitos
   * - C.T (columna C): monto decimal
   * - Se detiene cuando DNI y C.T están ambos vacíos
   * - Se omiten filas donde DNI esté vacío
   *
   * @param UploadedFile $file
   * @param int $periodId
   * @return array
   */
  public function importFromExcel(UploadedFile $file, int $periodId): array
  {
    $import = new WorkingConditionImport($periodId);
    Excel::import($import, $file);
    $results = $import->getResults();

    return [
      'success' => empty($results['errors']),
      'message' => empty($results['errors'])
        ? "Importación completada: {$results['created']} creados, {$results['updated']} actualizados."
        : "Importación con errores: {$results['created']} creados, {$results['updated']} actualizados.",
      'created' => $results['created'],
      'updated' => $results['updated'],
      'rows_processed' => $results['rows_processed'],
      'skipped' => $results['skipped'],
      'errors' => $results['errors'],
    ];
  }
}

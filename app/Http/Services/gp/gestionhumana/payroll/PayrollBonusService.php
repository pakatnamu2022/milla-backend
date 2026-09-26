<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollBonusTemplateExport;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollBonusResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Imports\gp\gestionhumana\payroll\PayrollBonusImport;
use App\Models\gp\gestionhumana\payroll\PayrollBonus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollBonusService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PayrollBonus::class,
      $request,
      PayrollBonus::filters,
      PayrollBonus::sorts,
      PayrollBonusResource::class,
    );
  }

  public function find($id)
  {
    $record = PayrollBonus::find($id);
    if (!$record) {
      throw new Exception('Bono no encontrado');
    }
    return $record;
  }

  public function show($id)
  {
    return new PayrollBonusResource($this->find($id));
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();
      $record = PayrollBonus::create($data);
      DB::commit();
      return new PayrollBonusResource($record);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();
      $record = $this->find($data['id']);
      $record->update($data);
      DB::commit();
      return new PayrollBonusResource($record->fresh());
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    try {
      DB::beginTransaction();
      $record = $this->find($id);
      $record->delete();
      DB::commit();
      return response()->json(['message' => 'Bono eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Descarga la plantilla Excel (cabecera azul) para cargar bonificaciones. No viene
   * pre-llenada con trabajadores: RRHH solo completa el DNI (columna A) y el monto (columna B)
   * de quienes sí tienen el bono asignado ese mes. Mismo formato que espera importFromExcel().
   */
  public function downloadTemplate(int $companyId)
  {
    return Excel::download(
      new PayrollBonusTemplateExport(),
      'plantilla_bonificaciones.xlsx'
    );
  }

  /**
   * Importa bonificaciones desde un archivo Excel para un periodo y tipo de bono fijos.
   *
   * Estructura del archivo:
   * - Fila 1: título general (ignorar)
   * - Fila 2: cabeceras → columna A = DNI, columna B = MONTO
   * - Fila 3 en adelante: registros de datos
   */
  public function importFromExcel(UploadedFile $file, int $periodId, int $typeId): array
  {
    $import = new PayrollBonusImport($periodId, $typeId);
    Excel::import($import, $file);
    $results = $import->getResults();

    return [
      'success'        => empty($results['errors']),
      'message'        => empty($results['errors'])
        ? "Importación completada: {$results['created']} creados, {$results['updated']} actualizados."
        : "Importación con errores: {$results['created']} creados, {$results['updated']} actualizados.",
      'created'        => $results['created'],
      'updated'        => $results['updated'],
      'rows_processed' => $results['rows_processed'],
      'skipped'        => $results['skipped'],
      'errors'         => $results['errors'],
    ];
  }
}

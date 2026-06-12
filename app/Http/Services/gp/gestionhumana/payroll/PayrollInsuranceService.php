<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollInsuranceResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Imports\gp\gestionhumana\payroll\PayrollInsuranceOncoplusImport;
use App\Imports\gp\gestionhumana\payroll\PayrollInsuranceFesaludImport;
use App\Models\gp\gestionhumana\payroll\PayrollInsurance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollInsuranceService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PayrollInsurance::class,
      $request,
      PayrollInsurance::filters,
      PayrollInsurance::sorts,
      PayrollInsuranceResource::class,
    );
  }

  public function find($id)
  {
    $record = PayrollInsurance::find($id);
    if (!$record) {
      throw new Exception('Seguro no encontrado');
    }
    return $record;
  }

  public function show($id)
  {
    return new PayrollInsuranceResource($this->find($id));
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();
      $record = PayrollInsurance::create($data);
      DB::commit();
      return new PayrollInsuranceResource($record);
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
      return new PayrollInsuranceResource($record->fresh());
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
      return response()->json(['message' => 'Seguro eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Importa seguros de planilla desde un archivo Excel (formato Oncosalud).
   *
   * Columnas requeridas en el Excel:
   * - Núm Doc Afiliado
   * - Tarifa con IGV
   * - Contratante
   * - Núm Doc Contratante
   *
   * @param UploadedFile $file
   * @param int $periodId
   * @param int $businessPartnerId
   * @return array
   */
  public function importFromExcelOncoplus(UploadedFile $file, int $periodId, int $businessPartnerId): array
  {
    $import = new PayrollInsuranceOncoplusImport($periodId, $businessPartnerId);
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
      'errors' => $results['errors'],
    ];
  }

  /**
   * Importa seguros de planilla desde un archivo Excel (formato FESALUD).
   *
   * Columnas requeridas en el Excel:
   * - N° Doc.f
   * - Apellido Paterno
   * - Apellido Materno
   * - Nombres
   * - Aporte Mensual inc IGV (Tarifa)
   *
   * Los encabezados se buscan dinámicamente (no están siempre en la fila 1).
   *
   * @param UploadedFile $file
   * @param int $periodId
   * @param int $businessPartnerId
   * @return array
   */
  public function importFromExcelFesalud(UploadedFile $file, int $periodId, int $businessPartnerId): array
  {
    $import = new PayrollInsuranceFesaludImport($periodId, $businessPartnerId);
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
      'errors' => $results['errors'],
    ];
  }
}

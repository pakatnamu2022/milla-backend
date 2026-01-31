<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\PhoneLineResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Imports\gp\tics\PhoneLineImport;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\tics\PhoneLine;
use App\Models\gp\tics\TelephoneAccount;
use App\Models\gp\tics\TelephonePlan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PhoneLineService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PhoneLine::query(),
      $request,
      PhoneLine::filters,
      PhoneLine::sorts,
      PhoneLineResource::class,
    );
  }

  public function store($data)
  {
    $phoneLine = PhoneLine::create($data);
    return new PhoneLineResource(PhoneLine::find($phoneLine->id));
  }

  public function find($id)
  {
    $phoneLine = PhoneLine::where('id', $id)->first();
    if (!$phoneLine) {
      throw new Exception('Línea telefónica no encontrada');
    }
    return $phoneLine;
  }

  public function show($id)
  {
    return new PhoneLineResource($this->find($id));
  }

  public function update($data)
  {
    $phoneLine = $this->find($data['id']);
    $phoneLine->update($data);
    return new PhoneLineResource($phoneLine);
  }

  public function destroy($id)
  {
    $phoneLine = $this->find($id);
    $phoneLine->delete();
    return response()->json(['message' => 'Línea telefónica eliminada correctamente']);
  }

  /**
   * Importar líneas telefónicas desde un archivo Excel
   * Formato esperado: RUC | RAZON | CUENTA | LINEA | PLAN
   */
  public function importFromExcel(UploadedFile $file)
  {
    DB::beginTransaction();
    try {
      // Validar que el archivo no sea nulo
      if (!$file || !$file->isValid()) {
        return [
          'success' => false,
          'message' => 'Archivo no válido o no encontrado',
          'error' => 'El archivo enviado no es válido'
        ];
      }

      // Importar datos del Excel
      $import = new PhoneLineImport();
      Excel::import($import, $file);
      $rows = $import->getRows();

      if ($rows->isEmpty()) {
        DB::rollBack();
        return [
          'success' => false,
          'message' => 'No se encontraron datos válidos en el archivo',
          'error' => 'El archivo está vacío o no tiene el formato correcto'
        ];
      }

      $totalRows = $rows->count();
      $imported = 0;
      $updated = 0;
      $errors = 0;
      $errorDetails = [];

      // Procesar cada fila
      foreach ($rows as $index => $rowData) {
        try {
          $ruc = trim($rowData['ruc']);
          $accountNumber = trim($rowData['cuenta']);
          $lineNumber = trim($rowData['linea']);
          $planName = trim($rowData['plan']);

          // Buscar la empresa por RUC
          $company = Company::where('num_doc', $ruc)->first();
          if (!$company) {
            $errors++;
            $errorDetails[] = [
              'row' => $index + 2, // +2 porque Excel empieza en 1 y tiene header
              'error' => "Empresa con RUC {$ruc} no encontrada",
              'data' => $rowData
            ];
            continue;
          }

          // Buscar o crear la cuenta telefónica
          $telephoneAccount = TelephoneAccount::firstOrCreate(
            ['account_number' => $accountNumber],
            ['company_id' => $company->id]
          );

          // Buscar o crear el plan telefónico
          // Intentar extraer el precio del nombre del plan si está en el formato "Nombre + Precio"
          $price = 0;
          $planNameClean = $planName;

          // Intentar extraer precio si está en formato "Nombre + 29.90"
          if (preg_match('/(.+?)\s*\+\s*(\d+(?:\.\d+)?)/', $planName, $matches)) {
            $planNameClean = trim($matches[1]);
            $price = floatval($matches[2]);
          }

          $telephonePlan = TelephonePlan::firstOrCreate(
            ['name' => $planName],
            [
              'price' => $price,
              'description' => null
            ]
          );

          // Buscar o crear la línea telefónica
          $phoneLine = PhoneLine::withTrashed()->where('line_number', $lineNumber)->first();

          if ($phoneLine) {
            // Actualizar la línea existente
            $phoneLine->update([
              'telephone_account_id' => $telephoneAccount->id,
              'telephone_plan_id' => $telephonePlan->id,
              'status' => 'active',
              'is_active' => true,
              'deleted_at' => null, // Reactivar si estaba eliminada
            ]);
            $updated++;
          } else {
            // Crear nueva línea
            PhoneLine::create([
              'telephone_account_id' => $telephoneAccount->id,
              'telephone_plan_id' => $telephonePlan->id,
              'line_number' => $lineNumber,
              'status' => 'active',
              'is_active' => true,
            ]);
            $imported++;
          }

        } catch (Exception $e) {
          $errors++;
          $errorDetails[] = [
            'row' => $index + 2,
            'error' => $e->getMessage(),
            'data' => $rowData
          ];
        }
      }

      DB::commit();

      $message = "Importación completada: {$imported} líneas creadas, {$updated} líneas actualizadas";
      if ($errors > 0) {
        $message .= ", {$errors} con errores";
      }

      return [
        'success' => true,
        'message' => $message,
        'summary' => [
          'total_rows' => $totalRows,
          'imported' => $imported,
          'updated' => $updated,
          'errors' => $errors,
          'error_details' => $errorDetails
        ]
      ];

    } catch (Exception $e) {
      DB::rollBack();
      return [
        'success' => false,
        'message' => 'Error en la importación',
        'error' => $e->getMessage()
      ];
    }
  }
}

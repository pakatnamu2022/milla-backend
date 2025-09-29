<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PotentialBuyersResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ImportService;
use App\Imports\PotentialBuyersImport;
use App\Models\ap\comercial\PotentialBuyers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PotentialBuyersService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PotentialBuyers::class,
      $request,
      PotentialBuyers::filters,
      PotentialBuyers::sorts,
      PotentialBuyersResource::class,
    );
  }

  public function find($id)
  {
    $businessPartner = PotentialBuyers::where('id', $id)->first();
    if (!$businessPartner) {
      throw new Exception('Cliente potencial no encontrado');
    }
    return $businessPartner;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      if ($data['type'] === 'VISITA') {
        $data['registration_date'] = now();
      }

      $businessPartner = PotentialBuyers::create($data);
      DB::commit();
      return new PotentialBuyersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new PotentialBuyersResource($this->find($id));
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);
      $businessPartner->delete();
      DB::commit();
      return response()->json(['message' => 'Cliente potencial eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function importFromExcel(UploadedFile $file)
  {
    try {
      // Validar que el archivo no sea nulo
      if (!$file || !$file->isValid()) {
        return [
          'success' => false,
          'message' => 'Archivo no válido o no encontrado',
          'error' => 'El archivo enviado no es válido'
        ];
      }

      $importService = new ImportService();

      // Validar archivo
      $importService->validateFile($file);

      // Importar datos del Excel
      $importResult = $importService->importFromExcel($file, PotentialBuyersImport::class);

      if (!$importResult['success']) {
        return [
          'success' => false,
          'message' => $importResult['message'],
          'error' => $importResult['error']
        ];
      }

      // Procesar los datos importados
      $processResult = $importService->processImportData(
        $importResult['data'],
        PotentialBuyers::class
      );

      return [
        'success' => true,
        'message' => 'Importación completada',
        'data' => $importResult['data'],
        'summary' => [
          'total_rows' => $importResult['total_rows'],
          'processed' => $processResult['processed'],
          'errors' => $processResult['errors'],
          'error_details' => $processResult['error_details']
        ]
      ];

    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => 'Error en la importación',
        'error' => $e->getMessage()
      ];
    }
  }
}

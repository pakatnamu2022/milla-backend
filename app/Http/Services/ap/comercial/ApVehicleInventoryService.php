<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApVehicleInventoryResource;
use App\Http\Services\BaseService;
use App\Imports\ap\comercial\ApVehicleInventoryImport;
use App\Models\ap\comercial\ApVehicleInventory;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApVehicleInventoryService extends BaseService
{
  const array WITH_RELATIONS = [
    'vehicle.vehicleStatus',
    'vehicle.warehouse.sede',
    'inventoryWarehouse.sede',
    'color',
    'brand',
    'model.family',
    'fuelType',
    'evaluatedBy',
  ];

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleInventory::class,
      $request,
      ApVehicleInventory::filters,
      ApVehicleInventory::sorts,
      ApVehicleInventoryResource::class
    );
  }

  public function find(int $id): ApVehicleInventory
  {
    $record = ApVehicleInventory::where('id', $id)->first();
    if (!$record) {
      throw new Exception('Registro de inventario no encontrado');
    }
    return $record;
  }

  public function store(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      $record = ApVehicleInventory::create($data);
      $record->load(self::WITH_RELATIONS);
      DB::commit();
      return ApVehicleInventoryResource::make($record);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): JsonResource
  {
    $record = ApVehicleInventory::with(self::WITH_RELATIONS)->findOrFail($id);
    return ApVehicleInventoryResource::make($record);
  }

  public function update(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      $record = $this->find($data['id']);
      unset($data['id']);

      $record->update($data);

      // Si se marca como evaluado y la ubicación se confirma, cambiar estado del vehículo a INVENTARIO VN
      $record->refresh();
      $this->checkAndUpdateVehicleStatus($record);

      $record->load(self::WITH_RELATIONS);
      DB::commit();
      return ApVehicleInventoryResource::make($record);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $record = $this->find($id);
      $record->delete();
      DB::commit();
      return ['message' => 'Registro de inventario eliminado correctamente'];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Marca un registro como evaluado y opcionalmente confirma la ubicación.
   * Si ambos flags quedan en true, actualiza el estado del vehículo a INVENTARIO VN.
   */
  public function evaluate(int $id, bool $isLocationConfirmed): JsonResource
  {
    DB::beginTransaction();
    try {
      $record = $this->find($id);

      $record->update([
        'is_evaluated' => true,
        'is_location_confirmed' => $isLocationConfirmed,
        'evaluated_at' => now(),
        'evaluated_by' => auth()->id(),
      ]);

      $record->refresh();
      $this->checkAndUpdateVehicleStatus($record);

      $record->load(self::WITH_RELATIONS);
      DB::commit();
      return ApVehicleInventoryResource::make($record);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Si el registro está evaluado y la ubicación confirmada,
   * actualiza el estado del vehículo asociado a INVENTARIO VN.
   */
  private function checkAndUpdateVehicleStatus(ApVehicleInventory $record): void
  {
    if ($record->is_evaluated && $record->is_location_confirmed && $record->ap_vehicle_id) {
      $record->vehicle?->update([
        'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
      ]);
    }
  }

  /**
   * Importa vehículos al inventario desde un archivo Excel.
   * Columnas requeridas: vin, color, marca, modelo, ano, gasolina,
   *   fecha_adjudicacion, dias, fecha_limite, fecha_recepcion, sede (warehouse)
   */
  public function importFromExcel(UploadedFile $file): array
  {
    $import = new ApVehicleInventoryImport();
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
   * Genera y devuelve un archivo Excel de plantilla para la importación de inventario.
   */
  public function downloadTemplate(): StreamedResponse
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Inventario Vehículos');

    // Cabeceras
    $headers = [
      'A1' => 'vin',
      'B1' => 'color',
      'C1' => 'marca',
      'D1' => 'modelo',
      'E1' => 'ano',
      'F1' => 'gasolina',
      'G1' => 'fecha_adjudicacion',
      'H1' => 'dias',
      'I1' => 'fecha_limite',
      'J1' => 'fecha_recepcion',
      'K1' => 'sede',
    ];

    foreach ($headers as $cell => $value) {
      $sheet->setCellValue($cell, $value);
    }

    // Fila de ejemplo
    $example = [
      'A2' => 'XXXXXXXXXXXXXXXXX',
      'B2' => 'BLANCO',
      'C2' => 'TOYOTA',
      'D2' => 'HILUX 4X4',
      'E2' => '2024',
      'F2' => 'GASOLINA',
      'G2' => '2024-01-15',
      'H2' => '30',
      'I2' => '2024-02-14',
      'J2' => '2024-01-20',
      'K2' => 'CHICLAYO',
    ];

    foreach ($example as $cell => $value) {
      $sheet->setCellValue($cell, $value);
    }

    // Estilo de cabecera
    $headerStyle = [
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center'],
    ];
    $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

    // Ancho de columnas
    $columnWidths = ['A' => 20, 'B' => 15, 'C' => 15, 'D' => 20, 'E' => 8,
      'F' => 15, 'G' => 22, 'H' => 8, 'I' => 22, 'J' => 22, 'K' => 20];
    foreach ($columnWidths as $col => $width) {
      $sheet->getColumnDimension($col)->setWidth($width);
    }

    // Instrucciones en una segunda hoja
    $instructions = $spreadsheet->createSheet();
    $instructions->setTitle('Instrucciones');
    $instructions->setCellValue('A1', 'INSTRUCCIONES DE LLENADO');
    $instructions->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);

    $instructionRows = [
      ['Columna', 'Descripción', 'Ejemplo', 'Requerido'],
      ['vin', 'Número de identificación del vehículo (hasta 17 caracteres)', 'XXXXXXXXXXXXXXXXX', 'SÍ'],
      ['color', 'Color del vehículo (se crea automáticamente si no existe)', 'BLANCO', 'NO'],
      ['marca', 'Marca exacta como está en el sistema', 'TOYOTA', 'NO'],
      ['modelo', 'Versión/Modelo del vehículo', 'HILUX 4X4', 'NO'],
      ['ano', 'Año del vehículo (4 dígitos)', '2024', 'NO'],
      ['gasolina', 'Tipo de combustible (se crea automáticamente si no existe)', 'GASOLINA', 'NO'],
      ['fecha_adjudicacion', 'Fecha de adjudicación (YYYY-MM-DD)', '2024-01-15', 'NO'],
      ['dias', 'Número de días', '30', 'NO'],
      ['fecha_limite', 'Fecha límite (YYYY-MM-DD)', '2024-02-14', 'NO'],
      ['fecha_recepcion', 'Fecha de recepción (YYYY-MM-DD)', '2024-01-20', 'NO'],
      ['sede', 'Nombre o código del almacén/sede donde está el vehículo', 'CHICLAYO', 'NO'],
    ];

    foreach ($instructionRows as $rowIndex => $rowData) {
      foreach ($rowData as $colIndex => $cellValue) {
        $instructions->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 3, $cellValue);
      }
    }

    // Estilo de la tabla de instrucciones
    $tableStyle = [
      'font' => ['bold' => true],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F75B6']],
      'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
    ];
    $instructions->getStyle('A3:D3')->applyFromArray($tableStyle);
    $instructions->getColumnDimension('A')->setWidth(25);
    $instructions->getColumnDimension('B')->setWidth(55);
    $instructions->getColumnDimension('C')->setWidth(25);
    $instructions->getColumnDimension('D')->setWidth(12);

    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'plantilla_inventario_vehiculos.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }
}

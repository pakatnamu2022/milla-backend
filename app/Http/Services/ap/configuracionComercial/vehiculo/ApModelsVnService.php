<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnDynamicsResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnSyncLogResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Jobs\SyncModelVnJob;
use App\Models\ap\ApMasters;
use App\Http\Utils\Constants;
use App\Models\ap\comercial\ApReceivingChecklist;
use App\Models\ap\comercial\ApReceivingInspection;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklist;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVnSyncLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Imports\ap\vehiculo\ApModelsVnImport;
use App\Imports\ap\vehiculo\ApModelsVnVerifyImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApModelsVnService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApModelsVn::class,
      $request,
      ApModelsVn::filters,
      ApModelsVn::sorts,
      ApModelsVnResource::class,
    );
  }

  public function export(Request $request)
  {
    return (new ExportService())->exportFromRequest($request, ApModelsVn::class);
  }

  public function find($id)
  {
    $engineType = ApModelsVn::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Modelo VN no encontrado');
    }
    return $engineType;
  }

  public function store(mixed $data)
  {
    $existe = ApModelsVn::where('family_id', $data['family_id'])
      ->where('type_operation_id', $data['type_operation_id'])
      ->where('version', $data['version'])
      ->where('model_year', $data['model_year'] ?? null)
      ->where('fuel_id', $data['fuel_id'] ?? null)
      ->where('vehicle_type_id', $data['vehicle_type_id'] ?? null)
      ->where('body_type_id', $data['body_type_id'] ?? null)
      ->where('traction_type_id', $data['traction_type_id'] ?? null)
      ->where('transmission_id', $data['transmission_id'] ?? null)
      ->whereNull('deleted_at')
      ->exists();

    if ($existe) {
      throw new Exception('Ya existe un modelo con la misma versión, familia, año, combustible, tipo de vehículo, carrocería, tracción y transmisión.');
    }

    if ((int)$data['type_operation_id'] === ApMasters::TIPO_OPERACION_COMERCIAL) {
      // Generate code using model method (separates correlatives by operation type)
      $data['code'] = ApModelsVn::generateNextCode(
        $data['family_id'],
        $data['model_year'],
        $data['type_operation_id']
      );
    } else {
      $data['code'] = ApModelsVn::generateNextCode(
        $data['family_id'],
        date('Y'),
        $data['type_operation_id']
      );
    }

    $engineType = ApModelsVn::create($data);

    // Invalidar caché
    Cache::forget('models.all');

    return new ApModelsVnResource($engineType);
  }

  public function show($id)
  {
    return new ApModelsVnResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $modelVn = $this->find($data['id']);

    // Los campos inmutables (code, family_id, model_year, type_operation_id)
    // ya están bloqueados en el Request y no llegarán aquí
    $modelVn->update($data);

    // Invalidar caché
    Cache::forget('models.all');

    return new ApModelsVnResource($modelVn);
  }


  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });

    // Invalidar caché
    Cache::forget('models.all');

    return response()->json(['message' => 'Modelo VN eliminado correctamente']);
  }

  public function downloadTemplate(): StreamedResponse
  {
    // ── Cargar opciones desde base de datos ──────────────────────────────────
    $familias = ApFamilies::where('status', true)->pluck('description')->toArray();
    $clases = ApClassArticle::where('status', true)->pluck('description')->toArray();
    $combustibles = ApFuelType::where('status', true)->pluck('description')->toArray();
    $tiposVehiculo = ApMasters::where('type', 'TIPO_VEHICULO')->where('status', true)->pluck('description')->toArray();
    $carrocerias = ApMasters::where('type', 'TIPO_CARROCERIA')->where('status', true)->pluck('description')->toArray();
    $tracciones = ApMasters::where('type', 'TIPO_TRACCION')->where('status', true)->pluck('description')->toArray();
    $transmisiones = ApMasters::where('type', 'TRANSMISION_VEHICULO')->where('status', true)->pluck('description')->toArray();
    $monedas = TypeCurrency::where('status', true)->pluck('code')->toArray();
    $tiposOp = ApMasters::where('type', 'TIPO_OPERACION')->where('status', true)->pluck('description')->toArray();

    $spreadsheet = new Spreadsheet();

    // ── Hoja de listas (oculta) ──────────────────────────────────────────────
    $listsSheet = $spreadsheet->createSheet(1);
    $listsSheet->setTitle('_Listas');
    $listsSheet->getTabColor()->setRGB('CCCCCC');
    $listsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    $listColumns = [
      'A' => ['titulo' => 'Familia', 'valores' => $familias],
      'B' => ['titulo' => 'Clase', 'valores' => $clases],
      'C' => ['titulo' => 'Combustible', 'valores' => $combustibles],
      'D' => ['titulo' => 'Tipo Vehículo', 'valores' => $tiposVehiculo],
      'E' => ['titulo' => 'Tipo Carrocería', 'valores' => $carrocerias],
      'F' => ['titulo' => 'Tipo Tracción', 'valores' => $tracciones],
      'G' => ['titulo' => 'Transmisión', 'valores' => $transmisiones],
      'H' => ['titulo' => 'Moneda', 'valores' => $monedas],
      'I' => ['titulo' => 'Tipo Operación', 'valores' => $tiposOp],
    ];

    $listRanges = [];
    foreach ($listColumns as $col => $info) {
      $listsSheet->setCellValue("{$col}1", $info['titulo']);
      foreach ($info['valores'] as $i => $val) {
        $listsSheet->setCellValue("{$col}" . ($i + 2), $val);
      }
      $count = count($info['valores']);
      $listRanges[$col] = $count > 0 ? "_Listas!\${$col}\$2:\${$col}\$" . ($count + 1) : null;
    }

    // ── Hoja principal ───────────────────────────────────────────────────────
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Modelos VN');

    $headers = [
      'A'  => 'N°',
      'B'  => 'Tipo de Operación *',
      'C'  => 'Familia *',
      'D'  => 'Clase *',
      'E'  => 'Versión / Descripción del Modelo *',
      'F'  => 'Año del Modelo',
      'G'  => 'Combustible *',
      'H'  => 'Tipo de Vehículo *',
      'I'  => 'Tipo de Carrocería *',
      'J'  => 'Tipo de Tracción *',
      'K'  => 'Transmisión *',
      'L'  => 'Moneda *',
      'M'  => 'Potencia (HP)',
      'N'  => 'Distancia entre Ejes (mm)',
      'O'  => 'N° de Ejes',
      'P'  => 'Ancho (mm)',
      'Q'  => 'Largo (mm)',
      'R'  => 'Alto (mm)',
      'S'  => 'N° de Asientos',
      'T'  => 'N° de Puertas',
      'U'  => 'Peso Neto (kg)',
      'V'  => 'Peso Bruto (kg)',
      'W'  => 'Carga Útil (kg)',
      'X'  => 'Cilindrada (cc)',
      'Y'  => 'N° de Cilindros',
      'Z'  => 'N° de Pasajeros',
      'AA' => 'N° de Ruedas',
      'AB' => 'Precio Distribuidor',
      'AC' => 'Costo de Transporte',
      'AD' => 'Otros Montos',
      'AE' => 'Descuento de Compra',
    ];

    foreach ($headers as $cell => $label) {
      $sheet->setCellValue("{$cell}1", $label);
    }

    // ── Fila de ejemplo ──────────────────────────────────────────────────────
    $example = [
      'A2'  => 0,  // N°=0 → se ignora al importar (fila de ejemplo)
      'B2'  => !empty($tiposOp) ? $tiposOp[0] : 'COMERCIAL',
      'C2'  => !empty($familias) ? $familias[0] : 'HILUX',
      'D2'  => !empty($clases) ? $clases[0] : 'VEHICULOS COMERCIALES',
      'E2'  => 'HILUX 4X4 SRV AT',
      'F2'  => 2025,
      'G2'  => !empty($combustibles) ? $combustibles[0] : 'DIESEL',
      'H2'  => !empty($tiposVehiculo) ? $tiposVehiculo[0] : 'PICK UP',
      'I2'  => !empty($carrocerias) ? $carrocerias[0] : 'CABINA DOBLE',
      'J2'  => !empty($tracciones) ? $tracciones[0] : '4X4',
      'K2'  => !empty($transmisiones) ? $transmisiones[0] : 'AUTOMATICA',
      'L2'  => !empty($monedas) ? $monedas[0] : 'USD',
      'M2'  => '204 HP',
      'N2'  => '3085',
      'O2'  => '2',
      'P2'  => '1855',
      'Q2'  => '5335',
      'R2'  => '1815',
      'S2'  => '5',
      'T2'  => '4',
      'U2'  => '2080',
      'V2'  => '3010',
      'W2'  => '930',
      'X2'  => '2755',
      'Y2'  => '4',
      'Z2'  => '5',
      'AA2' => '4',
      'AB2' => 44800.00,
      'AC2' => 850.00,
      'AD2' => 0.00,
      'AE2' => 0.00,
    ];

    foreach ($example as $cell => $value) {
      $sheet->setCellValue($cell, $value);
    }

    // ── Estilo de cabecera ───────────────────────────────────────────────────
    $headerStyle = [
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
      'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
    ];
    $sheet->getStyle('A1:AE1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(40);

    // ── Estilo de fila de ejemplo ────────────────────────────────────────────
    $exampleStyle = [
      'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EBF3FB']],
      'font'    => ['italic' => true, 'color' => ['rgb' => '555555']],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
    ];
    $sheet->getStyle('A2:AE2')->applyFromArray($exampleStyle);

    // ── Anchos de columna ────────────────────────────────────────────────────
    $widths = [
      'A' => 6, 'B' => 18, 'C' => 20, 'D' => 22, 'E' => 30,
      'F' => 10, 'G' => 18, 'H' => 18, 'I' => 20, 'J' => 18,
      'K' => 16, 'L' => 10, 'M' => 14, 'N' => 20, 'O' => 10,
      'P' => 12, 'Q' => 12, 'R' => 12, 'S' => 14, 'T' => 14,
      'U' => 14, 'V' => 14, 'W' => 14, 'X' => 14, 'Y' => 14,
      'Z' => 16, 'AA' => 14, 'AB' => 20, 'AC' => 20, 'AD' => 16, 'AE' => 20,
    ];
    foreach ($widths as $col => $w) {
      $sheet->getColumnDimension($col)->setWidth($w);
    }

    // ── Validación con dropdowns (filas 2-1001) ──────────────────────────────
    $dropdownMap = [
      'B' => $listRanges['I'], // Tipo Operación
      'C' => $listRanges['A'], // Familia
      'D' => $listRanges['B'], // Clase
      'G' => $listRanges['C'], // Combustible
      'H' => $listRanges['D'], // Tipo Vehículo
      'I' => $listRanges['E'], // Carrocería
      'J' => $listRanges['F'], // Tracción
      'K' => $listRanges['G'], // Transmisión
      'L' => $listRanges['H'], // Moneda
    ];

    foreach ($dropdownMap as $col => $range) {
      if (!$range) continue;
      for ($row = 2; $row <= 1001; $row++) {
        $validation = $sheet->getCell("{$col}{$row}")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($range);
      }
    }

    // ── Freeze de la primera fila ────────────────────────────────────────────
    $sheet->freezePane('A2');

    // ── Hoja de instrucciones ────────────────────────────────────────────────
    $info = $spreadsheet->createSheet(2);
    $info->setTitle('Instrucciones');

    $info->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA — MODELOS VN');
    $info->getStyle('A1')->applyFromArray([
      'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F4E79']],
    ]);
    $info->getRowDimension(1)->setRowHeight(24);

    $info->setCellValue('A2', '⚠ IMPORTANTE: Elimina la fila de ejemplo (fila 2 de la hoja "Modelos VN") antes de subir el archivo al sistema.');
    $info->getStyle('A2')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'C00000']],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFE7E7']],
    ]);

    $info->setCellValue('A3', '(*) Campo obligatorio. Las columnas con dropdown solo aceptan los valores listados en la hoja oculta _Listas.');
    $info->getStyle('A3')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF666666'));

    $instrucciones = [
      ['Campo', 'Descripción', 'Ejemplo', 'Requerido'],
      ['Tipo de Operación', 'COMERCIAL (venta de vehículos nuevos) o POSTVENTA (taller/repuestos)', 'COMERCIAL', 'SÍ'],
      ['Familia', 'Nombre de la familia del vehículo (debe existir en el sistema)', 'HILUX', 'SÍ'],
      ['Clase', 'Clasificación del artículo (debe existir en el sistema)', 'VEHICULOS COMERCIALES', 'SÍ'],
      ['Versión / Descripción del Modelo', 'Nombre completo del modelo/versión', 'HILUX 4X4 SRV AT', 'SÍ'],
      ['Año del Modelo', 'Año en 4 dígitos (requerido para COMERCIAL)', '2025', 'COMERCIAL'],
      ['Combustible', 'Tipo de combustible (debe existir en el sistema)', 'DIESEL', 'SÍ'],
      ['Tipo de Vehículo', 'Categoría del vehículo (PICK UP, SUV, SEDAN, etc.)', 'PICK UP', 'SÍ'],
      ['Tipo de Carrocería', 'Tipo de carrocería (CABINA DOBLE, STATION WAGON, etc.)', 'CABINA DOBLE', 'SÍ'],
      ['Tipo de Tracción', 'Sistema de tracción (4X4, 4X2, etc.)', '4X4', 'SÍ'],
      ['Transmisión', 'Tipo de transmisión (AUTOMATICA, MECANICA)', 'AUTOMATICA', 'SÍ'],
      ['Moneda', 'Código ISO de moneda (USD, PEN, etc.)', 'USD', 'SÍ'],
      ['Potencia (HP)', 'Potencia del motor en HP (requerido para COMERCIAL)', '204 HP', 'COMERCIAL'],
      ['Distancia entre Ejes (mm)', 'Distancia en milímetros', '3085', 'NO'],
      ['N° de Ejes', 'Cantidad de ejes', '2', 'NO'],
      ['Ancho (mm)', 'Ancho del vehículo en mm', '1855', 'NO'],
      ['Largo (mm)', 'Largo del vehículo en mm', '5335', 'NO'],
      ['Alto (mm)', 'Alto del vehículo en mm', '1815', 'NO'],
      ['N° de Asientos', 'Total de asientos incluyendo conductor', '5', 'NO'],
      ['N° de Puertas', 'Cantidad de puertas', '4', 'NO'],
      ['Peso Neto (kg)', 'Peso en vacío en kg', '2080', 'NO'],
      ['Peso Bruto (kg)', 'Peso máximo autorizado en kg', '3010', 'NO'],
      ['Carga Útil (kg)', 'Capacidad de carga en kg', '930', 'NO'],
      ['Cilindrada (cc)', 'Desplazamiento del motor en cc', '2755', 'NO'],
      ['N° de Cilindros', 'Cantidad de cilindros del motor', '4', 'NO'],
      ['N° de Pasajeros', 'Capacidad total de pasajeros', '5', 'NO'],
      ['N° de Ruedas', 'Cantidad total de ruedas', '4', 'NO'],
      ['Precio Distribuidor', 'Precio del distribuidor en la moneda indicada', '44800.00', 'NO'],
      ['Costo de Transporte', 'Costo de flete/transporte', '850.00', 'NO'],
      ['Otros Montos', 'Otros costos adicionales', '0.00', 'NO'],
      ['Descuento de Compra', 'Descuento obtenido en la compra', '0.00', 'NO'],
    ];

    foreach ($instrucciones as $ri => $row) {
      foreach ($row as $ci => $val) {
        $info->setCellValueByColumnAndRow($ci + 1, $ri + 5, $val);
      }
    }

    $info->getStyle('A5:D5')->applyFromArray([
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F75B6']],
      'alignment' => ['horizontal' => 'center'],
    ]);
    foreach (['A' => 35, 'B' => 65, 'C' => 25, 'D' => 14] as $c => $w) {
      $info->getColumnDimension($c)->setWidth($w);
    }

    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'plantilla_modelos_vn.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }

  public function downloadVerifyTemplate(): StreamedResponse
  {
    $combustibles = ApFuelType::where('status', true)->pluck('description')->toArray();

    $spreadsheet = new Spreadsheet();

    $listsSheet = $spreadsheet->createSheet(1);
    $listsSheet->setTitle('_Listas');
    $listsSheet->getTabColor()->setRGB('CCCCCC');
    $listsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    foreach ($combustibles as $i => $val) {
      $listsSheet->setCellValue('A' . ($i + 2), $val);
    }
    $listsSheet->setCellValue('A1', 'Combustible');
    $combRange = count($combustibles) > 0
      ? '_Listas!$A$2:$A$' . (count($combustibles) + 1)
      : null;

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Verificación Modelos VN');

    $headers = [
      'A' => 'N°',
      'B' => 'Versión / Descripción del Modelo *',
      'C' => 'Año del Modelo *',
      'D' => 'Combustible *',
    ];
    foreach ($headers as $col => $label) {
      $sheet->setCellValue("{$col}1", $label);
    }

    $sheet->setCellValue('A2', 0);
    $sheet->setCellValue('B2', 'HILUX 4X4 SRV AT');
    $sheet->setCellValue('C2', 2025);
    $sheet->setCellValue('D2', !empty($combustibles) ? $combustibles[0] : 'DIESEL');

    $headerStyle = [
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
      'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
    ];
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(40);

    $exampleStyle = [
      'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EBF3FB']],
      'font'    => ['italic' => true, 'color' => ['rgb' => '555555']],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
    ];
    $sheet->getStyle('A2:D2')->applyFromArray($exampleStyle);

    foreach (['A' => 6, 'B' => 35, 'C' => 14, 'D' => 20] as $col => $w) {
      $sheet->getColumnDimension($col)->setWidth($w);
    }

    if ($combRange) {
      for ($row = 2; $row <= 1001; $row++) {
        $validation = $sheet->getCell("D{$row}")->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($combRange);
      }
    }

    $sheet->freezePane('A2');
    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'plantilla_verificacion_modelos_vn.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }

  public function dynamicsPreview(int $id): array
  {
    $model = $this->find($id);
    $model->load(['classArticle', 'family.brand']);
    $resource = new ApModelsVnDynamicsResource($model);
    return $resource->toArray(request());
  }

  public function verifyFromExcel(UploadedFile $file): array
  {
    $import = new ApModelsVnVerifyImport();
    Excel::import($import, $file);
    $results = $import->getResults();

    $existing = array_map(function (array $row) {
      $model = ApModelsVn::with(['classArticle', 'family.brand'])->find($row['id']);
      $dynamicsPayload = null;
      $dynamicsError = null;
      if ($model) {
        try {
          $dynamicsPayload = (new ApModelsVnDynamicsResource($model))->toArray(request());
        } catch (\Throwable $th) {
          $dynamicsError = $th->getMessage();
        }
      }
      return array_merge($row, [
        'dynamics_payload' => $dynamicsPayload,
        'dynamics_error'   => $dynamicsError,
      ]);
    }, $results['existing']);

    return [
      'rows_processed'  => $results['rows_processed'],
      'existing_count'  => count($existing),
      'not_found_count' => count($results['not_found']),
      'existing'        => $existing,
      'not_found'       => $results['not_found'],
    ];
  }

  public function syncModel(int $id): array
  {
    $model = $this->find($id);

    if (!$model->code) {
      throw new Exception("El modelo ID {$id} no tiene código asignado y no puede sincronizarse con Dynamics.");
    }

    if ($model->type_operation_id !== ApMasters::TIPO_OPERACION_COMERCIAL) {
      throw new Exception("Solo los modelos de tipo COMERCIAL pueden sincronizarse con Dynamics.");
    }

    $existing = ApModelsVnSyncLog::where('model_vn_id', $model->id)
      ->whereIn('status', [
        ApModelsVnSyncLog::STATUS_COMPLETED,
        ApModelsVnSyncLog::STATUS_IN_PROGRESS,
        ApModelsVnSyncLog::STATUS_PENDING,
      ])
      ->latest()
      ->first();

    if ($existing) {
      throw new Exception("El modelo ya tiene una sincronización con estado '{$existing->status}' (Log #{$existing->id}).");
    }

    $log = ApModelsVnSyncLog::create([
      'model_vn_id' => $model->id,
      'code'        => $model->code,
      'status'      => ApModelsVnSyncLog::STATUS_PENDING,
      'attempts'    => 0,
    ]);

    SyncModelVnJob::dispatch($model->id, $log->id);

    return [
      'log_id'   => $log->id,
      'model_id' => $model->id,
      'code'     => $model->code,
      'status'   => $log->status,
      'message'  => 'Job de sincronización despachado correctamente.',
    ];
  }

  public function syncAll(): array
  {
    $syncedIds = ApModelsVnSyncLog::whereIn('status', [
      ApModelsVnSyncLog::STATUS_COMPLETED,
      ApModelsVnSyncLog::STATUS_IN_PROGRESS,
      ApModelsVnSyncLog::STATUS_PENDING,
    ])->pluck('model_vn_id');

    $models = ApModelsVn::whereNotNull('code')
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->whereNotIn('id', $syncedIds)
      ->get();

    if ($models->isEmpty()) {
      return ['dispatched' => 0, 'message' => 'No hay modelos VN pendientes de sincronizar.'];
    }

    $dispatched = 0;
    foreach ($models as $model) {
      $log = ApModelsVnSyncLog::create([
        'model_vn_id' => $model->id,
        'code'        => $model->code,
        'status'      => ApModelsVnSyncLog::STATUS_PENDING,
        'attempts'    => 0,
      ]);
      SyncModelVnJob::dispatch($model->id, $log->id);
      $dispatched++;
    }

    return ['dispatched' => $dispatched, 'message' => "Se despacharon {$dispatched} jobs de sincronización."];
  }

  public function syncLogs(Request $request)
  {
    $query = ApModelsVnSyncLog::with(['model:id,version,model_year,fuel_id']);

    return $this->getFilteredResults(
      $query,
      $request,
      ApModelsVnSyncLog::filters,
      ApModelsVnSyncLog::sorts,
      ApModelsVnSyncLogResource::class,
    );
  }

  public function fixWrongCodes(): array
  {
    $comercialId = ApMasters::TIPO_OPERACION_COMERCIAL;

    $models = ApModelsVn::with('family')
      ->where('type_operation_id', $comercialId)
      ->whereNull('deleted_at')
      ->get();

    $fixed = [];
    $skipped = [];

    foreach ($models as $model) {
      // Modelo bloqueado: su código es intocable, se omite siempre
      if ($model->locked) {
        $skipped[] = ['id' => $model->id, 'code' => $model->code, 'reason' => 'bloqueado (locked)'];
        continue;
      }

      $familia = $model->family;
      if (!$familia) {
        $skipped[] = ['id' => $model->id, 'code' => $model->code, 'reason' => 'Familia no encontrada'];
        continue;
      }

      $familyCode = $familia->code;
      $expectedPrefix = $familyCode . substr($model->model_year, -2);
      $currentCode = $model->code;

      // Año correcto en el código
      $yearOk = str_starts_with($currentCode, $expectedPrefix);

      // Longitud exacta: len(familyCode) + 2(año) + 3(correlativo)
      $expectedLength = strlen($expectedPrefix) + 3;
      $correlativeOk = strlen($currentCode) === $expectedLength
        && ctype_digit(substr($currentCode, -3));

      // Duplicado dentro del mismo espacio de códigos (mismo tipo operación)
      // Si el duplicado está locked, este modelo DEBE moverse aunque su código sea válido
      $duplicateInScope = ApModelsVn::where('code', $currentCode)
        ->where('id', '!=', $model->id)
        ->where('type_operation_id', $model->type_operation_id)
        ->whereNull('deleted_at')
        ->first(['id', 'locked']);

      $isDuplicated = $duplicateInScope !== null;
      $duplicateIsLocked = $isDuplicated && $duplicateInScope->locked;

      if ($yearOk && $correlativeOk && !$isDuplicated) {
        continue;
      }

      $reason = $duplicateIsLocked
        ? 'duplicado con modelo bloqueado (cede el código)'
        : (!$yearOk
          ? 'año incorrecto en código'
          : (!$correlativeOk ? 'correlativo inválido (' . substr($currentCode, -3) . ')' : 'código duplicado'));

      // Sacar temporalmente del pool para que generateNextCode no lo cuente
      DB::table('ap_models_vn')->where('id', $model->id)->update(['code' => '__FIXING__' . $model->id]);

      $newCode = ApModelsVn::generateNextCode($model->family_id, $model->model_year, $model->type_operation_id);

      // Garantizar unicidad global: si el código generado ya existe, incrementar correlativo
      $prefix = substr($newCode, 0, -3);
      $correlative = (int)substr($newCode, -3);
      while (
      ApModelsVn::where('code', $newCode)
        ->whereNull('deleted_at')
        ->exists()
      ) {
        $correlative++;
        $newCode = $prefix . str_pad($correlative, 3, '0', STR_PAD_LEFT);
      }

      DB::table('ap_models_vn')->where('id', $model->id)->update(['code' => $newCode]);

      $deletedLogs = ApModelsVnSyncLog::where('model_vn_id', $model->id)->count();
      ApModelsVnSyncLog::where('model_vn_id', $model->id)->delete();

      $fixed[] = [
        'id'           => $model->id,
        'version'      => $model->version,
        'reason'       => $reason,
        'old_code'     => $currentCode,
        'new_code'     => $newCode,
        'logs_deleted' => $deletedLogs,
      ];
    }

    Cache::forget('models.all');

    return [
      'fixed'   => count($fixed),
      'skipped' => count($skipped),
      'details' => $fixed,
      'errors'  => $skipped,
    ];
  }

  public function importFromExcel(UploadedFile $file): array
  {
    $import = new ApModelsVnImport();
    Excel::import($import, $file);
    $results = $import->getResults();

    $hayErrores = !empty($results['errors']);
    $totalErrors = count($results['errors']);
    $msg = "Importación completada: {$results['created']} creado(s), {$results['skipped']} omitido(s) por duplicado.";
    if ($hayErrores) {
      $msg .= " {$totalErrors} fila(s) con error.";
    }

    return [
      'success'        => !$hayErrores,
      'message'        => $msg,
      'rows_processed' => $results['rows_processed'],
      'errors_count'   => $totalErrors,
      'errors'         => $results['errors'],
      'skipped'        => $results['skipped'],
      'skipped_rows'   => $results['skipped_rows'],
      'created'        => $results['created'],
      'created_rows'   => $results['created_rows'],
    ];
  }

  public function downloadInitialStockTemplate(): StreamedResponse
  {
    $almacenes = Warehouse::whereNull('deleted_at')->where('status', true)
      ->whereNotNull('dyn_code')->pluck('dyn_code')->toArray();

    $spreadsheet = new Spreadsheet();

    $listsSheet = $spreadsheet->createSheet(1);
    $listsSheet->setTitle('_Listas');
    $listsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
    $listsSheet->setCellValue('A1', 'SitioId');
    foreach ($almacenes as $i => $val) {
      $listsSheet->setCellValue('A' . ($i + 2), $val);
    }
    $sitioRange = count($almacenes) > 0
      ? '_Listas!$A$2:$A$' . (count($almacenes) + 1)
      : null;

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Stock Inicial VN');

    $headers = [
      'A' => 'N°',
      'B' => 'CODIGO *',
      'C' => 'CANTIDAD *',
      'D' => 'COSTO UNITARIO *',
      'E' => 'COSTO TOTAL *',
      'F' => 'SITIOID *',
      'G' => 'SERIE *',
    ];
    foreach ($headers as $col => $label) {
      $sheet->setCellValue("{$col}1", $label);
    }

    $sheet->getStyle('A1:G1')->applyFromArray([
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
      'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(36);

    $example = [
      'A2' => 0,
      'B2' => 'HLX25001',
      'C2' => 1,
      'D2' => 66030.23,
      'E2' => 66030.23,
      'F2' => !empty($almacenes) ? $almacenes[0] : 'EXR-CM-CIX',
      'G2' => 'LGWDCF19XVJ609324',
    ];
    foreach ($example as $cell => $value) {
      $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A2:G2')->applyFromArray([
      'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF3FB']],
      'font'    => ['italic' => true, 'color' => ['rgb' => '555555']],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
    ]);

    foreach (['A' => 6, 'B' => 16, 'C' => 12, 'D' => 20, 'E' => 20, 'F' => 20, 'G' => 24] as $col => $w) {
      $sheet->getColumnDimension($col)->setWidth($w);
    }

    if ($sitioRange) {
      for ($row = 2; $row <= 1001; $row++) {
        $v = $sheet->getCell("F{$row}")->getDataValidation();
        $v->setType(DataValidation::TYPE_LIST);
        $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $v->setAllowBlank(true);
        $v->setShowDropDown(true);
        $v->setFormula1($sitioRange);
      }
    }

    $sheet->freezePane('A2');
    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'plantilla_stock_inicial_vn.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }

  public function matchExcelTemplate(): StreamedResponse
  {
    $combustibles = ApFuelType::whereNull('deleted_at')->where('status', true)->pluck('description')->toArray();

    $spreadsheet = new Spreadsheet();

    // Hoja oculta de listas
    $listsSheet = $spreadsheet->createSheet(1);
    $listsSheet->setTitle('_Listas');
    $listsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
    $listsSheet->setCellValue('A1', 'Combustible');
    foreach ($combustibles as $i => $val) {
      $listsSheet->setCellValue('A' . ($i + 2), $val);
    }
    $combRange = count($combustibles) > 0
      ? '_Listas!$A$2:$A$' . (count($combustibles) + 1)
      : null;

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Match Modelos VN');

    $headers = [
      'A' => 'VERSION',
      'B' => 'MARCA',
      'C' => 'UM',
      'D' => 'cantidad',
      'E' => 'Costo Unitario',
      'F' => 'Costo Total',
      'G' => 'SitioId',
      'H' => 'Serie',
      'I' => 'Cta. Inventario',
      'J' => 'Cta. ContraPartida',
      'K' => 'Año Modelo',
      'L' => 'COMBUSTIBLE',
    ];

    foreach ($headers as $col => $label) {
      $sheet->setCellValue("{$col}1", $label);
    }

    $headerStyle = [
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
      'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
    ];
    $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Fila de ejemplo
    $example = [
      'A2' => 'POER MT 2.4T 4X4 STD NEW FL',
      'B2' => 'GREAT WALL',
      'C2' => 'UND',
      'D2' => 1,
      'E2' => 66030.23,
      'F2' => 66030.23,
      'G2' => 'EXR-CM-CIX',
      'H2' => 'LGWDCF19XVJ609324',
      'I2' => '2811000-01',
      'J2' => '0211111-01',
      'K2' => 2027,
      'L2' => !empty($combustibles) ? $combustibles[0] : 'DIESEL',
    ];
    foreach ($example as $cell => $value) {
      $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A2:L2')->applyFromArray([
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF3FB']],
      'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
    ]);

    // Anchos
    $widths = ['A' => 38, 'B' => 16, 'C' => 8, 'D' => 10, 'E' => 16, 'F' => 16,
               'G' => 16, 'H' => 24, 'I' => 18, 'J' => 18, 'K' => 12, 'L' => 18];
    foreach ($widths as $col => $w) {
      $sheet->getColumnDimension($col)->setWidth($w);
    }

    // Dropdown combustible en columna L
    if ($combRange) {
      for ($row = 2; $row <= 1001; $row++) {
        $v = $sheet->getCell("L{$row}")->getDataValidation();
        $v->setType(DataValidation::TYPE_LIST);
        $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $v->setAllowBlank(true);
        $v->setShowDropDown(true);
        $v->setFormula1($combRange);
      }
    }

    $sheet->freezePane('A2');
    $spreadsheet->setActiveSheetIndex(0);

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'plantilla_match_modelos_vn.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }

  public function matchFromExcel(UploadedFile $file): StreamedResponse
  {
    // setReadDataOnly(true) evita que PhpSpreadsheet evalúe referencias
    // estructuradas de tablas Excel que causarían "Table for Structured Reference
    // cannot be identified"
    $reader = IOFactory::createReaderForFile($file->getPathname());
    if (method_exists($reader, 'setReadDataOnly')) {
      $reader->setReadDataOnly(true);
    }
    $spreadsheet = $reader->load($file->getPathname());
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    // Leer cabeceras de la fila 1 y mapear columnas por nombre
    $headers = [];
    $colIndex = 1;
    while (true) {
      $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
      $header = trim((string)$sheet->getCell("{$colLetter}1")->getValue());
      if ($header === '' && $colIndex > 1) break;
      $headers[$colLetter] = $header;
      if ($colLetter === $highestCol) break;
      $colIndex++;
    }

    // Identificar columnas clave (case-insensitive)
    $colVersion = null;
    $colModelYear = null;
    $colFuel = null;
    foreach ($headers as $letter => $name) {
      $normalized = mb_strtoupper(trim($name));
      if ($normalized === 'VERSION') $colVersion = $letter;
      if ($normalized === 'AÑO MODELO') $colModelYear = $letter;
      if ($normalized === 'COMBUSTIBLE') $colFuel = $letter;
    }

    if (!$colVersion || !$colModelYear || !$colFuel) {
      throw new Exception('El archivo no contiene las columnas requeridas: VERSION, AÑO MODELO, COMBUSTIBLE.');
    }

    // Pre-cargar combustibles en memoria para evitar N+1
    $fuelMap = ApFuelType::whereNull('deleted_at')
      ->get()
      ->keyBy(fn($f) => mb_strtoupper(trim($f->description)));

    // Pre-cargar solo modelos COMERCIAL activos con fuel_id en memoria
    $models = ApModelsVn::whereNull('deleted_at')
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->select(['id', 'code', 'version', 'model_year', 'fuel_id'])
      ->get();

    // Normaliza espacios: reemplaza no-breaking spaces y colapsa múltiples espacios
    $normalizeVersion = fn(string $v): string => mb_strtoupper(preg_replace('/[\s\x{00A0}]+/u', ' ', trim($v)));

    // Agrupar por (version_upper|model_year|fuel_id) para búsqueda O(1)
    $modelIndex = [];
    foreach ($models as $m) {
      $key = $normalizeVersion($m->version) . '|' . $m->model_year . '|' . $m->fuel_id;
      // Guardar solo el primero encontrado; podrían existir duplicados
      if (!isset($modelIndex[$key])) {
        $modelIndex[$key] = $m->code;
      }
    }

    // Determinar columna de salida (siguiente tras la última)
    $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
    $codeColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex + 1);
    $obsColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex + 2);

    // Cabeceras nuevas
    $headerStyle = [
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    ];
    $sheet->setCellValue("{$codeColLetter}1", 'Código Modelo');
    $sheet->setCellValue("{$obsColLetter}1", 'Observación');
    $sheet->getStyle("{$codeColLetter}1:{$obsColLetter}1")->applyFromArray($headerStyle);
    $sheet->getColumnDimension($codeColLetter)->setWidth(18);
    $sheet->getColumnDimension($obsColLetter)->setWidth(50);

    // Estilos para resultado encontrado / no encontrado
    $foundStyle = [
      'font' => ['bold' => true, 'color' => ['rgb' => '1D6B38']],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6F0DE']],
    ];
    $notFoundStyle = [
      'font' => ['color' => ['rgb' => '9C0006']],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC7CE']],
    ];

    for ($row = 2; $row <= $highestRow; $row++) {
      $rawVersion = trim((string)$sheet->getCell("{$colVersion}{$row}")->getValue());
      $rawModelYear = trim((string)$sheet->getCell("{$colModelYear}{$row}")->getValue());
      $rawFuel = trim((string)$sheet->getCell("{$colFuel}{$row}")->getValue());

      // Fila vacía → saltar
      if ($rawVersion === '' && $rawModelYear === '' && $rawFuel === '') continue;

      $versionUp = $normalizeVersion($rawVersion);
      $fuelUp = mb_strtoupper(preg_replace('/[\s\x{00A0}]+/u', ' ', trim($rawFuel)));

      // Buscar combustible
      $fuelRecord = $fuelMap[$fuelUp] ?? null;

      if (!$fuelRecord) {
        $sheet->setCellValue("{$codeColLetter}{$row}", '');
        $sheet->setCellValue("{$obsColLetter}{$row}", "Combustible \"{$rawFuel}\" no registrado en el sistema.");
        $sheet->getStyle("{$codeColLetter}{$row}:{$obsColLetter}{$row}")->applyFromArray($notFoundStyle);
        continue;
      }

      // Validar año
      if (!is_numeric($rawModelYear)) {
        $sheet->setCellValue("{$codeColLetter}{$row}", '');
        $sheet->setCellValue("{$obsColLetter}{$row}", "Año de modelo inválido: \"{$rawModelYear}\".");
        $sheet->getStyle("{$codeColLetter}{$row}:{$obsColLetter}{$row}")->applyFromArray($notFoundStyle);
        continue;
      }

      $key = "{$versionUp}|{$rawModelYear}|{$fuelRecord->id}";
      $code = $modelIndex[$key] ?? null;

      if ($code) {
        $sheet->setCellValue("{$codeColLetter}{$row}", $code);
        $sheet->setCellValue("{$obsColLetter}{$row}", 'Encontrado');
        $sheet->getStyle("{$codeColLetter}{$row}:{$obsColLetter}{$row}")->applyFromArray($foundStyle);
      } else {
        // Diagnóstico más detallado
        $byVersion = collect($models)->first(fn($m) => $normalizeVersion($m->version) === $versionUp);
        if (!$byVersion) {
          $obs = "No se encontró ningún modelo con versión \"{$rawVersion}\".";
        } else {
          $byVersionYear = collect($models)->first(
            fn($m) => $normalizeVersion($m->version) === $versionUp && (string)$m->model_year === (string)$rawModelYear
          );
          if (!$byVersionYear) {
            $obs = "Versión encontrada, pero no con año {$rawModelYear}.";
          } else {
            $obs = "Versión y año encontrados, pero no con combustible \"{$rawFuel}\".";
          }
        }
        $sheet->setCellValue("{$codeColLetter}{$row}", '');
        $sheet->setCellValue("{$obsColLetter}{$row}", $obs);
        $sheet->getStyle("{$codeColLetter}{$row}:{$obsColLetter}{$row}")->applyFromArray($notFoundStyle);
      }
    }

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'modelos_vn_match.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }

  public function importInitialStockFromExcel(UploadedFile $file): array
  {
    $preview = false; // cambiar a false para persistir
    $reader = IOFactory::createReaderForFile($file->getPathname());
    if (method_exists($reader, 'setReadDataOnly')) {
      $reader->setReadDataOnly(true);
    }
    $spreadsheet = $reader->load($file->getPathname());
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    // Map headers from row 1 (uppercase normalized)
    $colMap = [];
    $colIndex = 1;
    while (true) {
      $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
      $header = mb_strtoupper(trim(str_replace('*', '', (string)$sheet->getCell("{$letter}1")->getValue())));
      if ($header === '' && $colIndex > 1) break;
      $colMap[$header] = $letter;
      if ($letter === $highestCol) break;
      $colIndex++;
    }

    $cCodigo = $colMap['CODIGO'] ?? null;
    $cSerie = $colMap['SERIE'] ?? null;
    $cSitio = $colMap['SITIOID'] ?? null;
    $cQty = $colMap['CANTIDAD'] ?? null;
    $cUPrice = $colMap['COSTO UNITARIO'] ?? null;
    $cTotal = $colMap['COSTO TOTAL'] ?? null;

    if (!$cCodigo || !$cSerie || !$cSitio) {
      throw new Exception('El archivo no contiene las columnas requeridas: CODIGO, SERIE, SITIOID.');
    }

    $modelIndex = ApModelsVn::whereNull('deleted_at')
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->select(['id', 'code', 'version', 'model_year', 'class_id'])
      ->get()
      ->keyBy(fn($m) => mb_strtoupper(trim($m->code)));

    // Clave compuesta dyn_code|article_class_id para distinguir bodegas del mismo sitio por categoría de vehículo
    $warehouseMap = Warehouse::whereNull('deleted_at')
      ->where('status', true)
      ->get()
      ->keyBy(fn($w) => mb_strtoupper(trim($w->dyn_code ?? '')) . '|' . ($w->article_class_id ?? ''));

    $uploadDate = \Carbon\Carbon::parse('2026-06-30');

    $exchangeRate = \App\Models\gp\maestroGeneral\ExchangeRate::where('date', $uploadDate->toDateString())->first();
    if (!$exchangeRate) {
      throw new \Exception('No se ha registrado el tipo de cambio para el 30 de junio de 2026.');
    }

    $results = ['preview' => $preview, 'created' => 0, 'errors' => 0, 'rows' => []];

    $checklistItems = ApDeliveryReceivingChecklist::where('status', true)->get();
    $placeholderPhotoUrl = url('images/ap/body_car.png');

    $dercoPartner = BusinessPartners::find(4);
    $dercoEstablishment = BusinessPartnersEstablishment::where('business_partner_id', 4)->first();
    $automotoresEstablishments = BusinessPartnersEstablishment::where('business_partner_id', Constants::AP_AUTOMOTORES_PARTNER_ID)
      ->get()
      ->keyBy('sede_id');
    $automotoresFallbackEstablishment = $automotoresEstablishments->first();

    for ($row = 2; $row <= $highestRow; $row++) {
      $rawCodigo = mb_strtoupper(trim((string)$sheet->getCell("{$cCodigo}{$row}")->getValue()));
      $rawSerie = trim((string)$sheet->getCell("{$cSerie}{$row}")->getValue());
      $rawSitio = trim((string)$sheet->getCell("{$cSitio}{$row}")->getValue());

      if ($rawCodigo === '' && $rawSerie === '') continue;

      $rawQty = $cQty ? (float)$sheet->getCell("{$cQty}{$row}")->getValue() : 1;
      $rawUPrice = $cUPrice ? (float)$sheet->getCell("{$cUPrice}{$row}")->getValue() : 0;
      $rawTotal = $cTotal ? (float)$sheet->getCell("{$cTotal}{$row}")->getValue() : 0;

      DB::beginTransaction();

      try {
        if (strlen($rawSerie) < 10) {
          throw new Exception("SERIE/VIN inválido: \"{$rawSerie}\".");
        }

        $modelRecord = $modelIndex[$rawCodigo] ?? null;
        if (!$modelRecord) {
          throw new Exception("Código de modelo no encontrado: \"{$rawCodigo}\".");
        }

        $sitioUp = mb_strtoupper(trim($rawSitio));
        $warehouse = $warehouseMap[$sitioUp . '|' . ($modelRecord->class_id ?? '')] ?? null;

        // EXR = "existencias por recibir" → vehículo llega hasta EN TRÁNSITO
        $isPorRecibir = $warehouse && str_starts_with(mb_strtoupper($warehouse->dyn_code ?? ''), 'EXR');
        $finalStatus = $isPorRecibir ? ApVehicleStatus::VEHICULO_EN_TRAVESIA : ApVehicleStatus::INVENTARIO_VN;

        $existingVehicle = Vehicles::where('vin', $rawSerie)->first();

        if ($existingVehicle) {
          if ($existingVehicle->type_operation_id !== ApMasters::TIPO_OPERACION_POSTVENTA) {
            throw new Exception("El VIN \"{$rawSerie}\" ya existe y no es de postventa.");
          }
          $existingVehicle->update([
            'type_operation_id'    => ApMasters::TIPO_OPERACION_COMERCIAL,
            'ap_models_vn_id'      => $modelRecord->id,
            'ap_vehicle_status_id' => $finalStatus,
          ]);
          $vehicle = $existingVehicle->fresh();
        } else {
          $vehicle = Vehicles::create([
            'vin'                  => $rawSerie,
            'engine_number'        => 'SI-' . $rawSerie,
            'year'                 => (int)$modelRecord->model_year,
            'ap_models_vn_id'      => $modelRecord->id,
            'warehouse_id'         => $warehouse?->id,
            'vehicle_color_id'     => ApMasters::COLOR_OTHERS_ID,
            'engine_type_id'       => ApMasters::ENGINE_TYPE_OTHERS_ID,
            'ap_vehicle_status_id' => $finalStatus,
            'type_operation_id'    => ApMasters::TIPO_OPERACION_COMERCIAL,
            'status'               => true,
          ]);
        }

        $movPedido = VehicleMovement::create([
          'movement_type'        => VehicleMovement::ORDERED,
          'ap_vehicle_id'        => $vehicle->id,
          'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN,
          'previous_status_id'   => null,
          'new_status_id'        => ApVehicleStatus::PEDIDO_VN,
          'movement_date'        => $uploadDate,
          'observation'          => 'Saldo inicial',
        ]);

        VehicleMovement::create([
          'movement_type'        => VehicleMovement::IN_TRANSIT,
          'ap_vehicle_id'        => $vehicle->id,
          'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
          'previous_status_id'   => ApVehicleStatus::PEDIDO_VN,
          'new_status_id'        => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
          'movement_date'        => $uploadDate,
          'observation'          => 'Saldo inicial - tránsito',
        ]);

        $shippingGuide = null;
        if (!$isPorRecibir) {
          $movInventario = VehicleMovement::create([
            'movement_type'        => VehicleMovement::INVENTORY,
            'ap_vehicle_id'        => $vehicle->id,
            'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
            'previous_status_id'   => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
            'new_status_id'        => ApVehicleStatus::INVENTARIO_VN,
            'movement_date'        => $uploadDate,
            'observation'          => 'Saldo inicial - ingreso a inventario',
          ]);

          $automotoresEstablishment = $automotoresEstablishments[$warehouse?->sede_id] ?? $automotoresFallbackEstablishment;

          $shippingGuide = ShippingGuides::create([
            'document_type'          => ShippingGuides::DOCUMENT_TYPE_GR,
            'issuer_type'            => ShippingGuides::ISSUER_TYPE_SUPPLIER,
            'series'                 => 'SI',
            'correlative'            => $rawSerie,
            'document_number'        => 'SI-' . $rawSerie,
            'issue_date'             => $uploadDate,
            'vehicle_movement_id'    => $movInventario->id,
            'requires_sunat'         => false,
            'is_sunat_registered'    => true,
            'aceptada_por_sunat'     => true,
            'sent_at'                => $uploadDate,
            'accepted_at'            => $uploadDate,
            'is_received'            => true,
            'received_date'          => $uploadDate,
            'received_by'            => auth()->id(),
            'status'                 => true,
            'send_dynamics'          => true,
            'status_dynamic'         => true,
            'migration_status'       => 'completed',
            'migrated_at'            => $uploadDate,
            'sede_transmitter_id'    => $warehouse?->sede_id,
            'sede_receiver_id'       => $warehouse?->sede_id,
            'transmitter_id'         => $dercoEstablishment?->id,
            'receiver_id'            => $automotoresEstablishment?->id,
            'transport_company_id'   => $dercoPartner?->id,
            'ruc_transport'          => $dercoPartner?->num_doc,
            'company_name_transport' => $dercoPartner?->full_name,
            'transfer_reason_id'     => SunatConcepts::TRANSFER_REASON_COMPRA,
            'transfer_modality_id'   => SunatConcepts::TYPE_TRANSPORTATION_PUBLICO,
            'origin_ubigeo'          => $dercoEstablishment?->ubigeo ?? '-',
            'origin_address'         => $dercoEstablishment?->address ?? '-',
            'destination_ubigeo'     => $automotoresEstablishment?->ubigeo ?? '-',
            'destination_address'    => $automotoresEstablishment?->address ?? '-',
            'notes'                  => 'Saldo inicial importado desde Excel',
            'note_received'          => 'SALDO INICIAL',
            'created_by'             => auth()->id(),
          ]);

          foreach ($checklistItems as $item) {
            ApReceivingChecklist::create([
              'receiving_id'      => $item->id,
              'shipping_guide_id' => $shippingGuide->id,
              'kilometers'        => 0,
            ]);
          }

          ApReceivingInspection::create([
            'shipping_guide_id'    => $shippingGuide->id,
            'photo_front_url'      => $placeholderPhotoUrl,
            'photo_back_url'       => $placeholderPhotoUrl,
            'photo_left_url'       => $placeholderPhotoUrl,
            'photo_right_url'      => $placeholderPhotoUrl,
            'general_observations' => 'SALDO INICIAL',
            'inspected_by'         => auth()->id(),
          ]);
        }

        $correlative = (DB::table('ap_purchase_order')->max('number_correlative') ?? 0) + 1;

        $po = PurchaseOrder::create([
          'number'                    => 'SI-' . $rawSerie,
          'number_correlative'        => $correlative,
          'number_guide'              => 'SI-' . $rawSerie,
          'invoice_series'            => 'SI',
          'invoice_number'            => $rawSerie,
          'supplier_id'               => 4, // DERCO PERU S.A.
          'currency_id'               => 1, // USD - DOLAR AMERICANO
          'exchange_rate_id'          => $exchangeRate->id,
          'created_by'                => auth()->id(),
          'vehicle_movement_id'       => $movPedido->id,
          'emission_date'             => $uploadDate->toDateString(),
          'subtotal'                  => round($rawTotal / 1.18, 2),
          'igv'                       => round($rawTotal - $rawTotal / 1.18, 2),
          'total'                     => $rawTotal,
          'sede_id'                   => $warehouse?->sede_id,
          'warehouse_id'              => $warehouse?->id,
          'type_operation_id'         => ApMasters::TIPO_OPERACION_COMERCIAL,
          'migration_status'          => 'completed',
          'migrated_at'               => $uploadDate,
          'invoice_dynamics'          => 'SI-' . $rawSerie,
          'receipt_dynamics'          => 'SI-' . $rawSerie,
          'invoice_sync_attempted_at' => $uploadDate,
          'invoice_sync_attempts'     => 1,
          'status'                    => true,
          'notes'                     => 'Saldo inicial importado desde Excel',
        ]);

        PurchaseOrderItem::create([
          'purchase_order_id' => $po->id,
          'is_vehicle'        => true,
          'description'       => $modelRecord->version,
          'unit_price'        => $rawUPrice,
          'quantity'          => $rawQty,
          'quantity_received' => $rawQty,
          'quantity_pending'  => 0,
          'total'             => $rawTotal,
        ]);

        $movements = ['ORDERED', 'IN_TRANSIT'];
        if (!$isPorRecibir) {
          $movements[] = 'INVENTORY';
        }

        if ($preview) {
          DB::rollBack();
          $results['rows'][] = [
            'row'            => $row,
            'vin'            => $rawSerie,
            'code'           => $rawCodigo,
            'sitio'          => $sitioUp,
            'status'         => 'preview_ok',
            'warehouse_id'   => $warehouse?->id,
            'warehouse_name' => $warehouse?->name ?? '(sin almacén)',
            'warehouse_code' => $warehouse?->dyn_code ?? null,
            'final_status'   => $isPorRecibir ? 'EN_TRÁNSITO (EXR)' : 'INVENTARIO_VN',
            'movements'      => $movements,
            'po_number'      => 'SI-' . $rawSerie,
            'shipping_guide' => $isPorRecibir ? null : 'SI-' . $rawSerie,
            'checklist'      => $isPorRecibir ? null : $checklistItems->count() . ' ítems',
            'inspection'     => $isPorRecibir ? null : 'placeholder (body_car.png)',
            'total'          => $rawTotal,
            'subtotal'       => round($rawTotal / 1.18, 2),
            'igv'            => round($rawTotal - $rawTotal / 1.18, 2),
            'emission_date'  => $uploadDate->toDateString(),
            'exchange_rate'  => $exchangeRate->rate ?? null,
          ];
        } else {
          DB::commit();
          $results['created']++;
          $results['rows'][] = [
            'row'               => $row,
            'vin'               => $rawSerie,
            'code'              => $rawCodigo,
            'sitio'             => $sitioUp,
            'status'            => 'created',
            'warehouse_id'      => $warehouse?->id,
            'warehouse_name'    => $warehouse?->name ?? '(sin almacén)',
            'final_status'      => $isPorRecibir ? 'EN_TRÁNSITO (EXR)' : 'INVENTARIO_VN',
            'movements'         => $movements,
            'po_number'         => 'SI-' . $rawSerie,
            'shipping_guide_id' => $shippingGuide?->id,
          ];
        }
      } catch (\Throwable $th) {
        DB::rollBack();
        $results['errors']++;
        $results['rows'][] = [
          'row'    => $row,
          'vin'    => $rawSerie,
          'code'   => $rawCodigo ?? '',
          'sitio'  => isset($sitioUp) ? $sitioUp : mb_strtoupper(trim((string)$sheet->getCell("{$cSitio}{$row}")->getValue())),
          'status' => 'error',
          'error'  => $th->getMessage(),
        ];
      }
    }

    return $results;
  }
}

<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnDynamicsResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Jobs\SyncModelVnJob;
use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVnSyncLog;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Imports\ap\vehiculo\ApModelsVnImport;
use App\Imports\ap\vehiculo\ApModelsVnVerifyImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
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
    if ($data['type_operation_id'] === ApMasters::TIPO_OPERACION_COMERCIAL) {
      $existe = ApModelsVn::where('family_id', $data['family_id'])
        ->where('model_year', $data['model_year'])
        ->where('version', $data['version'])
        ->whereNull('deleted_at')
        ->exists();

      if ($existe) {
        throw new Exception('Ya existe un modelo con esa familia y año.');
      }

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
    $familias      = ApFamilies::where('status', true)->pluck('description')->toArray();
    $clases        = ApClassArticle::where('status', true)->pluck('description')->toArray();
    $combustibles  = ApFuelType::where('status', true)->pluck('description')->toArray();
    $tiposVehiculo = ApMasters::where('type', 'TIPO_VEHICULO')->where('status', true)->pluck('description')->toArray();
    $carrocerias   = ApMasters::where('type', 'TIPO_CARROCERIA')->where('status', true)->pluck('description')->toArray();
    $tracciones    = ApMasters::where('type', 'TIPO_TRACCION')->where('status', true)->pluck('description')->toArray();
    $transmisiones = ApMasters::where('type', 'TRANSMISION_VEHICULO')->where('status', true)->pluck('description')->toArray();
    $monedas       = TypeCurrency::where('status', true)->pluck('code')->toArray();
    $tiposOp       = ApMasters::where('type', 'TIPO_OPERACION')->where('status', true)->pluck('description')->toArray();

    $spreadsheet = new Spreadsheet();

    // ── Hoja de listas (oculta) ──────────────────────────────────────────────
    $listsSheet = $spreadsheet->createSheet(1);
    $listsSheet->setTitle('_Listas');
    $listsSheet->getTabColor()->setRGB('CCCCCC');
    $listsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    $listColumns = [
      'A' => ['titulo' => 'Familia',         'valores' => $familias],
      'B' => ['titulo' => 'Clase',            'valores' => $clases],
      'C' => ['titulo' => 'Combustible',      'valores' => $combustibles],
      'D' => ['titulo' => 'Tipo Vehículo',    'valores' => $tiposVehiculo],
      'E' => ['titulo' => 'Tipo Carrocería',  'valores' => $carrocerias],
      'F' => ['titulo' => 'Tipo Tracción',    'valores' => $tracciones],
      'G' => ['titulo' => 'Transmisión',      'valores' => $transmisiones],
      'H' => ['titulo' => 'Moneda',           'valores' => $monedas],
      'I' => ['titulo' => 'Tipo Operación',   'valores' => $tiposOp],
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
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
    ];
    $sheet->getStyle('A1:AE1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(40);

    // ── Estilo de fila de ejemplo ────────────────────────────────────────────
    $exampleStyle = [
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EBF3FB']],
      'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
    ];
    $sheet->getStyle('A2:AE2')->applyFromArray($exampleStyle);

    // ── Anchos de columna ────────────────────────────────────────────────────
    $widths = [
      'A' => 6,  'B' => 18, 'C' => 20, 'D' => 22, 'E' => 30,
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
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F75B6']],
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
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E79']],
      'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
    ];
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(40);

    $exampleStyle = [
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EBF3FB']],
      'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
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
      $dynamicsError   = null;
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
      'rows_processed' => $results['rows_processed'],
      'existing_count' => count($existing),
      'not_found_count'=> count($results['not_found']),
      'existing'       => $existing,
      'not_found'      => $results['not_found'],
    ];
  }

  public function syncModel(int $id): array
  {
    $model = $this->find($id);

    if (!$model->code) {
      throw new Exception("El modelo ID {$id} no tiene código asignado y no puede sincronizarse con Dynamics.");
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
      'log_id'     => $log->id,
      'model_id'   => $model->id,
      'code'       => $model->code,
      'status'     => $log->status,
      'message'    => 'Job de sincronización despachado correctamente.',
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

  public function syncLogs(Request $request): \Illuminate\Pagination\LengthAwarePaginator
  {
    $query = ApModelsVnSyncLog::with(['model:id,version,model_year,fuel_id'])
      ->when($request->model_id, fn($q, $v) => $q->where('model_vn_id', $v))
      ->when($request->status,   fn($q, $v) => $q->where('status', $v))
      ->when($request->code,     fn($q, $v) => $q->where('code', 'like', "%{$v}%"))
      ->orderBy('id', 'desc');

    return $query->paginate($request->per_page ?? 20);
  }

  public function importFromExcel(UploadedFile $file): array
  {
    $import = new ApModelsVnImport();
    Excel::import($import, $file);
    $results = $import->getResults();

    $hayErrores  = !empty($results['errors']);
    $totalErrors = count($results['errors']);
    $msg = "Importación completada: {$results['created']} creado(s), {$results['skipped']} omitido(s) por duplicado.";
    if ($hayErrores) {
      $msg .= " {$totalErrors} fila(s) con error.";
    }

    return [
      'success'        => !$hayErrores,
      'message'        => $msg,
      'rows_processed' => $results['rows_processed'],
      // errores primero (mayor nivel de alerta)
      'errors_count'   => $totalErrors,
      'errors'         => $results['errors'],
      // omitidos
      'skipped'        => $results['skipped'],
      'skipped_rows'   => $results['skipped_rows'],
      // creados
      'created'        => $results['created'],
      'created_rows'   => $results['created_rows'],
    ];
  }
}

<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Imports\ap\vehiculo\ApModelsVnImport;
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
      'A'  => 'Tipo de Operación *',
      'B'  => 'Familia *',
      'C'  => 'Clase *',
      'D'  => 'Versión / Descripción del Modelo *',
      'E'  => 'Año del Modelo',
      'F'  => 'Combustible *',
      'G'  => 'Tipo de Vehículo *',
      'H'  => 'Tipo de Carrocería *',
      'I'  => 'Tipo de Tracción *',
      'J'  => 'Transmisión *',
      'K'  => 'Moneda *',
      'L'  => 'Potencia (HP)',
      'M'  => 'Distancia entre Ejes (mm)',
      'N'  => 'N° de Ejes',
      'O'  => 'Ancho (mm)',
      'P'  => 'Largo (mm)',
      'Q'  => 'Alto (mm)',
      'R'  => 'N° de Asientos',
      'S'  => 'N° de Puertas',
      'T'  => 'Peso Neto (kg)',
      'U'  => 'Peso Bruto (kg)',
      'V'  => 'Carga Útil (kg)',
      'W'  => 'Cilindrada (cc)',
      'X'  => 'N° de Cilindros',
      'Y'  => 'N° de Pasajeros',
      'Z'  => 'N° de Ruedas',
      'AA' => 'Precio Distribuidor',
      'AB' => 'Costo de Transporte',
      'AC' => 'Otros Montos',
      'AD' => 'Descuento de Compra',
    ];

    foreach ($headers as $cell => $label) {
      $sheet->setCellValue("{$cell}1", $label);
    }

    // ── Fila de ejemplo ──────────────────────────────────────────────────────
    $example = [
      'A2'  => !empty($tiposOp) ? $tiposOp[0] : 'COMERCIAL',
      'B2'  => !empty($familias) ? $familias[0] : 'HILUX',
      'C2'  => !empty($clases) ? $clases[0] : 'VEHICULOS COMERCIALES',
      'D2'  => 'HILUX 4X4 SRV AT',
      'E2'  => 2025,
      'F2'  => !empty($combustibles) ? $combustibles[0] : 'DIESEL',
      'G2'  => !empty($tiposVehiculo) ? $tiposVehiculo[0] : 'PICK UP',
      'H2'  => !empty($carrocerias) ? $carrocerias[0] : 'CABINA DOBLE',
      'I2'  => !empty($tracciones) ? $tracciones[0] : '4X4',
      'J2'  => !empty($transmisiones) ? $transmisiones[0] : 'AUTOMATICA',
      'K2'  => !empty($monedas) ? $monedas[0] : 'USD',
      'L2'  => '204 HP',
      'M2'  => '3085',
      'N2'  => '2',
      'O2'  => '1855',
      'P2'  => '5335',
      'Q2'  => '1815',
      'R2'  => '5',
      'S2'  => '4',
      'T2'  => '2080',
      'U2'  => '3010',
      'V2'  => '930',
      'W2'  => '2755',
      'X2'  => '4',
      'Y2'  => '5',
      'Z2'  => '4',
      'AA2' => 44800.00,
      'AB2' => 850.00,
      'AC2' => 0.00,
      'AD2' => 0.00,
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
    $sheet->getStyle('A1:AD1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(40);

    // ── Estilo de fila de ejemplo ────────────────────────────────────────────
    $exampleStyle = [
      'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EBF3FB']],
      'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
    ];
    $sheet->getStyle('A2:AD2')->applyFromArray($exampleStyle);

    // ── Anchos de columna ────────────────────────────────────────────────────
    $widths = [
      'A' => 18, 'B' => 20, 'C' => 22, 'D' => 30, 'E' => 10,
      'F' => 18, 'G' => 18, 'H' => 20, 'I' => 18, 'J' => 16,
      'K' => 10, 'L' => 14, 'M' => 20, 'N' => 10, 'O' => 12,
      'P' => 12, 'Q' => 12, 'R' => 14, 'S' => 14, 'T' => 14,
      'U' => 14, 'V' => 14, 'W' => 14, 'X' => 14, 'Y' => 16,
      'Z' => 14, 'AA' => 20, 'AB' => 20, 'AC' => 16, 'AD' => 20,
    ];
    foreach ($widths as $col => $w) {
      $sheet->getColumnDimension($col)->setWidth($w);
    }

    // ── Validación con dropdowns (filas 2-1001) ──────────────────────────────
    $dropdownMap = [
      'A' => $listRanges['I'], // Tipo Operación
      'B' => $listRanges['A'], // Familia
      'C' => $listRanges['B'], // Clase
      'F' => $listRanges['C'], // Combustible
      'G' => $listRanges['D'], // Tipo Vehículo
      'H' => $listRanges['E'], // Carrocería
      'I' => $listRanges['F'], // Tracción
      'J' => $listRanges['G'], // Transmisión
      'K' => $listRanges['H'], // Moneda
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

  public function importFromExcel(UploadedFile $file): array
  {
    $import = new ApModelsVnImport();
    Excel::import($import, $file);
    $results = $import->getResults();

    $hayErrores = !empty($results['errors']);
    $msg = "Importación completada: {$results['created']} creado(s), {$results['skipped']} omitido(s) por duplicado.";
    if ($hayErrores) {
      $msg .= ' ' . count($results['errors']) . ' fila(s) con error.';
    }

    return [
      'success'        => !$hayErrores,
      'message'        => $msg,
      'created'        => $results['created'],
      'skipped'        => $results['skipped'],
      'rows_processed' => $results['rows_processed'],
      'errors'         => $results['errors'],
    ];
  }
}

<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\ChangeStateRequest;
use App\Http\Requests\tp\comercial\EndRouteRequest;
use App\Http\Requests\tp\comercial\FuelRecordRequest;
use App\Http\Requests\tp\comercial\StartRouteRequest;
use App\Http\Requests\tp\comercial\StoreTravelControlRequest;
use App\Http\Requests\tp\comercial\UpdateTravelControlRequest;
use App\Http\Resources\tp\comercial\DispatchItemResource;
use App\Http\Resources\tp\comercial\TravelControlResource;
use App\Models\tp\comercial\TravelExpense;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Http\Services\tp\comercial\TravelControlService;
use App\Models\tp\comercial\DispatchItem;
use App\Models\tp\comercial\DispatchStatus;
use App\Models\tp\comercial\DriverTravelRecord;
use App\Models\tp\comercial\TravelControl;
use Exception;
use Illuminate\Http\Request;
use Log;
use Throwable;

class TravelControlController extends Controller
{

  protected TravelControlService $service;

  public function __construct(TravelControlService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    try {
      return response()->json($this->service->list($request));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTravelControlRequest $request)
  {
    try {
      $data = $request->validated();
      return response()->json($this->service->store($data), 201);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return response()->json($this->service->show($id));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateTravelControlRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->update($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return response()->json($this->service->destroy($id));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function startRoute(StartRouteRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      return response()->json($this->service->startRoute($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function endRoute(EndRouteRequest $request, $id)
  {
    try {

      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->endRoute($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function fuelRecord(FuelRecordRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->fuelRecord($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function changeState(ChangeStateRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      return response()->json($this->service->changeState($data));

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function driverRecords($id)
  {
    try {
      return response()->json([
        'data' => $this->service->driverRecords($id)
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  //validar datos
  public function validateMileage(Request $request, $vehicle_id)
  {
    try {
      return response()->json([
        'data' => $this->service->validateMileage($vehicle_id)
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function availableStates()
  {
    try {
      return response()->json([
        'data' => $this->service->availableStates()
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }

  }

  /**
   * Start a specific segment (sub-trip)
   */
  public function startSegment(Request $request, $travelId, $segmentId)
  {
    try {
      $segment = DispatchItem::where('despacho_id', $travelId)
        ->findOrFail($segmentId);

      if (!$segment->canStart()) {
        throw new Exception('This segment cannot be started');
      }

      $firstPending = DispatchItem::where('despacho_id', $travelId)
        ->where('segment_status', 'pending')
        ->orderBy('id', 'asc')
        ->first();

      if ($firstPending && $firstPending->id != $segmentId) {
        throw new Exception('Debes completar los tramos en orden. El siguiente tramo pendiente es: ' . $firstPending->getSegmentNameAttribute());
      }

      $request->validate([
        'mileage' => 'required|numeric|min:0',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
      ]);

      $segment->update([
        'segment_status' => 'in_progress',
        'initial_mileage' => $request->mileage,
        'actual_start' => now(),
        'start_latitude' => $request->latitude,
        'start_longitude' => $request->longitude,
      ]);


      $firstSegment = DispatchItem::where('despacho_id', $travelId)
        ->orderBy('id', 'asc')
        ->first();

      if ($firstSegment && $firstSegment->id == $segmentId) {
        $travel = TravelControl::find($travelId);
        $travel->update(['km_inicio' => $request->mileage]);
      }


      return response()->json([
        'success' => true,
        'message' => 'Segment started successfully',
        'data' => new DispatchItemResource($segment->fresh())
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * End a specific segment (sub-trip)
   */
  public function endSegment(Request $request, $travelId, $segmentId)
  {
    try {
      $segment = DispatchItem::where('despacho_id', $travelId)
        ->findOrFail($segmentId);

      if (!$segment->canEnd()) {
        throw new Exception('This segment cannot be ended');
      }

      $request->validate([
        'mileage' => 'required|numeric|min:0',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'tonnage' => 'nullable|numeric|min:0',
      ]);

      $totalMileage = $request->mileage - $segment->initial_mileage;
      $totalHours = now()->diffInHours($segment->actual_start, true);

      $segment->update([
        'segment_status' => 'completed',
        'final_mileage' => $request->mileage,
        'total_mileage' => $totalMileage,
        'total_hours' => $totalHours,
        'actual_end' => now(),
        'end_latitude' => $request->latitude,
        'end_longitude' => $request->longitude,
      ]);

      // Update tonnage if provided
      if ($request->has('tonnage')) {
        $segment->update(['cantidad' => $request->tonnage]);
      }

      // // Unlock the next segment
      // $nextSegment = DispatchItem::where('despacho_id', $travelId)
      //   ->where('id', '>', $segmentId)
      //   ->orderBy('id','asc')
      //   ->first();

      // if ($nextSegment && $nextSegment->segment_status === 'locked') {
      //   $nextSegment->update(['segment_status' => 'pending']);
      // }

      //unlock the next segment and set its initial mileage
      $nextSegment = DispatchItem::where('despacho_id', $travelId)
        ->where('id', '>', $segmentId)
        ->orderBy('id', 'asc')
        ->first();

      if ($nextSegment) {
        if ($nextSegment->initial_mileage === null || $nextSegment->initial_mileage == 0) {
          $nextSegment->update([
            'initial_mileage' => $request->mileage // El km final del tramo actual
          ]);
        }
        if ($nextSegment->segment_status === 'locked') {
          $nextSegment->update(['segment_status' => 'pending']);
        }
      }

      // Check if all segments are completed
      $allCompleted = DispatchItem::where('despacho_id', $travelId)
        ->whereNotIn('segment_status', ['completed'])
        ->count() === 0;

      if ($allCompleted) {
        $travel = TravelControl::find($travelId);
        $travel->update([
          'km_fin' => $request->mileage,
          'estado' => DispatchStatus::STATUS_FUEL_PENDING
        ]);
        DriverTravelRecord::create([
          'dispatch_id' => $travel->id,
          'driver_id' => $travel->conductor_id,
          'record_type' => 'end',
          'recorded_at' => now(),
          'recorded_mileage' => $request->mileage,
          'notes' => 'Fin de ruta - Todos los tramos completados',
          'sync_status' => 'completed'
        ]);
      }

      return response()->json([
        'success' => true,
        'message' => 'Segment completed successfully',
        'data' => new DispatchItemResource($segment->fresh()),
        'all_completed' => $allCompleted
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }


  public function getSegments($travelId)
  {
    try {

      $segments = $this->service->getSegments($travelId);

      return response()->json([
        'success' => true,
        'data' => DispatchItemResource::collection($segments),
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get segment progress statistics
   */
  public function getSegmentProgress($travelId)
  {
    try {
      $total = DispatchItem::where('despacho_id', $travelId)->count();
      $completed = DispatchItem::where('despacho_id', $travelId)
        ->where('segment_status', 'completed')->count();
      $inProgress = DispatchItem::where('despacho_id', $travelId)
        ->where('segment_status', 'in_progress')->count();
      $pending = DispatchItem::where('despacho_id', $travelId)
        ->where('segment_status', 'pending')->count();
      $locked = DispatchItem::where('despacho_id', $travelId)
        ->where('segment_status', 'locked')->count();

      return response()->json([
        'success' => true,
        'data' => [
          'total' => $total,
          'completed' => $completed,
          'in_progress' => $inProgress,
          'pending' => $pending,
          'locked' => $locked,
          'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ]
      ]);

    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }


  public function exportReport(Request $request, $id)
  {
    try {
      $travel = TravelControl::with([
        'driver',
        'tract',
        'customer',
        'items.origin',
        'items.destination',
        'items.product',
      ])->findOrFail($id);

      $format = $request->get('format', 'excel');

      // Preparar datos para el reporte
      $reportData = $this->prepareReportData($travel);

      // Columnas del reporte
      $columns = [
        'tipo' => 'Tipo',
        'nro_viaje' => 'N° Viaje',
        'placa' => 'Placa',
        'conductor' => 'Conductor',
        'ruta' => 'Ruta',
        'cliente' => 'Cliente',
        'estado' => 'Estado',
        'km_inicial' => 'Km Inicial',
        'km_final' => 'Km Final',
        'total_km' => 'Total Km',
        'total_horas' => 'Total Horas',
        'toneladas' => 'Toneladas',
        'fecha_inicio' => 'Fecha Inicio',
        'fecha_fin' => 'Fecha Fin',
        'origen' => 'Origen',
        'destino' => 'Destino',
        'producto' => 'Producto',
        'cantidad' => 'Cantidad',
        'km_viaje' => 'Km Viaje',
        'estado_tramo' => 'Estado Tramo',
        'inicio_real' => 'Inicio Real',
        'fin_real' => 'Fin Real',
      ];

      $filename = "reporte_viaje_{$travel->trip_number}_" . now()->format('Y-m-d_H-i-s');

      if ($format === 'pdf') {
        return $this->generatePdf($reportData, $columns, $travel, $filename);
      } else {
        return $this->generateExcel($reportData, $columns, $filename);
      }

    } catch (Throwable $th) {
      \Log::error('Error en exportReport: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
      return $this->error($th->getMessage());
    }
  }

  private function prepareReportData($travel)
  {
    $data = [];

    // Información general del viaje
    $generalInfo = [
      'tipo' => 'INFORMACIÓN GENERAL',
      'nro_viaje' => $travel->trip_number,
      'placa' => $travel->tract->placa ?? 'N/A',
      'conductor' => $travel->driver->nombre_completo ?? 'N/A',
      'ruta' => $this->getRouteFromItems($travel),
      'cliente' => $travel->customer->nombre_completo ?? 'N/A',
      'estado' => $travel->state_spanish,
      'km_inicial' => $travel->km_inicio ?? 'N/A',
      'km_final' => $travel->km_fin ?? 'N/A',
      'total_km' => $travel->total_km ?? 'N/A',
      'total_horas' => $travel->total_hours ?? 'N/A',
      'toneladas' => $travel->tonnage ?? 'N/A',
      'fecha_inicio' => $travel->fecha_viaje?->format('d/m/Y H:i') ?? 'N/A',
      'fecha_fin' => $travel->km_fin ? $travel->updated_at?->format('d/m/Y H:i') : 'N/A',
      'origen' => '',
      'destino' => '',
      'producto' => '',
      'cantidad' => '',
      'km_viaje' => '',
      'estado_tramo' => '',
      'inicio_real' => '',
      'fin_real' => '',
    ];
    $data[] = $generalInfo;

    // Fila separadora
    $separator = array_fill_keys(array_keys($generalInfo), '');
    $separator['tipo'] = 'DETALLE DE TRAMOS';
    $data[] = $separator;

    // Información de cada tramo
    foreach ($travel->items->sortBy('id') as $index => $item) {
      $row = array_fill_keys(array_keys($generalInfo), '');
      $row['tipo'] = "TRAMO " . ($index + 1);
      $row['origen'] = $item->origin->descripcion ?? 'N/A';
      $row['destino'] = $item->destination->descripcion ?? 'N/A';
      $row['producto'] = $item->product->descripcion ?? 'N/A';
      $row['cantidad'] = $item->cantidad ?? 'N/A';
      $row['km_viaje'] = $item->km_viaje ?? 'N/A';
      $row['km_inicial'] = $item->initial_mileage ?? 'N/A';
      $row['km_final'] = $item->final_mileage ?? 'N/A';
      $row['total_km'] = $item->total_mileage ?? 'N/A';
      $row['total_horas'] = $item->total_hours ?? 'N/A';
      $row['estado_tramo'] = $item->state_spanish_tramo ?? 'N/A';
      $row['inicio_real'] = $item->actual_start?->format('d/m/Y H:i') ?? 'N/A';
      $row['fin_real'] = $item->actual_end?->format('d/m/Y H:i') ?? 'N/A';
      $data[] = $row;
    }

    return $data;
  }

  private function getRouteFromItems($travel): string
  {
    if (!$travel->relationLoaded('items') || $travel->items->isEmpty()) {
      return 'Sin ruta';
    }

    $firstItem = $travel->items->first();
    $lastItem = $travel->items->last();

    $origin = $firstItem->origin->descripcion ?? 'Sin origen';
    $destination = $lastItem->destination->descripcion ?? 'Sin destino';

    return $origin . ' - ' . $destination;
  }

  // private function generatePdf($data, $columns, $travel, $filename)
// {
//     $html = '<!DOCTYPE html>
//     <html>
//     <head>
//         <meta charset="UTF-8">
//         <title>Reporte Viaje ' . $travel->trip_number . '</title>
//         <style>
//             body {
//                 font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
//                 margin: 20px;
//                 font-size: 10pt;
//             }
//             h1 {
//                 color: #2C3E50;
//                 text-align: center;
//                 font-size: 18pt;
//                 margin-bottom: 5px;
//             }
//             .header-info {
//                 background-color: #ECF0F1;
//                 padding: 10px;
//                 border-radius: 5px;
//                 margin-bottom: 20px;
//             }
//             .header-info p {
//                 margin: 5px 0;
//                 font-size: 9pt;
//             }
//             .header-info strong {
//                 color: #2C3E50;
//             }
//             h2 {
//                 color: #2C3E50;
//                 font-size: 14pt;
//                 margin-top: 20px;
//                 margin-bottom: 10px;
//                 border-bottom: 2px solid #3498DB;
//                 padding-bottom: 5px;
//             }
//             table {
//                 width: 100%;
//                 border-collapse: collapse;
//                 margin-top: 10px;
//                 font-size: 8pt;
//             }
//             th {
//                 background-color: #2C3E50;
//                 color: white;
//                 padding: 8px;
//                 text-align: center;
//                 font-weight: bold;
//             }
//             td {
//                 border: 1px solid #ddd;
//                 padding: 6px;
//                 text-align: left;
//             }
//             .info-row {
//                 background-color: #E8F0FE;
//                 font-weight: bold;
//             }
//             .separator-row {
//                 background-color: #D9E1F2;
//                 font-weight: bold;
//             }
//             .tramo-row {
//                 background-color: #F9F9F9;
//             }
//             .completed {
//                 color: #27AE60;
//                 font-weight: bold;
//             }
//             .footer {
//                 margin-top: 20px;
//                 text-align: center;
//                 font-size: 8pt;
//                 color: #7F8C8D;
//                 border-top: 1px solid #ddd;
//                 padding-top: 10px;
//             }
//         </style>
//     </head>
//     <body>';

  //     $html .= '<h1>REPORTE DE VIAJE</h1>';
//     $html .= '<div class="header-info">';
//     $html .= '<p><strong>N° Viaje:</strong> ' . $travel->trip_number . '</p>';
//     $html .= '<p><strong>Placa:</strong> ' . ($travel->tract->placa ?? 'N/A') . '</p>';
//     $html .= '<p><strong>Conductor:</strong> ' . ($travel->driver->nombre_completo ?? 'N/A') . '</p>';
//     $html .= '<p><strong>Cliente:</strong> ' . ($travel->customer->nombre_completo ?? 'N/A') . '</p>';
//     $html .= '<p><strong>Fecha Generación:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>';
//     $html .= '</div>';

  //     $html .= '<h2>Detalle de Tramos</h2>';
//     $html .= '<table border="1" cellpadding="5" cellspacing="0">';

  //     // Encabezados
//     $html .= '<thead><tr>';
//     foreach ($columns as $key => $label) {
//         $html .= '<th>' . $label . '</th>';
//     }
//     $html .= '</tr></thead><tbody>';

  //     // Datos
//     foreach ($data as $index => $row) {
//         $rowClass = '';
//         if ($row['tipo'] === 'INFORMACIÓN GENERAL') {
//             $rowClass = 'info-row';
//         } elseif (strpos($row['tipo'], 'DETALLE') !== false) {
//             $rowClass = 'separator-row';
//         } elseif (strpos($row['tipo'], 'TRAMO') !== false) {
//             $rowClass = 'tramo-row';
//         }

  //         $html .= '<tr class="' . $rowClass . '">';
//         foreach (array_keys($columns) as $key) {
//             $value = $row[$key] ?? '';
//             $cellClass = '';

  //             if ($key === 'estado_tramo' && $value === 'completed') {
//                 $cellClass = 'completed';
//             }
//             if ($key === 'total_km' && is_numeric($value) && $value > 0) {
//                 $cellClass = 'completed';
//             }

  //             $html .= '<td class="' . $cellClass . '">' . $value . '</td>';
//         }
//         $html .= '</tr>';
//     }

  //     $html .= '</tbody></table>';

  //     // Resumen de métricas
//     $html .= '<h2>Resumen de Métricas</h2>';
//     $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 50%;">';
//     $html .= '<tr><td><strong>Total Km Recorridos</strong></td><td>' . ($travel->total_km ?? 'N/A') . ' km</td></tr>';
//     $html .= '<tr><td><strong>Total Horas</strong></td><td>' . ($travel->total_hours ?? 'N/A') . ' horas</td></tr>';
//     $html .= '<tr><td><strong>Toneladas Transportadas</strong></td><td>' . ($travel->tonnage ?? 'N/A') . '</td></tr>';
//     $html .= '</table>';

  //     $html .= '<div class="footer">';
//     $html .= '<p>Reporte generado automáticamente por el sistema de Control de Viajes</p>';
//     $html .= '</div>';

  //     $html .= '</body></html>';

  //     $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
//     $pdf->setPaper('a4', 'landscape');

  //     return $pdf->download($filename . '.pdf');
// }

  private function generateExcel($data, $columns, $filename)
  {


    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Título del reporte
    $sheet->setCellValue('A1', 'REPORTE DE VIAJE');
    $sheet->mergeCells('A1:' . $this->getColumnLetter(count($columns)) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Fecha de generación
    $sheet->setCellValue('A2', 'Fecha Generación: ' . now()->format('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:' . $this->getColumnLetter(count($columns)) . '2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    // Encabezados de columna (fila 4)
    $colIndex = 1;
    foreach ($columns as $key => $label) {
      $columnLetter = $this->getColumnLetter($colIndex);
      $sheet->setCellValue($columnLetter . '4', $label);
      $sheet->getStyle($columnLetter . '4')->getFont()->setBold(true);
      $sheet->getStyle($columnLetter . '4')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
      $sheet->getStyle($columnLetter . '4')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF2C3E50'); // Azul oscuro
      $sheet->getStyle($columnLetter . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
      $colIndex++;
    }

    // Datos (desde fila 5)
    $rowIndex = 5;
    foreach ($data as $row) {
      $colIndex = 1;
      foreach ($columns as $key => $label) {
        $columnLetter = $this->getColumnLetter($colIndex);
        $value = $row[$key] ?? '';

        // Aplicar estilos según el tipo de fila
        if ($row['tipo'] === 'INFORMACIÓN GENERAL') {
          // Fila de información general - fondo gris claro
          $sheet->getStyle($columnLetter . $rowIndex)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F0FE');
          $sheet->getStyle($columnLetter . $rowIndex)->getFont()->setBold(true);
        } elseif (strpos($row['tipo'], 'DETALLE') !== false) {
          // Fila separadora - fondo gris
          $sheet->getStyle($columnLetter . $rowIndex)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9E1F2');
          $sheet->getStyle($columnLetter . $rowIndex)->getFont()->setBold(true);
        } elseif (strpos($row['tipo'], 'TRAMO') !== false) {
          // Filas de tramos - alternar colores
          $tramoNumber = filter_var($row['tipo'], FILTER_SANITIZE_NUMBER_INT);
          if ($tramoNumber % 2 == 0) {
            $sheet->getStyle($columnLetter . $rowIndex)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFF9F9F9');
          }
          // Resaltar valores completados
          if (
            ($key === 'estado_tramo' && $value === 'completed') ||
            ($key === 'total_km' && is_numeric($value) && $value > 0)
          ) {
            $sheet->getStyle($columnLetter . $rowIndex)->getFont()
              ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN));
            $sheet->getStyle($columnLetter . $rowIndex)->getFont()->setBold(true);
          }
        }

        $sheet->setCellValue($columnLetter . $rowIndex, $value);
        $colIndex++;
      }
      $rowIndex++;
    }

    // Aplicar bordes a toda la tabla
    $lastRow = $rowIndex - 1;
    $lastColumn = $this->getColumnLetter(count($columns));
    $tableRange = 'A4:' . $lastColumn . $lastRow;
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN)
      ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK));

    // Autoajustar anchos de columna
    foreach (range(1, count($columns)) as $col) {
      $sheet->getColumnDimension($this->getColumnLetter($col))->setAutoSize(true);
    }

    // Congelar el panel en la fila 4 (encabezados)
    $sheet->freezePane('A5');

    // Configurar la respuesta para descarga
    $writer = new Xlsx($spreadsheet);

    // Guardar en un buffer temporal
    ob_start();
    $writer->save('php://output');
    $content = ob_get_contents();
    ob_end_clean();

    return response($content, 200, [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
      'Cache-Control' => 'max-age=0',
    ]);
  }

  private function getColumnLetter($index)
  {
    $letter = '';
    while ($index > 0) {
      $index--;
      $letter = chr($index % 26 + 65) . $letter;
      $index = intval($index / 26);
    }
    return $letter;
  }

  private function generatePdf($data, $columns, $travel, $filename)
  {
    // Separar datos en dos grupos
    $generalInfo = [];
    $tramos = [];
    $isGeneralInfo = true;

    foreach ($data as $row) {
      if ($row['tipo'] === 'INFORMACIÓN GENERAL') {
        $generalInfo = $row;
        $isGeneralInfo = false;
      } elseif (strpos($row['tipo'], 'DETALLE') !== false) {
        continue; // Saltar separador
      } elseif (strpos($row['tipo'], 'TRAMO') !== false) {
        $tramos[] = $row;
      }
    }

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Viaje ' . $travel->trip_number . '</title>
        <style>
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                margin: 15px;
                font-size: 9pt;
            }
            h1 {
                color: #2C3E50;
                text-align: center;
                font-size: 16pt;
                margin-bottom: 10px;
            }
            .header-info {
                background-color: #ECF0F1;
                padding: 8px;
                border-radius: 5px;
                margin-bottom: 15px;
            }
            .header-info p {
                margin: 4px 0;
                font-size: 8pt;
            }
            h2 {
                color: #2C3E50;
                font-size: 12pt;
                margin-top: 15px;
                margin-bottom: 8px;
                border-bottom: 1px solid #3498DB;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
                font-size: 8pt;
            }
            th {
                background-color: #2C3E50;
                color: white;
                padding: 5px;
                text-align: center;
                font-weight: bold;
            }
            td {
                border: 1px solid #ddd;
                padding: 4px;
            }
            .info-label {
                background-color: #34495E;
                color: white;
                font-weight: bold;
                width: 25%;
            }
            .info-value {
                background-color: #ECF0F1;
                width: 25%;
            }
            .completed {
                color: #27AE60;
                font-weight: bold;
            }
            .footer {
                margin-top: 20px;
                text-align: center;
                font-size: 7pt;
                color: #7F8C8D;
            }
        </style>
    </head>
    <body>';

    $html .= '<h1>REPORTE DE VIAJE</h1>';

    // Información general en formato de tabla 2xN
    $html .= '<table cellpadding="4" cellspacing="0" style="width: 100%; margin-bottom: 15px;">';
    $html .= '<tr>';
    $html .= '<td class="info-label">N° Viaje</td><td class="info-value">' . ($generalInfo['nro_viaje'] ?? 'N/A') . '</td>';
    $html .= '<td class="info-label">Placa</td><td class="info-value">' . ($generalInfo['placa'] ?? 'N/A') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="info-label">Conductor</td><td class="info-value">' . ($generalInfo['conductor'] ?? 'N/A') . '</td>';
    $html .= '<td class="info-label">Cliente</td><td class="info-value">' . ($generalInfo['cliente'] ?? 'N/A') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="info-label">Ruta</td><td colspan="3">' . ($generalInfo['ruta'] ?? 'N/A') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="info-label">Estado</td><td class="info-value">' . ($generalInfo['estado'] ?? 'N/A') . '</td>';
    $html .= '<td class="info-label">Fecha Generación</td><td class="info-value">' . now()->format('d/m/Y H:i:s') . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    // Tabla de métricas
    $html .= '<h2>Métricas del Viaje</h2>';
    $html .= '<table cellpadding="4" cellspacing="0" style="width: 60%;">';
    $html .= '<tr><th>Métrica</th><th>Valor</th></tr>';
    $html .= '<tr><td>Km Inicial</td><td>' . ($generalInfo['km_inicial'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td>Km Final</td><td>' . ($generalInfo['km_final'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td>Total Km</td><td>' . ($generalInfo['total_km'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td>Total Horas</td><td>' . ($generalInfo['total_horas'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td>Toneladas</td><td>' . ($generalInfo['toneladas'] ?? 'N/A') . '</td></tr>';
    $html .= '</table>';

    $html .= '<h2>Detalle de Tramos</h2>';
    $html .= '<table cellpadding="4" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>#</th>';
    $html .= '<th>Origen</th>';
    $html .= '<th>Destino</th>';
    $html .= '<th>Producto</th>';
    $html .= '<th>Cantidad</th>';
    $html .= '<th>Km Viaje</th>';
    $html .= '<th>Km Inicial</th>';
    $html .= '<th>Km Final</th>';
    $html .= '<th>Total Km</th>';
    $html .= '<th>Estado</th>';
    $html .= '</tr>';
    $html .= '</thead><tbody>';

    foreach ($tramos as $index => $tramo) {
      $tramoNumber = filter_var($tramo['tipo'], FILTER_SANITIZE_NUMBER_INT);
      $html .= '<tr>';
      $html .= '<td align="center">' . $tramoNumber . '</td>';
      $html .= '<td>' . ($tramo['origen'] ?? 'N/A') . '</td>';
      $html .= '<td>' . ($tramo['destino'] ?? 'N/A') . '</td>';
      $html .= '<td>' . ($tramo['producto'] ?? 'N/A') . '</td>';
      $html .= '<td align="right">' . ($tramo['cantidad'] ?? 'N/A') . '</td>';
      $html .= '<td align="right">' . ($tramo['km_viaje'] ?? 'N/A') . '</td>';
      $html .= '<td align="right">' . ($tramo['km_inicial'] ?? 'N/A') . '</td>';
      $html .= '<td align="right">' . ($tramo['km_final'] ?? 'N/A') . '</td>';
      $html .= '<td align="right" class="completed">' . ($tramo['total_km'] ?? 'N/A') . '</td>';
      $html .= '<td>' . ($tramo['estado_tramo'] ?? 'N/A') . '</td>';
      $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '<div class="footer">Reporte generado automáticamente por el sistema de Control de Viajes</div>';
    $html .= '</body></html>';

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    $pdf->setPaper('a4', 'portrait');

    return $pdf->download($filename . '.pdf');
  }

  // public function exportAllReports(Request $request)
// {
//     try {
//         // Obtener filtros del request (opcional)
//         $filters = $request->only(['status', 'date_from', 'date_to', 'search']);
//         Log::info('=== exportAllReports called ===');
//         Log::info('Headers: ' . json_encode($request->headers->all()));
//         Log::info('Body: ' . json_encode($request->all()));
//         $format = $request->get('format', 'excel');

  //         // Crear request simulado para el servicio de exportación
//         $exportRequest = new Request();
//         $exportRequest->merge([
//             'format' => $format,
//             'title' => 'Reporte_General_Viajes',
//         ]);

  //         // Agregar filtros como parámetros de consulta
//         foreach ($filters as $key => $value) {
//             if ($value) {
//                 $exportRequest->merge([$key => $value]);
//             }
//         }

  //         $exportService = new ExportService();

  //         return $exportService->exportFromRequest($exportRequest, TravelControl::class);

  //     } catch (Throwable $th) {
//         \Log::error('Error en exportAllReports: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
//         return $this->error($th->getMessage());
//     }
// }



  /**
   * Export all travels report to Excel or PDF (versión simplificada)
   */
  public function exportAllReports(Request $request)
  {
    try {
      $format = $request->get('format', 'excel');
      $status = $request->get('status');
      $search = $request->get('search');

      $reportData = [];
      $chunkSize = 50;

      // Obtener todos los viajes con sus relaciones
      $query = TravelControl::with([
        'driver',
        'tract',
        'customer',
        'items.origin',
        'items.destination',
        'items.product',
      ]);

      // Aplicar filtros
      if ($status && $status !== 'all') {
        $dbStatuses = DispatchStatus::fromTripStatus($status);
        Log::info('Status mapping - Input: ' . $status . ', DB Statuses: ' . json_encode($dbStatuses));
        if (!empty($dbStatuses)) {
          $query->whereIn('estado', $dbStatuses);
        }
      }

      if ($search) {
        $query->where(function ($q) use ($search) {
          $q->where('id', 'LIKE', "%{$search}%")
            ->orWhere('trip_number', 'LIKE', "%{$search}%")
            ->orWhereHas('driver', function ($q2) use ($search) {
              $q2->where('nombre_completo', 'LIKE', "%{$search}%");
            })
            ->orWhereHas('tract', function ($q3) use ($search) {
              $q3->where('placa', 'LIKE', "%{$search}%");
            })
            ->orWhereHas('customer', function ($q4) use ($search) {
              $q4->where('nombre_completo', 'LIKE', "%{$search}%");
            });
        });
      }

      $total = $query->count();
      \Log::info("Total de viajes a exportar: {$total}");
      if ($total > 500) {
        \Log::warning("Demasiados viajes para exportar: {$total}. Limitando a 500.");
        $query->limit(500);
      }

      $travels = $query->orderBy('id', 'desc')->get();

      // Preparar datos para el reporte
      $reportData = [];

      foreach ($travels as $travel) {

        $items = DispatchItem::where('despacho_id', $travel->id)
          ->with(['origin', 'destination', 'product'])
          ->orderBy('id', 'asc')
          ->get();

        $reportData[] = [
          'tipo' => 'INFORMACIÓN GENERAL',
          'nro_viaje' => $travel->trip_number,
          'placa' => $travel->tract->placa ?? 'N/A',
          'conductor' => $travel->driver->nombre_completo ?? 'N/A',
          'ruta' => $travel->getRouteFromItemsAttribute(),
          'cliente' => $travel->customer->nombre_completo ?? 'N/A',
          'estado' => $travel->state_spanish ?? $travel->mapped_status,
          'km_inicial' => $travel->km_inicio ?? 'N/A',
          'km_final' => $travel->km_fin ?? 'N/A',
          'total_km' => $travel->total_km ?? 'N/A',
          'total_horas' => $travel->total_hours ? number_format($travel->total_hours, 2) : 'N/A',
          'toneladas' => $travel->tonnage ?? 'N/A',
          'fecha_inicio' => $travel->fecha_viaje?->format('d/m/Y H:i') ?? 'N/A',
          'fecha_fin' => $travel->km_fin ? $travel->updated_at?->format('d/m/Y H:i') : 'N/A',
          'origen' => '',
          'destino' => '',
          'producto' => '',
          'cantidad' => '',
          'km_viaje' => '',
          'estado_tramo' => '',
          'inicio_real' => '',
          'fin_real' => '',
        ];

        // Agregar tramos del viaje
        foreach ($items as $index => $item) {
          $reportData[] = [
            'tipo' => "TRAMO " . ($index + 1),
            'nro_viaje' => '',
            'placa' => '',
            'conductor' => '',
            'ruta' => '',
            'cliente' => '',
            'estado' => '',
            'km_inicial' => $item->initial_mileage ?? 'N/A',
            'km_final' => $item->final_mileage ?? 'N/A',
            'total_km' => $item->total_mileage ?? 'N/A',
            'total_horas' => $item->total_hours ? number_format($item->total_hours, 2) : 'N/A',
            'toneladas' => '',
            'fecha_inicio' => '',
            'fecha_fin' => '',
            'origen' => $item->origin->descripcion ?? 'N/A',
            'destino' => $item->destination->descripcion ?? 'N/A',
            'producto' => $item->product->descripcion ?? 'N/A',
            'cantidad' => $item->cantidad ?? 'N/A',
            'km_viaje' => $item->km_viaje ?? 'N/A',
            'estado_tramo' => $item->state_spanish_tramo ?? $item->segment_status,
            'inicio_real' => $item->actual_start?->format('d/m/Y H:i') ?? 'N/A',
            'fin_real' => $item->actual_end?->format('d/m/Y H:i') ?? 'N/A',
          ];
        }

        // Agregar línea separadora entre viajes
        $reportData[] = [
          'tipo' => '--- SEPARADOR ---',
          'nro_viaje' => '',
          'placa' => '',
          'conductor' => '',
          'ruta' => '',
          'cliente' => '',
          'estado' => '',
          'km_inicial' => '',
          'km_final' => '',
          'total_km' => '',
          'total_horas' => '',
          'toneladas' => '',
          'fecha_inicio' => '',
          'fecha_fin' => '',
          'origen' => '',
          'destino' => '',
          'producto' => '',
          'cantidad' => '',
          'km_viaje' => '',
          'estado_tramo' => '',
          'inicio_real' => '',
          'fin_real' => '',
        ];
      }

      $columns = [
        'tipo' => 'Tipo',
        'nro_viaje' => 'N° Viaje',
        'placa' => 'Placa',
        'conductor' => 'Conductor',
        'ruta' => 'Ruta',
        'cliente' => 'Cliente',
        'estado' => 'Estado',
        'km_inicial' => 'Km Inicial',
        'km_final' => 'Km Final',
        'total_km' => 'Total Km',
        'total_horas' => 'Total Horas',
        'toneladas' => 'Toneladas',
        'fecha_inicio' => 'Fecha Inicio',
        'fecha_fin' => 'Fecha Fin',
        'origen' => 'Origen',
        'destino' => 'Destino',
        'producto' => 'Producto',
        'cantidad' => 'Cantidad',
        'km_viaje' => 'Km Viaje',
        'estado_tramo' => 'Estado Tramo',
        'inicio_real' => 'Inicio Real',
        'fin_real' => 'Fin Real',
      ];

      $filename = "reporte_viajes_" . now()->format('Y-m-d_H-i-s');

      if ($format === 'pdf') {
        return $this->generatePdfReport($reportData, $columns, $filename);
      } else {
        return $this->generateExcelReport($reportData, $columns, $filename);
      }

    } catch (Throwable $th) {
      \Log::error('Error en exportAllReports: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
      return $this->error($th->getMessage());
    }
  }

  private function generatePdfGeneral($data, $columns, $filename)
  {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte General de Viajes</title>
        <style>
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                margin: 10px;
                font-size: 8pt;
            }
            h1 {
                color: #2C3E50;
                text-align: center;
                font-size: 14pt;
                margin-bottom: 10px;
            }
            .header-info {
                margin-bottom: 15px;
                text-align: center;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 7pt;
            }
            th {
                background-color: #2C3E50;
                color: white;
                padding: 4px;
                text-align: center;
                font-weight: bold;
            }
            td {
                border: 1px solid #ddd;
                padding: 3px;
            }
            .info-row {
                background-color: #E8F0FE;
                font-weight: bold;
            }
            .footer {
                margin-top: 20px;
                text-align: center;
                font-size: 6pt;
                color: #7F8C8D;
            }
        </style>
    </head>
    <body>';

    $html .= '<h1>REPORTE GENERAL DE VIAJES</h1>';
    $html .= '<div class="header-info">';
    $html .= '<p><strong>Fecha Generación:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>';
    $html .= '</div>';

    $html .= '<table border="1" cellpadding="4" cellspacing="0">';

    // Encabezados
    $html .= '<thead><tr>';
    foreach ($columns as $key => $label) {
      $html .= '<th>' . $label . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    // Datos
    foreach ($data as $row) {
      if ($row['tipo'] === '--- SEPARADOR ---') {
        continue;
      }

      $rowClass = '';
      if ($row['tipo'] === 'INFORMACIÓN GENERAL') {
        $rowClass = 'info-row';
      }

      $html .= '<tr class="' . $rowClass . '">';
      foreach (array_keys($columns) as $key) {
        $value = $row[$key] ?? '';
        $html .= '<td>' . $value . '</td>';
      }
      $html .= '</tr>';
    }

    $html .= '</tbody></td>';
    $html .= '<div class="footer">Reporte generado automáticamente por el sistema de Control de Viajes</div>';
    $html .= '</body></html>';

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    $pdf->setPaper('a4', 'landscape');

    return $pdf->download($filename . '.pdf');
  }
  private function generateExcelReport($data, $columns, $filename)
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Título
    $sheet->setCellValue('A1', 'REPORTE GENERAL DE VIAJES');
    $sheet->mergeCells('A1:' . $this->getColumnLetter(count($columns)) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Fecha
    $sheet->setCellValue('A2', 'Fecha Generación: ' . now()->format('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:' . $this->getColumnLetter(count($columns)) . '2');

    // Encabezados
    $colIndex = 1;
    foreach ($columns as $key => $label) {
      $columnLetter = $this->getColumnLetter($colIndex);
      $sheet->setCellValue($columnLetter . '4', $label);
      $sheet->getStyle($columnLetter . '4')->getFont()->setBold(true);
      $sheet->getStyle($columnLetter . '4')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
      $sheet->getStyle($columnLetter . '4')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF2C3E50');
      $colIndex++;
    }

    // Datos
    $rowIndex = 5;
    foreach ($data as $row) {
      if ($row['tipo'] === '--- SEPARADOR ---') {
        $rowIndex++;
        continue;
      }

      $colIndex = 1;
      foreach ($columns as $key => $label) {
        $columnLetter = $this->getColumnLetter($colIndex);
        $value = $row[$key] ?? '';

        if ($row['tipo'] === 'INFORMACIÓN GENERAL') {
          $sheet->getStyle($columnLetter . $rowIndex)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F0FE');
          $sheet->getStyle($columnLetter . $rowIndex)->getFont()->setBold(true);
        } elseif (strpos($row['tipo'], 'TRAMO') !== false) {
          if (
            ($key === 'estado_tramo' && $value === 'completed') ||
            ($key === 'total_km' && is_numeric($value) && $value > 0)
          ) {
            $sheet->getStyle($columnLetter . $rowIndex)->getFont()
              ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN));
          }
        }

        $sheet->setCellValue($columnLetter . $rowIndex, $value);
        $colIndex++;
      }
      $rowIndex++;
    }

    // Autoajustar
    foreach (range(1, count($columns)) as $col) {
      $sheet->getColumnDimension($this->getColumnLetter($col))->setAutoSize(true);
    }

    $sheet->freezePane('A5');

    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $content = ob_get_contents();
    ob_end_clean();

    return response($content, 200, [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
      'Cache-Control' => 'max-age=0',
    ]);
  }

  public function updateMileage(Request $request, $id)
  {
      try {

          $travel = TravelControl::with([
              'items' => function ($q) {
                  $q->orderBy('id', 'asc');
              }
          ])->findOrFail($id);

          //verificar que el viaje se encuentre en estado que permita edicion
          $allowedStates = [
              DispatchStatus::STATUS_FUEL_PENDING,
              DispatchStatus::STATUS_COMPLETED,
          ];

          if (!in_array($travel->estado, $allowedStates)) {
              throw new Exception('Solo se pueden editar kilometrajes de viajes en estado Combustible Pendiente o Completado');
          }

          $validated = $request->validate([
              'general_initial_km' => 'nullable|numeric|min:0',
              'general_final_km' => 'nullable|numeric|min:0',
              'segments' => 'nullable|array',
              'segments.*.id' => 'required|exists:op_despacho_item,id',
              'segments.*.initial_mileage' => 'nullable|numeric|min:0',
              'segments.*.final_mileage' => 'nullable|numeric|min:0',
          ]);

          

          DB::beginTransaction();

          $finalGeneralInitial = $request->has('general_initial_km') 
              ? $request->general_initial_km 
              : $travel->km_inicio;
          
          $finalGeneralFinal = $request->has('general_final_km') 
              ? $request->general_final_km 
              : $travel->km_fin;

          $segmentsData = [];
          if ($request->has('segments')) {
              foreach ($request->segments as $segmentData) {
                  $segment = DispatchItem::find($segmentData['id']);
                  if ($segment && $segment->despacho_id == $travel->id) {
                      $segmentsData[$segment->id] = [
                          'original' => $segment,
                          'new_initial' => $segmentData['initial_mileage'] ?? $segment->initial_mileage,
                          'new_final' => $segmentData['final_mileage'] ?? $segment->final_mileage,
                      ];
                  }
              }
          }

          // 1. Validar que el km inicial del PRIMER tramo sea >= km inicial general
          $firstSegment = $travel->items->first();
          if ($firstSegment) {
              $firstSegmentInitial = $segmentsData[$firstSegment->id]['new_initial'] ?? $firstSegment->initial_mileage;
              
              if ($firstSegmentInitial !== null && $finalGeneralInitial !== null) {
                  if ($firstSegmentInitial < $finalGeneralInitial) {
                      throw new Exception(
                          "El kilometraje inicial del primer tramo ({$firstSegmentInitial} km) " .
                          "no puede ser menor al kilometraje inicial general ({$finalGeneralInitial} km)"
                      );
                  }
              }
          }

          // 2. Validar que el km final del ÚLTIMO tramo sea <= km final general
          $lastSegment = $travel->items->last();
          if ($lastSegment) {
              $lastSegmentFinal = $segmentsData[$lastSegment->id]['new_final'] ?? $lastSegment->final_mileage;
              
              if ($lastSegmentFinal !== null && $finalGeneralFinal !== null) {
                  if ($lastSegmentFinal > $finalGeneralFinal) {
                      throw new Exception(
                          "El kilometraje final del último tramo ({$lastSegmentFinal} km) " .
                          "no puede ser mayor al kilometraje final general ({$finalGeneralFinal} km)"
                      );
                  }
              }
          }
          // 3. Validar que los tramos sean contiguos (km final de un tramo <= km inicial del siguiente)
          $sortedSegments = $travel->items->sortBy('id')->values();
          for ($i = 0; $i < $sortedSegments->count() - 1; $i++) {
              $current = $sortedSegments[$i];
              $next = $sortedSegments[$i + 1];
              
              $currentFinal = $segmentsData[$current->id]['new_final'] ?? $current->final_mileage;
              $nextInitial = $segmentsData[$next->id]['new_initial'] ?? $next->initial_mileage;
              
              if ($currentFinal !== null && $nextInitial !== null) {
                  if ($currentFinal > $nextInitial) {
                      throw new Exception(
                          "Inconsistencia entre tramos: El km final del tramo '{$current->getSegmentNameAttribute()}' " .
                          "({$currentFinal} km) no puede ser mayor al km inicial del siguiente tramo " .
                          "'{$next->getSegmentNameAttribute()}' ({$nextInitial} km)"
                      );
                  }
              }
          }

          // 4. Validar que el km inicial general no sea mayor al km final general (ya existe)
          if ($finalGeneralInitial !== null && $finalGeneralFinal !== null) {
              if ($finalGeneralFinal <= $finalGeneralInitial) {
                  throw new Exception('El kilometraje final general debe ser mayor al inicial');
              }
          }
          
          // 5. Validar que el primer tramo inicial no sea mayor al último tramo final
          if ($firstSegment && $lastSegment) {
              $firstInitial = $segmentsData[$firstSegment->id]['new_initial'] ?? $firstSegment->initial_mileage;
              $lastFinal = $segmentsData[$lastSegment->id]['new_final'] ?? $lastSegment->final_mileage;
              
              if ($firstInitial !== null && $lastFinal !== null && $firstInitial > $lastFinal) {
                  throw new Exception(
                      "El kilometraje inicial del primer tramo ({$firstInitial} km) " .
                      "no puede ser mayor al kilometraje final del último tramo ({$lastFinal} km)"
                  );
              }
          }

          //actualizar kilometrajes generales
          if ($request->has('general_initial_km')) {
              $newInitialKm = $request->general_initial_km;
              $travel->km_inicio = $newInitialKm;
              $changesDetected = true;
          }

          if ($request->has('general_final_km')) {
              $newFinalKm = $request->general_final_km;
              $travel->km_fin = $newFinalKm;
              $changesDetected = true;
          }

          $travel->save();

          $segmentUpdatesCount = 0;

          // Actualizar segmentos
          if ($request->has('segments')) {
              foreach ($request->segments as $segmentData) {
                  $segment = DispatchItem::find($segmentData['id']);
                  if (!$segment || $segment->despacho_id != $travel->id) {
                      continue;
                  }

                  $updateData = [];

                  if (array_key_exists('initial_mileage', $segmentData)) {
                      $updateData['initial_mileage'] = $segmentData['initial_mileage'];
                  }

                  if (array_key_exists('final_mileage', $segmentData)) {
                      $updateData['final_mileage'] = $segmentData['final_mileage'];
                  }

                  // Recalcular total_mileage
                  $initial = $updateData['initial_mileage'] ?? $segment->initial_mileage;
                  $final = $updateData['final_mileage'] ?? $segment->final_mileage;

                  if ($initial !== null && $final !== null) {
                      $updateData['total_mileage'] = $final - $initial;
                  }

                  // Recalcular total_hours
                  if ($segment->actual_start && $segment->actual_end) {
                      $updateData['total_hours'] = $segment->actual_end->diffInHours($segment->actual_start, true);
                  }

                  if (!empty($updateData)) {
                      $segment->update($updateData);
                  }
              }
          }

          DB::commit();

          // recargar el viaje con datos actualizados
          $travel->refresh();
          $travel->load(['items' => function($q){
              $q->orderBy('id', 'asc');
          }]);

          return response()->json([
              'success' => true,
              'message' => 'Kilometrajes actualizados correctamente',
              'data' => new TravelControlResource($travel)
          ]);

      } catch (Exception $e) {
          DB::rollBack();
          return response()->json([
              'success' => false,
              'message' => $e->getMessage()
          ], 500);
      }
  }



public function exportSummaryReport(Request $request)
{
    try {
        $format = $request->get('format', 'excel');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Obtener IDs de viajes que tienen gasto de combustible (concepto_id = 25)
        $travelIdsWithFuel = TravelExpense::where('concepto_id', 25)
            ->where('status_deleted', 1)
            ->pluck('viaje_id')
            ->unique()
            ->toArray();

        if (empty($travelIdsWithFuel)) {
            // Si no hay viajes con combustible, devolver reporte vacío
            $emptyData = [];
            $columns = $this->getSummaryColumns();
            $filename = "reporte_resumido_viajes_" . now()->format('Y-m-d_H-i-s');
            
            if ($format === 'pdf') {
                return $this->generateEmptySummaryPdf($columns, $filename);
            } else {
                return $this->generateEmptySummaryExcel($columns, $filename);
            }
        }

        // Obtener viajes COMPLETADOS (estado 9) que tienen registro de combustible
        $query = TravelControl::with([
            'driver:id,nombre_completo',
            'tract:id,placa',
            'customer:id,nombre_completo',
            'items.origin',
            'items.destination',
            'expenses' => function($q) {
                $q->where('concepto_id', 25); // Solo combustible
            }
        ])
        ->where('estado', DispatchStatus::STATUS_COMPLETED) // Solo completados (9)
        ->whereIn('id', $travelIdsWithFuel); // Solo los que tienen combustible registrado

        // Aplicar filtro de búsqueda
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('trip_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('driver', function($q2) use ($search) {
                      $q2->where('nombre_completo', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('tract', function($q3) use ($search) {
                      $q3->where('placa', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('customer', function($q4) use ($search) {
                      $q4->where('nombre_completo', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Aplicar filtro de fechas (por fecha de viaje)
        if ($dateFrom) {
            $query->whereDate('fecha_viaje', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('fecha_viaje', '<=', $dateTo);
        }

        $travels = $query->orderBy('fecha_viaje', 'desc')->get();

        // Preparar datos para el reporte resumido
        $reportData = [];
        $totalKm = 0;
        $totalHoras = 0;
        $totalToneladas = 0;
        $totalSoles = 0;
        $totalProduccion = 0;
        
        foreach ($travels as $travel) {
            // Obtener el monto de combustible
            $fuelAmount = 0;
            if ($travel->expenses->isNotEmpty()) {
                $fuelAmount = (float) $travel->expenses->first()->monto;
            }
            
            $kmTotal = $travel->total_km ?? 0;
            $horasTotales = $travel->total_hours ?? 0;
            $toneladas = $travel->tonnage ?? 0;
            $produccion = is_numeric($travel->produccion) ? (float) $travel->produccion : 0;
            
            $totalKm += $kmTotal;
            $totalHoras += $horasTotales;
            $totalToneladas += $toneladas;
            $totalSoles += $fuelAmount;
            $totalProduccion += $produccion;
            
            $reportData[] = [
                'nro_viaje' => $travel->trip_number,
                'placa' => $travel->tract->placa ?? 'N/A',
                'conductor' => $travel->driver->nombre_completo ?? 'N/A',
                'ruta' => $this->getTravelRoute($travel),
                'cliente' => $travel->customer->nombre_completo ?? 'N/A',
                'estado' => 'Completado',
                'km_total' => $kmTotal,
                'horas_totales' => $horasTotales,
                'toneladas' => $toneladas,
                'soles_combustible' => $fuelAmount,
                'produccion' => $travel->produccion

            ];
        }

        $columns = $this->getSummaryColumns();
        
        // Agregar resumen de totales
        $summary = [
            'total_viajes' => count($reportData),
            'total_km' => $totalKm,
            'total_horas' => $totalHoras,
            'total_toneladas' => $totalToneladas,
            'total_soles' => $totalSoles,
            'total_produccion' => $totalProduccion,
        ];

        $filename = "reporte_resumido_viajes_completados_" . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            return $this->generateSummaryPdf($reportData, $columns, $summary, $filename);
        } else {
            return $this->generateSummaryExcel($reportData, $columns, $summary, $filename);
        }

    } catch (Throwable $th) {
        Log::error('Error en exportSummaryReport: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
        return $this->error($th->getMessage());
    }
}

/**
 * Get route string from travel items
 */
private function getTravelRoute($travel): string
{
    if (!$travel->relationLoaded('items') || $travel->items->isEmpty()) {
        return 'Sin ruta';
    }
    
    $firstItem = $travel->items->first();
    $lastItem = $travel->items->last();
    
    $origin = $firstItem->origin->descripcion ?? 'Sin origen';
    $destination = $lastItem->destination->descripcion ?? 'Sin destino';
    
    return $origin . ' - ' . $destination;
}

/**
 * Get summary report columns
 */
private function getSummaryColumns(): array
{
    return [
        'nro_viaje' => 'N° Viaje',
        'placa' => 'Placa',
        'conductor' => 'Conductor',
        'ruta' => 'Ruta',
        'cliente' => 'Cliente',
        'estado' => 'Estado',
        'km_total' => 'Km Total (km)',
        'horas_totales' => 'Horas Totales (Horas)',
        'toneladas' => 'Toneladas (Ton)',
        'soles_combustible' => 'Combustible (Soles)',
        'produccion' => 'Produccion'
    ];
}

/**
 * Generate summary Excel report
 */
private function generateSummaryExcel($data, $columns, $summary, $filename)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Título
    $sheet->setCellValue('A1', 'REPORTE RESUMIDO DE VIAJES COMPLETADOS');
    $sheet->mergeCells('A1:' . $this->getColumnLetter(count($columns)) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Fecha de generación
    $sheet->setCellValue('A2', 'Fecha Generación: ' . now()->format('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:' . $this->getColumnLetter(count($columns)) . '2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    // Totales en fila 3
    $sheet->setCellValue('A3', 'Total Viajes: ' . $summary['total_viajes']);
    $sheet->setCellValue('B3', 'Total Km: ' . number_format($summary['total_km'], 0));
    $sheet->setCellValue('C3', 'Total Horas: ' . number_format($summary['total_horas'], 2));
    $sheet->setCellValue('D3', 'Total Toneladas: ' . number_format($summary['total_toneladas'], 2));
    $sheet->setCellValue('E3', 'Total Soles: S/ ' . number_format($summary['total_soles'], 2));
    $sheet->setCellValue('F3', 'Total Produccion: S/ ' . number_format($summary['total_produccion'], 2));
    
    $sheet->getStyle('A3:F3')->getFont()->setBold(true);
    $sheet->getStyle('A3:F3')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFE8F0FE');

    // Encabezados de columna (fila 5)
    $colIndex = 1;
    foreach ($columns as $key => $label) {
        $columnLetter = $this->getColumnLetter($colIndex);
        $sheet->setCellValue($columnLetter . '5', $label);
        $sheet->getStyle($columnLetter . '5')->getFont()->setBold(true);
        $sheet->getStyle($columnLetter . '5')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle($columnLetter . '5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2C3E50');
        $sheet->getStyle($columnLetter . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $colIndex++;
    }

    // Datos (desde fila 6)
    $rowIndex = 6;
    foreach ($data as $row) {
        $colIndex = 1;
        foreach ($columns as $key => $label) {
            $columnLetter = $this->getColumnLetter($colIndex);
            $value = $row[$key] ?? '';
            $numericColumns = ['km_total', 'horas_totales', 'toneladas',
            'soles_combustible', 'produccion'];

            if(in_array($key, $numericColumns) && is_numeric($value)){
              $sheet->setCellValue($columnLetter . $rowIndex, (float) $value);
                  if ($key === 'km_total') {
                    $sheet->getStyle($columnLetter . $rowIndex)->getNumberFormat()
                    ->setFormatCode('#,##0');
                  } elseif ($key === 'horas_totales') {
                    $sheet->getStyle($columnLetter . $rowIndex)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                  } elseif ($key === 'toneladas') {
                    $sheet->getStyle($columnLetter . $rowIndex)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                  } elseif ($key === 'soles_combustible') {
                    $sheet->getStyle($columnLetter . $rowIndex)->getNumberFormat()
                    ->setFormatCode('"S/ "#,##0.00');
                  } elseif ($key === 'produccion') {
                    $sheet->getStyle($columnLetter . $rowIndex)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                  }
            }else{
              $sheet->setCellValue($columnLetter . $rowIndex, $value);
            }
            $colIndex++;
        }
        $rowIndex++;
    }

    // Aplicar bordes a toda la tabla
    $lastRow = $rowIndex - 1;
    $lastColumn = $this->getColumnLetter(count($columns));
    $tableRange = 'A5:' . $lastColumn . $lastRow;
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)
        ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK));

    foreach (['km_total', 'horas_totales', 'toneladas', 'soles_combustible', 'produccion'] as $numCol) {
        if (isset($columns[$numCol])) {
            $colIndex = array_search($numCol, array_keys($columns)) + 1;
            $columnLetter = $this->getColumnLetter($colIndex);
            $sheet->getStyle($columnLetter . '6:' . $columnLetter . $lastRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }
    
    // Autoajustar anchos de columna
    foreach (range(1, count($columns)) as $col) {
        $sheet->getColumnDimension($this->getColumnLetter($col))->setAutoSize(true);
    }

    // Congelar el panel en la fila 5
    $sheet->freezePane('A6');

    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $content = ob_get_contents();
    ob_end_clean();

    return response($content, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
        'Cache-Control' => 'max-age=0',
    ]);
}

/**
 * Generate summary PDF report
 */
private function generateSummaryPdf($data, $columns, $summary, $filename)
{
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Resumido de Viajes Completados</title>
        <style>
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                margin: 15px;
                font-size: 9pt;
            }
            h1 {
                color: #2C3E50;
                text-align: center;
                font-size: 16pt;
                margin-bottom: 5px;
            }
            .header-info {
                text-align: center;
                margin-bottom: 15px;
                font-size: 8pt;
                color: #7F8C8D;
            }
            .summary-box {
                background-color: #ECF0F1;
                padding: 10px;
                margin-bottom: 15px;
                border-radius: 5px;
            }
            .summary-box table {
                width: 100%;
                font-size: 9pt;
            }
            .summary-box td {
                padding: 4px;
            }
            .summary-label {
                font-weight: bold;
                color: #2C3E50;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 8pt;
            }
            th {
                background-color: #2C3E50;
                color: white;
                padding: 8px;
                text-align: center;
                font-weight: bold;
            }
            td {
                border: 1px solid #ddd;
                padding: 6px;
            }
            .footer {
                margin-top: 20px;
                text-align: center;
                font-size: 7pt;
                color: #7F8C8D;
            }
        </style>
    </head>
    <body>';
    
    $html .= '<h1>REPORTE RESUMIDO DE VIAJES COMPLETADOS</h1>';
    $html .= '<div class="header-info">Fecha Generación: ' . now()->format('d/m/Y H:i:s') . '</div>';
    
    // Cuadro de resumen
    $html .= '<div class="summary-box">';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<td class="summary-label">Total Viajes:</td><td>' . $summary['total_viajes'] . '</td>';
    $html .= '<td class="summary-label">Total Km:</td><td>' . number_format($summary['total_km'], 0) . ' km</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="summary-label">Total Horas:</td><td>' . number_format($summary['total_horas'], 2) . ' h</td>';
    $html .= '<td class="summary-label">Total Toneladas:</td><td>' . number_format($summary['total_toneladas'], 2) . ' ton</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="summary-label">Total Soles:</td><td colspan="3">S/ ' . number_format($summary['total_soles'], 2) . '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Tabla de datos
    $html .= '<table border="1" cellpadding="5" cellspacing="0">';
    $html .= '<thead><tr>';
    foreach ($columns as $key => $label) {
        $html .= '<th>' . $label . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($columns as $key => $label) {
            $value = $row[$key] ?? '';
            
            // Formatear valores numéricos
            if ($key === 'km_total' && is_numeric($value)) {
                $value = number_format($value, 0) . ' km';
            } elseif ($key === 'horas_totales' && is_numeric($value)) {
                $value = number_format($value, 2) . ' h';
            } elseif ($key === 'toneladas' && is_numeric($value)) {
                $value = number_format($value, 2) . ' ton';
            } elseif ($key === 'soles_combustible' && is_numeric($value)) {
                $value = 'S/ ' . number_format($value, 2);
            }
            
            $html .= '<td>' . $value . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '<div class="footer">Reporte generado automáticamente por el sistema de Control de Viajes</div>';
    $html .= '</body></html>';
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    $pdf->setPaper('a4', 'landscape');
    
    return $pdf->download($filename . '.pdf');
}

/**
 * Generate empty summary Excel (when no data)
 */
private function generateEmptySummaryExcel($columns, $filename)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'REPORTE RESUMIDO DE VIAJES COMPLETADOS');
    $sheet->mergeCells('A1:' . $this->getColumnLetter(count($columns)) . '1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Fecha Generación: ' . now()->format('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:' . $this->getColumnLetter(count($columns)) . '2');

    $sheet->setCellValue('A4', 'No hay viajes completados con registro de combustible para los filtros seleccionados.');
    $sheet->mergeCells('A4:' . $this->getColumnLetter(count($columns)) . '4');
    $sheet->getStyle('A4')->getFont()->setItalic(true);
    $sheet->getStyle('A4')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));

    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    $content = ob_get_contents();
    ob_end_clean();

    return response($content, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
        'Cache-Control' => 'max-age=0',
    ]);
}

/**
 * Generate empty summary PDF (when no data)
 */
private function generateEmptySummaryPdf($columns, $filename)
{
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Resumido de Viajes Completados</title>
        <style>
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                margin: 15px;
                font-size: 9pt;
            }
            h1 {
                color: #2C3E50;
                text-align: center;
                font-size: 16pt;
                margin-bottom: 5px;
            }
            .header-info {
                text-align: center;
                margin-bottom: 15px;
                font-size: 8pt;
                color: #7F8C8D;
            }
            .empty-message {
                text-align: center;
                margin-top: 50px;
                font-size: 12pt;
                color: #E74C3C;
            }
            .footer {
                margin-top: 50px;
                text-align: center;
                font-size: 7pt;
                color: #7F8C8D;
            }
        </style>
    </head>
    <body>';
    
    $html .= '<h1>REPORTE RESUMIDO DE VIAJES COMPLETADOS</h1>';
    $html .= '<div class="header-info">Fecha Generación: ' . now()->format('d/m/Y H:i:s') . '</div>';
    $html .= '<div class="empty-message">';
    $html .= '<p>No hay viajes completados con registro de combustible para los filtros seleccionados.</p>';
    $html .= '</div>';
    $html .= '<div class="footer">Reporte generado automáticamente por el sistema de Control de Viajes</div>';
    $html .= '</body></html>';
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    $pdf->setPaper('a4', 'portrait');
    
    return $pdf->download($filename . '.pdf');
}

}

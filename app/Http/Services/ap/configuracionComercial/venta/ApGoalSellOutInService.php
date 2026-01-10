<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApGoalSellOutInResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\venta\ApGoalSellOutIn;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApGoalSellOutInService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApGoalSellOutIn::class,
      $request,
      ApGoalSellOutIn::filters,
      ApGoalSellOutIn::sorts,
      ApGoalSellOutInResource::class,
    );
  }

  public function find($id)
  {
    $ApGoalSellOutIn = ApGoalSellOutIn::where('id', $id)->first();
    if (!$ApGoalSellOutIn) {
      throw new Exception('Registro no encontrado');
    }
    return $ApGoalSellOutIn;
  }

  public function generateReportPDF($year, $month)
  {
    $reportData = $this->getGoalReport($year, $month);

    if (!$reportData['data']['brands'] || empty($reportData['data']['brands'])) {
      throw new Exception('No hay datos para generar el reporte PDF');
    }

    $pdf = PDF::loadView('reports.ap.configuracionComercial.venta.goal-sell-out-in', $reportData);

    // Configurar PDF
    $pdf->setOptions([
      'defaultFont' => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled' => false,
      'dpi' => 96,
    ]);

    return $pdf;
  }

  public function getGoalReport($year, $month)
  {
    $data = DB::table('ap_goal_sell_out_in as goals')
      ->join('ap_masters as shops', 'goals.shop_id', '=', 'shops.id')
      ->join('ap_vehicle_brand as brands', 'goals.brand_id', '=', 'brands.id')
      ->select(
        'shops.description as shop_name',
        'brands.name as brand_name',
        DB::raw('SUM(CASE WHEN goals.type = "IN" THEN goals.goal ELSE 0 END) as sell_in_goal'),
        DB::raw('SUM(CASE WHEN goals.type = "OUT" THEN goals.goal ELSE 0 END) as sell_out_goal')
      )
      ->where('goals.year', $year)
      ->where('goals.month', $month)
      ->whereNull('goals.deleted_at')
      ->groupBy('shops.id', 'shops.description', 'brands.id', 'brands.name')
      ->orderBy('shops.description')
      ->orderBy('brands.name')
      ->get()
      ->groupBy('shop_name');

    return $this->formatReportData($data, $year, $month);
  }

  private function formatReportData($data, $year, $month)
  {
    if ($data->isEmpty()) {
      return [
        'data' => [
          'brands' => [],
          'sell_in' => ['rows' => [], 'totals' => []],
          'sell_out' => ['rows' => [], 'totals' => []]
        ],
        'period' => [
          'year' => $year,
          'month' => $month,
          'month_name' => $this->getMonthName($month)
        ]
      ];
    }

    $brands = $data->flatten()->pluck('brand_name')->unique()->sort()->values();
    $shops = $data->keys();

    $sellInTable = [];
    $sellOutTable = [];

    foreach ($shops as $shop) {
      $sellInRow = ['shop' => $shop];
      $sellOutRow = ['shop' => $shop];
      $sellInTotal = 0;
      $sellOutTotal = 0;

      foreach ($brands as $brand) {
        $goal = $data[$shop]->firstWhere('brand_name', $brand);
        $sellInGoal = $goal->sell_in_goal ?? 0;
        $sellOutGoal = $goal->sell_out_goal ?? 0;

        $sellInRow[$brand] = $sellInGoal;
        $sellOutRow[$brand] = $sellOutGoal;
        $sellInTotal += $sellInGoal;
        $sellOutTotal += $sellOutGoal;
      }

      $sellInRow['total'] = $sellInTotal;
      $sellOutRow['total'] = $sellOutTotal;

      $sellInTable[] = $sellInRow;
      $sellOutTable[] = $sellOutRow;
    }

    // Calcular totales por columna
    $sellInColumnTotals = ['shop' => 'Total'];
    $sellOutColumnTotals = ['shop' => 'Total'];
    $sellInGrandTotal = 0;
    $sellOutGrandTotal = 0;

    foreach ($brands as $brand) {
      $sellInBrandTotal = collect($sellInTable)->sum($brand);
      $sellOutBrandTotal = collect($sellOutTable)->sum($brand);

      $sellInColumnTotals[$brand] = $sellInBrandTotal;
      $sellOutColumnTotals[$brand] = $sellOutBrandTotal;
      $sellInGrandTotal += $sellInBrandTotal;
      $sellOutGrandTotal += $sellOutBrandTotal;
    }

    $sellInColumnTotals['total'] = $sellInGrandTotal;
    $sellOutColumnTotals['total'] = $sellOutGrandTotal;

    return [
      'data' => [
        'brands' => $brands,
        'sell_in' => [
          'rows' => $sellInTable,
          'totals' => $sellInColumnTotals
        ],
        'sell_out' => [
          'rows' => $sellOutTable,
          'totals' => $sellOutColumnTotals
        ]
      ],
      'period' => [
        'year' => $year,
        'month' => $month,
        'month_name' => $this->getMonthName($month)
      ]
    ];
  }

  private function getMonthName($month)
  {
    $months = [
      1 => 'ENERO',
      2 => 'FEBRERO',
      3 => 'MARZO',
      4 => 'ABRIL',
      5 => 'MAYO',
      6 => 'JUNIO',
      7 => 'JULIO',
      8 => 'AGOSTO',
      9 => 'SETIEMBRE',
      10 => 'OCTUBRE',
      11 => 'NOVIEMBRE',
      12 => 'DICIEMBRE'
    ];

    return $months[$month] ?? 'DESCONOCIDO';
  }

  public function store(mixed $data)
  {
    // validamos que no exista un registro con el mismo year, month, type, brand_id y shop_id
    $exists = ApGoalSellOutIn::where('year', $data['year'])
      ->where('month', $data['month'])
      ->where('type', $data['type'])
      ->where('brand_id', $data['brand_id'])
      ->where('shop_id', $data['shop_id'])
      ->first();
    if ($exists) {
      throw new Exception('Ya existe un registro con el mismo año, mes, tipo, marca y tienda');
    }
    $ApGoalSellOutIn = ApGoalSellOutIn::create($data);
    return new ApGoalSellOutInResource($ApGoalSellOutIn);
  }

  public function show($id)
  {
    return new ApGoalSellOutInResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $ApGoalSellOutIn = $this->find($data['id']);
    $ApGoalSellOutIn->update($data);
    return new ApGoalSellOutInResource($ApGoalSellOutIn);
  }

  public function destroy($id)
  {
    $ApGoalSellOutIn = $this->find($id);
    DB::transaction(function () use ($ApGoalSellOutIn) {
      $ApGoalSellOutIn->delete();
    });
    return response()->json(['message' => 'Registro eliminado correctamente']);
  }
}

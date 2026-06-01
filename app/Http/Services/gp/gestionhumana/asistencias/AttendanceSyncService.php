<?php

namespace App\Http\Services\gp\gestionhumana\asistencias;

use App\Exports\GeneralExport;
use App\Http\Resources\gp\gestionhumana\asistencias\AttendanceSyncResource;
use App\Http\Services\BaseService;
use App\Jobs\SyncAttendanceJob;
use App\Models\gp\gestionhumana\asistencias\AttendanceSync;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceSyncService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      AttendanceSync::query()->with('person'),
      $request,
      AttendanceSync::filters,
      AttendanceSync::sorts,
      AttendanceSyncResource::class,
    );
  }

  public function show(int $id): JsonResponse
  {
    $record = AttendanceSync::with('person')->findOrFail($id);

    return response()->json(new AttendanceSyncResource($record));
  }

  public function sync(Request $request): JsonResponse
  {
    $validRanges = ['today', 'yesterday', 'this_week', 'this_month', 'last_month', 'last_3_months', 'last_6_months', 'custom'];

    $request->validate([
      'range'     => ['required', 'string', 'in:' . implode(',', $validRanges)],
      'date_from' => ['required_if:range,custom', 'nullable', 'date_format:Y-m-d'],
      'date_to'   => ['required_if:range,custom', 'nullable', 'date_format:Y-m-d', 'gte:date_from'],
    ]);

    [$from, $to] = $this->resolveRange($request->range, $request->date_from, $request->date_to);

    if ($from->diffInDays($to) > 180) {
      return response()->json(['message' => 'El rango no puede superar 180 días.'], 422);
    }

    if ($request->range === 'today') {
      $date   = $from->toDateString();
      $before = AttendanceSync::whereDate('date', $date)->count();

      SyncAttendanceJob::dispatchSync($date);

      $after = AttendanceSync::whereDate('date', $date)->count();

      return response()->json([
        'message'       => "Sync completado para {$date}.",
        'range'         => 'today',
        'date_from'     => $date,
        'date_to'       => $date,
        'new_records'   => max(0, $after - $before),
        'total_for_day' => $after,
      ]);
    }

    $days = 0;
    for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
      SyncAttendanceJob::dispatch($day->toDateString());
      $days++;
    }

    return response()->json([
      'message'   => "{$days} jobs despachados para {$from->toDateString()} → {$to->toDateString()}.",
      'range'     => $request->range,
      'date_from' => $from->toDateString(),
      'date_to'   => $to->toDateString(),
      'days'      => $days,
    ]);
  }

  private function resolveRange(string $range, ?string $dateFrom, ?string $dateTo): array
  {
    $now = now()->timezone('America/Lima');

    return match ($range) {
      'today'         => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
      'yesterday'     => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
      'this_week'     => [$now->copy()->startOfWeek(), $now->copy()],
      'this_month'    => [$now->copy()->startOfMonth(), $now->copy()],
      'last_month'    => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
      'last_3_months' => [$now->copy()->subMonths(3)->startOfMonth(), $now->copy()],
      'last_6_months' => [$now->copy()->subMonths(6)->startOfMonth(), $now->copy()],
      'custom'        => [Carbon::createFromFormat('Y-m-d', $dateFrom), Carbon::createFromFormat('Y-m-d', $dateTo)],
    };
  }

  public function reportSunafil(Request $request): Response|JsonResponse|BinaryFileResponse
  {
    $request->validate([
      'date_from' => ['required', 'date_format:Y-m-d'],
      'date_to'   => ['required', 'date_format:Y-m-d', 'gte:date_from'],
      'emp_code'  => ['nullable', 'string'],
      'person_id' => ['nullable', 'integer'],
      'search'    => ['nullable', 'string', 'max:100'],
    ]);

    $rows = $this->buildPivotedRows(
      $request->date_from,
      $request->date_to,
      $request->person_id ? (int) $request->person_id : null,
      $request,
    );

    $data = $rows->map(function (object $row) {
      $scheduled = $this->generateScheduledTimes($row);
      $checkIn   = $scheduled['check_in'];
      $checkOut  = $scheduled['check_out'];

      $hoursWorked = ($checkIn && $checkOut)
        ? round((Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 3600, 2)
        : null;

      return [
        'date'         => $row->date,
        'emp_code'     => $row->emp_code,
        'vat'          => $row->vat,
        'full_name'    => $row->full_name,
        'check_in'     => $checkIn,
        'lunch_out'    => $scheduled['lunch_out'],
        'lunch_in'     => $scheduled['lunch_in'],
        'check_out'    => $checkOut,
        'hours_worked' => $hoursWorked,
      ];
    });

    if ($request->get('export') === 'csv') {
      return $this->streamCsv($data, 'sunafil_' . $request->date_from . '_' . $request->date_to . '.csv', [
        'date', 'emp_code', 'vat', 'full_name', 'check_in', 'lunch_out', 'lunch_in', 'check_out', 'hours_worked',
      ]);
    }

    if ($request->get('export') === 'xlsx') {
      $columns  = [
        'date'         => 'Fecha',
        'emp_code'     => 'Código',
        'vat'          => 'DNI',
        'full_name'    => 'Nombre Completo',
        'check_in'     => 'Entrada',
        'lunch_out'    => 'Salida Almuerzo',
        'lunch_in'     => 'Retorno Almuerzo',
        'check_out'    => 'Salida',
        'hours_worked' => 'Horas Trabajadas',
      ];
      $filename = 'sunafil_' . $request->date_from . '_' . $request->date_to . '.xlsx';
      return Excel::download(new GeneralExport($data, $columns, 'SUNAFIL'), $filename);
    }

    return $this->paginateCollection($data, $request);
  }

  public function reportInternal(Request $request): Response|JsonResponse|BinaryFileResponse
  {
    $request->validate([
      'date_from' => ['required', 'date_format:Y-m-d'],
      'date_to'   => ['required', 'date_format:Y-m-d', 'gte:date_from'],
      'person_id' => ['nullable', 'integer'],
      'emp_code'  => ['nullable', 'string'],
      'search'    => ['nullable', 'string', 'max:100'],
    ]);

    $rows    = $this->buildPivotedRows(
      $request->date_from,
      $request->date_to,
      $request->person_id ? (int) $request->person_id : null,
      $request,
    );
    $summary = $this->buildInternalSummary($rows);

    if ($request->get('export') === 'xlsx') {
      $flat = $summary->flatMap(fn($person) => collect($person['daily'])->map(fn($day) => [
        'emp_code'       => $person['emp_code'],
        'full_name'      => $person['full_name'],
        'date'           => $day['date'],
        'check_in'       => $day['check_in'],
        'lunch_out'      => $day['lunch_out'],
        'lunch_in'       => $day['lunch_in'],
        'check_out'      => $day['check_out'],
        'hours_worked'   => $day['hours_worked'],
        'expected_hours' => $day['expected_hours'],
        'balance'        => $day['balance'],
      ]));
      $columns  = [
        'emp_code'       => 'Código',
        'full_name'      => 'Nombre Completo',
        'date'           => 'Fecha',
        'check_in'       => 'Entrada',
        'lunch_out'      => 'Salida Almuerzo',
        'lunch_in'       => 'Retorno Almuerzo',
        'check_out'      => 'Salida',
        'hours_worked'   => 'Horas Trabajadas',
        'expected_hours' => 'Horas Esperadas',
        'balance'        => 'Balance',
      ];
      $filename = 'interno_' . $request->date_from . '_' . $request->date_to . '.xlsx';
      return Excel::download(new GeneralExport($flat, $columns, 'Reporte Interno'), $filename);
    }

    return $this->paginateCollection($summary, $request);
  }

  public function personDashboard(int $personId, Request $request): JsonResponse
  {
    $request->validate([
      'date_from' => ['nullable', 'date_format:Y-m-d'],
      'date_to'   => ['nullable', 'date_format:Y-m-d'],
    ]);

    $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
    $dateTo   = $request->get('date_to', now()->toDateString());

    $rows = $this->buildPivotedRows($dateFrom, $dateTo, $personId, null);

    if ($rows->isEmpty()) {
      return response()->json(['message' => 'No attendance records found for this person in the given range.'], 404);
    }

    $first = $rows->first();

    $daily = $rows->map(function (object $row) {
      $checkIn  = $row->check_in;
      $lunchOut = $row->lunch_out;
      $lunchIn  = $row->lunch_in;
      $checkOut = $row->check_out;

      $dayOfWeek     = Carbon::parse($row->date)->dayOfWeek;
      $isSaturday    = $dayOfWeek === 6;
      $expectedHours = $isSaturday ? 5.0 : 8.6;

      $hoursWorked = null;
      if ($checkIn && $checkOut) {
        $grossMinutes  = (Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 60;
        $lunchMinutes  = $isSaturday ? 0 : 84; // 1h 24min Mon–Fri; no lunch on Saturday
        $hoursWorked   = round(max(0, $grossMinutes - $lunchMinutes) / 60, 2);
      }

      $balance = $hoursWorked !== null ? round($hoursWorked - $expectedHours, 2) : null;

      return [
        'date'           => $row->date,
        'check_in'       => $checkIn,
        'lunch_out'      => $lunchOut,
        'lunch_in'       => $lunchIn,
        'check_out'      => $checkOut,
        '_worked_raw'    => $hoursWorked,
        '_expected_raw'  => $expectedHours,
        '_balance_raw'   => $balance,
        'hours_worked'   => $this->toHm($hoursWorked),
        'expected_hours' => $this->toHm($expectedHours),
        'balance'        => $this->toHm($balance),
      ];
    })->sortBy('date')->values();

    $withData      = $daily->whereNotNull('_worked_raw');
    $totalWorked   = round($withData->sum('_worked_raw'), 2);
    $totalExpected = round($withData->sum('_expected_raw'), 2);

    $daily = $daily->map(fn($d) => array_diff_key($d, array_flip(['_worked_raw', '_expected_raw', '_balance_raw'])));

    $daysPresent = $daily->count();

    return response()->json([
      'person_id'      => $first->person_id,
      'emp_code'       => $first->emp_code,
      'full_name'      => $first->full_name,
      'vat'            => $first->vat,
      'date_from'      => $dateFrom,
      'date_to'        => $dateTo,
      'days_present'   => $daysPresent,
      'expected_hours' => $this->toHm($totalExpected),
      'hours_worked'   => $this->toHm($totalWorked),
      'balance'        => $this->toHm(round($totalWorked - $totalExpected, 2)),
      'daily'          => $daily,
    ]);
  }

  // ─────────────────────────────────────────────────────────────────────────

  private function buildPivotedRows(string $dateFrom, string $dateTo, ?int $personId = null, ?Request $request = null): \Illuminate\Support\Collection
  {
    $query = DB::table('attendance_sync as a')
      ->leftJoin('rrhh_persona as p', 'p.id', '=', 'a.person_id')
      ->leftJoin('work_schedules as ws', 'ws.id', '=', 'p.work_schedule_id')
      ->select([
        'a.date',
        'a.emp_code',
        DB::raw("UPPER(COALESCE(p.nombre_completo, a.full_name)) AS full_name"),
        'a.person_id',
        'p.vat',
        DB::raw("COALESCE(ws.checkin,   '08:00:00') AS schedule_checkin"),
        DB::raw("COALESCE(ws.lunch_out, '13:00:00') AS schedule_lunch_out"),
        DB::raw("COALESCE(ws.lunch_in,  '14:24:00') AS schedule_lunch_in"),
        DB::raw("COALESCE(ws.checkout,  '18:00:00') AS schedule_checkout"),
        DB::raw("MIN(CASE WHEN a.mark_type = 'check_in'  THEN a.time END) AS check_in"),
        DB::raw("MIN(CASE WHEN a.mark_type = 'lunch_out' THEN a.time END) AS lunch_out"),
        DB::raw("MIN(CASE WHEN a.mark_type = 'lunch_in'  THEN a.time END) AS lunch_in"),
        DB::raw("MAX(CASE WHEN a.mark_type = 'check_out' THEN a.time END) AS check_out"),
      ])
      ->whereDate('a.date', '>=', $dateFrom)
      ->whereDate('a.date', '<=', $dateTo)
      ->groupBy(
        'a.date', 'a.emp_code', 'a.person_id',
        'p.vat', 'p.nombre_completo', 'a.full_name',
        'ws.checkin', 'ws.lunch_out', 'ws.lunch_in', 'ws.checkout'
      )
      ->orderBy('a.emp_code')
      ->orderBy('a.date');

    if ($personId) {
      $query->where('a.person_id', $personId);
    }

    if ($request) {
      if ($request->filled('emp_code')) {
        $query->where('a.emp_code', $request->emp_code);
      }

      if ($request->filled('search')) {
        $term = $request->search;
        $query->where(function ($q) use ($term) {
          $q->where('a.emp_code', 'like', "%{$term}%")
            ->orWhere('p.nombre_completo', 'like', "%{$term}%")
            ->orWhere('a.full_name', 'like', "%{$term}%");
        });
      }
    }

    return $query->get();
  }

  private function buildInternalSummary(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
  {
    return $rows->groupBy('emp_code')->map(function ($dayRows, string $empCode) {
      $daily = $dayRows->map(function (object $row) {
        $checkIn  = $row->check_in;
        $lunchOut = $row->lunch_out;
        $lunchIn  = $row->lunch_in;
        $checkOut = $row->check_out;

        $dayOfWeek     = Carbon::parse($row->date)->dayOfWeek;
        $isSaturday    = $dayOfWeek === 6;
        $expectedHours = $isSaturday ? 5.0 : 8.6;

        $hoursWorked = null;
        if ($checkIn && $checkOut) {
          $grossMinutes = (Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 60;
          $lunchMinutes = $isSaturday ? 0 : 84; // 1h 24min Mon–Fri; no lunch on Saturday
          $hoursWorked  = round(max(0, $grossMinutes - $lunchMinutes) / 60, 2);
        }

        $balance = $hoursWorked !== null ? round($hoursWorked - $expectedHours, 2) : null;

        return [
          'date'           => $row->date,
          'check_in'       => $checkIn,
          'lunch_out'      => $lunchOut,
          'lunch_in'       => $lunchIn,
          'check_out'      => $checkOut,
          '_worked_raw'    => $hoursWorked,
          '_expected_raw'  => $expectedHours,
          'hours_worked'   => $this->toHm($hoursWorked),
          'expected_hours' => $this->toHm($expectedHours),
          'balance'        => $this->toHm($balance),
        ];
      })->sortBy('date')->values();

      $first         = $dayRows->first();
      $withData      = $daily->whereNotNull('_worked_raw');
      $totalWorked   = round($withData->sum('_worked_raw'), 2);
      $totalExpected = round($withData->sum('_expected_raw'), 2);
      $daily         = $daily->map(fn($d) => array_diff_key($d, array_flip(['_worked_raw', '_expected_raw'])));
      $daysPresent   = $daily->count();

      return [
        'person_id'      => $first->person_id,
        'emp_code'       => $empCode,
        'full_name'      => $first->full_name,
        'days_present'   => $daysPresent,
        'expected_hours' => $this->toHm($totalExpected),
        'hours_worked'   => $this->toHm($totalWorked),
        'balance'        => $this->toHm(round($totalWorked - $totalExpected, 2)),
        'daily'          => $daily,
      ];
    })->sortBy('full_name')->values();
  }

  private function toHm(?float $hours): ?string
  {
    if ($hours === null) return null;
    $sign  = $hours < 0 ? '-' : '';
    $total = (int) round(abs($hours) * 60);
    return "{$sign}" . intdiv($total, 60) . 'h ' . ($total % 60) . 'min';
  }

  private function generateScheduledTimes(object $row): array
  {
    if (! $row->check_in) {
      return ['check_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'check_out' => null];
    }

    $isSaturday = Carbon::parse($row->date)->dayOfWeek === 6;
    $offsets    = [];

    foreach (['check_in', 'lunch_out', 'lunch_in', 'check_out'] as $markType) {
      mt_srand(crc32($row->date . $row->emp_code . $markType));
      $offsets[$markType] = ['minutes' => mt_rand(3, 10), 'seconds' => mt_rand(0, 59)];
      mt_srand();
    }

    $checkIn  = $this->addTimeOffset($row->schedule_checkin,  $offsets['check_in']);
    $checkOut = $this->addTimeOffset($row->schedule_checkout, $offsets['check_out']);

    if ($isSaturday) {
      if ($checkOut > '13:00:00') {
        $checkOut = '13:00:00';
      }
      return ['check_in' => $checkIn, 'lunch_out' => null, 'lunch_in' => null, 'check_out' => $checkOut];
    }

    return [
      'check_in'  => $checkIn,
      'lunch_out' => $this->addTimeOffset($row->schedule_lunch_out, $offsets['lunch_out']),
      'lunch_in'  => $this->addTimeOffset($row->schedule_lunch_in,  $offsets['lunch_in']),
      'check_out' => $checkOut,
    ];
  }

  private function addTimeOffset(string $baseTime, array $offset): string
  {
    return Carbon::createFromFormat('H:i:s', $baseTime)
      ->addMinutes($offset['minutes'])
      ->addSeconds($offset['seconds'])
      ->format('H:i:s');
  }

  private function streamCsv(\Illuminate\Support\Collection $data, string $filename, array $headers): Response
  {
    $csv = implode(',', $headers) . "\n";
    foreach ($data as $row) {
      $csv .= implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $row[$h] ?? '') . '"', $headers)) . "\n";
    }

    return response($csv, 200, [
      'Content-Type'        => 'text/csv',
      'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ]);
  }
}

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

  public function sync(): JsonResponse
  {
    $date = now()->toDateString();
    $before = AttendanceSync::whereDate('date', $date)->count();

    SyncAttendanceJob::dispatchSync($date);

    $after = AttendanceSync::whereDate('date', $date)->count();

    return response()->json([
      'message'       => "Sync completed for {$date}.",
      'new_records'   => max(0, $after - $before),
      'total_for_day' => $after,
    ]);
  }

  public function syncRange(Request $request): JsonResponse
  {
    $request->validate([
      'date_from' => ['required', 'date_format:Y-m-d'],
      'date_to'   => ['required', 'date_format:Y-m-d', 'gte:date_from'],
    ]);

    $from = Carbon::createFromFormat('Y-m-d', $request->date_from);
    $to   = Carbon::createFromFormat('Y-m-d', $request->date_to);

    if ($from->diffInDays($to) > 90) {
      return response()->json(['message' => 'Date range cannot exceed 90 days.'], 422);
    }

    $days = 0;
    for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
      SyncAttendanceJob::dispatch($day->toDateString());
      $days++;
    }

    return response()->json([
      'message' => "{$days} jobs dispatched for {$from->toDateString()} → {$to->toDateString()}.",
      'days'    => $days,
    ]);
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
      $checkIn  = $row->check_in;
      $checkOut = $row->check_out;

      if ($checkOut && $checkOut > '18:00:00') {
        $checkOut = '18:00:00';
      }

      $hoursWorked = ($checkIn && $checkOut)
        ? round((Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 3600, 2)
        : null;

      return [
        'date'         => $row->date,
        'emp_code'     => $row->emp_code,
        'vat'          => $row->vat,
        'full_name'    => $row->full_name,
        'check_in'     => $checkIn,
        'check_out'    => $checkOut,
        'hours_worked' => $hoursWorked,
      ];
    });

    if ($request->get('export') === 'csv') {
      return $this->streamCsv($data, 'sunafil_' . $request->date_from . '_' . $request->date_to . '.csv', [
        'date', 'emp_code', 'vat', 'full_name', 'check_in', 'check_out', 'hours_worked',
      ]);
    }

    if ($request->get('export') === 'xlsx') {
      $columns  = [
        'date'         => 'Fecha',
        'emp_code'     => 'Código',
        'vat'          => 'DNI',
        'full_name'    => 'Nombre Completo',
        'check_in'     => 'Entrada',
        'check_out'    => 'Salida (cap. 18:00)',
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

      $hoursWorked = null;
      if ($checkIn && $checkOut) {
        if ($lunchOut && $lunchIn) {
          $hoursWorked = (Carbon::parse($lunchOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 3600
            + (Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($lunchIn)->getTimestamp()) / 3600;
        } else {
          $hoursWorked = (Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 3600;
        }
        $hoursWorked = round($hoursWorked, 2);
      }

      $dayOfWeek     = Carbon::parse($row->date)->dayOfWeek;
      $expectedHours = $dayOfWeek === 6 ? 5.0 : 8.6;

      return [
        'date'           => $row->date,
        'check_in'       => $checkIn,
        'lunch_out'      => $lunchOut,
        'lunch_in'       => $lunchIn,
        'check_out'      => $checkOut,
        'hours_worked'   => $hoursWorked,
        'expected_hours' => $expectedHours,
        'balance'        => $hoursWorked !== null ? round($hoursWorked - $expectedHours, 2) : null,
      ];
    })->sortBy('date')->values();

    $complete      = $daily->whereNotNull('hours_worked');
    $daysPresent   = $daily->count();
    $totalWorked   = round($complete->sum('hours_worked'), 2);
    $totalExpected = round($complete->sum('expected_hours'), 2);

    return response()->json([
      'person_id'      => $first->person_id,
      'emp_code'       => $first->emp_code,
      'full_name'      => $first->full_name,
      'vat'            => $first->vat,
      'date_from'      => $dateFrom,
      'date_to'        => $dateTo,
      'days_present'   => $daysPresent,
      'expected_hours' => $totalExpected,
      'hours_worked'   => $totalWorked,
      'balance'        => round($totalWorked - $totalExpected, 2),
      'daily'          => $daily,
    ]);
  }

  // ─────────────────────────────────────────────────────────────────────────

  private function buildPivotedRows(string $dateFrom, string $dateTo, ?int $personId = null, ?Request $request = null): \Illuminate\Support\Collection
  {
    $query = DB::table('attendance_sync as a')
      ->leftJoin('rrhh_persona as p', 'p.id', '=', 'a.person_id')
      ->select([
        'a.date',
        'a.emp_code',
        DB::raw("UPPER(COALESCE(p.nombre_completo, a.full_name)) AS full_name"),
        'a.person_id',
        'p.vat',
        DB::raw("MAX(CASE WHEN a.mark_type = 'check_in'  THEN a.time END) AS check_in"),
        DB::raw("MAX(CASE WHEN a.mark_type = 'lunch_out' THEN a.time END) AS lunch_out"),
        DB::raw("MAX(CASE WHEN a.mark_type = 'lunch_in'  THEN a.time END) AS lunch_in"),
        DB::raw("MAX(CASE WHEN a.mark_type = 'check_out' THEN a.time END) AS check_out"),
      ])
      ->whereDate('a.date', '>=', $dateFrom)
      ->whereDate('a.date', '<=', $dateTo)
      ->groupBy('a.date', 'a.emp_code', 'a.person_id', 'p.vat', 'p.nombre_completo', 'a.full_name')
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

        $hoursWorked = null;
        if ($checkIn && $checkOut) {
          if ($lunchOut && $lunchIn) {
            $hoursWorked = (Carbon::parse($lunchOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 3600
              + (Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($lunchIn)->getTimestamp()) / 3600;
          } else {
            $hoursWorked = (Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp()) / 3600;
          }
          $hoursWorked = round($hoursWorked, 2);
        }

        $dayOfWeek     = Carbon::parse($row->date)->dayOfWeek;
        $expectedHours = $dayOfWeek === 6 ? 5.0 : 8.6;

        return [
          'date'           => $row->date,
          'check_in'       => $checkIn,
          'lunch_out'      => $lunchOut,
          'lunch_in'       => $lunchIn,
          'check_out'      => $checkOut,
          'hours_worked'   => $hoursWorked,
          'expected_hours' => $expectedHours,
          'balance'        => $hoursWorked !== null ? round($hoursWorked - $expectedHours, 2) : null,
        ];
      })->sortBy('date')->values();

      $first         = $dayRows->first();
      $daysPresent   = $daily->count();
      $complete      = $daily->whereNotNull('hours_worked');
      $totalWorked   = round($complete->sum('hours_worked'), 2);
      $totalExpected = round($complete->sum('expected_hours'), 2);

      return [
        'person_id'      => $first->person_id,
        'emp_code'       => $empCode,
        'full_name'      => $first->full_name,
        'days_present'   => $daysPresent,
        'expected_hours' => $totalExpected,
        'hours_worked'   => $totalWorked,
        'balance'        => round($totalWorked - $totalExpected, 2),
        'daily'          => $daily,
      ];
    })->sortBy('full_name')->values();
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

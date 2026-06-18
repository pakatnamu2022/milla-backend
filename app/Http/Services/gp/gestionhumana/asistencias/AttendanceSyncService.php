<?php

namespace App\Http\Services\gp\gestionhumana\asistencias;

use App\Exports\AttendanceReportExport;
use App\Exports\GeneralExport;
use App\Http\Resources\gp\gestionhumana\asistencias\AttendanceSyncResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\EmailService;
use App\Http\Utils\Constants;
use App\Jobs\SendAbsentAttendanceReportJob;
use App\Jobs\SyncAttendanceJob;
use App\Models\gp\gestionhumana\asistencias\AttendanceSync;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Holidays\Holidays;
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
      $date = $from->toDateString();
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
      'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
      'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
      'this_week' => [$now->copy()->startOfWeek(), $now->copy()],
      'this_month' => [$now->copy()->startOfMonth(), $now->copy()],
      'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
      'last_3_months' => [$now->copy()->subMonths(3)->startOfMonth(), $now->copy()],
      'last_6_months' => [$now->copy()->subMonths(6)->startOfMonth(), $now->copy()],
      'custom' => [Carbon::createFromFormat('Y-m-d', $dateFrom), Carbon::createFromFormat('Y-m-d', $dateTo)],
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
      $request->person_id ? (int)$request->person_id : null,
      $request,
    );

    $data = $rows->map(function (object $row) {
      $scheduled = $this->generateScheduledTimes($row);
      $checkIn = $scheduled['check_in'];
      $checkOut = $scheduled['check_out'];
      $lunchOut = $scheduled['lunch_out'];
      $lunchIn = $scheduled['lunch_in'];

      if ($checkIn && $checkOut) {
        $grossSeconds = Carbon::parse($checkOut)->getTimestamp() - Carbon::parse($checkIn)->getTimestamp();
        $lunchSeconds = ($lunchOut && $lunchIn)
          ? Carbon::parse($lunchIn)->getTimestamp() - Carbon::parse($lunchOut)->getTimestamp()
          : 0;
        $hoursWorked = round(max(0, $grossSeconds - $lunchSeconds) / 3600, 2);
      } else {
        $hoursWorked = null;
      }

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
      $columns = [
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

    $rows = $this->buildPivotedRows(
      $request->date_from,
      $request->date_to,
      $request->person_id ? (int)$request->person_id : null,
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
      $columns = [
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
    $dateTo = $request->get('date_to', now()->toDateString());

    $rows = $this->buildPivotedRows($dateFrom, $dateTo, $personId, null)->keyBy('date');
    $holidays = $this->loadHolidays($dateFrom, $dateTo);
    $vacations = $this->loadPersonVacations($personId, $dateFrom, $dateTo);
    $schedule = $this->getPersonScheduleRow($personId);

    if ($rows->isEmpty() && !$schedule) {
      return response()->json(['message' => 'No attendance records found for this person in the given range.'], 404);
    }

    $firstRow = $rows->first();
    $empCode = $firstRow?->emp_code;
    $fullName = $firstRow ? $firstRow->full_name : strtoupper($schedule?->full_name ?? '');
    $vat = $firstRow?->vat ?? $schedule?->vat;

    $daily = collect();

    foreach (CarbonPeriod::create($dateFrom, $dateTo) as $day) {
      $dateStr = $day->toDateString();
      $dayOfWeek = $day->dayOfWeek;

      if ($dayOfWeek === 0) {
        continue; // Skip Sundays
      }

      if (isset($holidays[$dateStr])) {
        $daily->push([
          'date'           => $dateStr,
          'type'           => 'holiday',
          'holiday_name'   => $holidays[$dateStr],
          'check_in'       => null,
          'lunch_out'      => null,
          'lunch_in'       => null,
          'check_out'      => null,
          'is_estimated'   => false,
          'hours_worked'   => $this->toHm(0.0),
          'expected_hours' => $this->toHm(0.0),
          'balance'        => $this->toHm(0.0),
        ]);
        continue;
      }

      if (isset($vacations[$dateStr])) {
        $daily->push([
          'date'           => $dateStr,
          'type'           => 'vacation',
          'vacation_id'    => $vacations[$dateStr],
          'check_in'       => null,
          'lunch_out'      => null,
          'lunch_in'       => null,
          'check_out'      => null,
          'is_estimated'   => false,
          'hours_worked'   => $this->toHm(0.0),
          'expected_hours' => $this->toHm(0.0),
          'balance'        => $this->toHm(0.0),
        ]);
        continue;
      }

      $row = $rows->get($dateStr) ?? ($schedule ? $this->makeSyntheticRow($dateStr, $personId, $schedule) : null);
      if (!$row) continue;

      $isSaturday = $dayOfWeek === 6;
      $marks = $this->resolveMarks($row, $isSaturday);
      [$lunchMinutes, $expectedHours] = $this->computeScheduleExpectation($row, $isSaturday);

      $hoursWorked = null;
      if ($marks['checkIn'] && $marks['checkOut']) {
        $grossMinutes = (Carbon::parse($marks['checkOut'])->getTimestamp() - Carbon::parse($marks['checkIn'])->getTimestamp()) / 60;
        $hoursWorked = round(max(0, $grossMinutes - $lunchMinutes) / 60, 2);
      }

      $balance = $hoursWorked !== null
        ? round($hoursWorked - $expectedHours, 2)
        : round(-$expectedHours, 2);

      $daily->push([
        'date'           => $dateStr,
        'type'           => 'work',
        'message'        => match (true) {
          !$marks['checkIn']  => 'Sin marcación',
          !$marks['checkOut'] => 'Sin salida registrada',
          Carbon::createFromFormat('H:i:s', $marks['checkIn'])->gt(
            Carbon::createFromFormat('H:i:s', $row->schedule_checkin)->addMinutes(15)
          )                  => 'Tardanza',
          default            => 'Asistió',
        },
        'check_in'       => $marks['checkIn'],
        'lunch_out'      => $marks['lunchOut'],
        'lunch_in'       => $marks['lunchIn'],
        'check_out'      => $marks['checkOut'],
        'is_estimated'   => $marks['isEstimated'],
        '_worked_raw'    => $hoursWorked,
        '_expected_raw'  => $expectedHours,
        'hours_worked'   => $this->toHm($hoursWorked),
        'expected_hours' => $this->toHm($expectedHours),
        'balance'        => $this->toHm($balance),
      ]);
    }

    $workDays = $daily->where('type', 'work');
    $totalWorked = round($workDays->whereNotNull('_worked_raw')->sum('_worked_raw'), 2);
    $totalExpected = round($workDays->sum('_expected_raw'), 2);

    $daily = $daily->map(fn($d) => array_diff_key($d, array_flip(['_worked_raw', '_expected_raw'])));

    return response()->json([
      'person_id'         => $personId,
      'emp_code'          => $empCode,
      'full_name'         => $fullName,
      'vat'               => $vat,
      'date_from'         => $dateFrom,
      'date_to'           => $dateTo,
      'days_with_records' => $rows->count(),
      'days_expected'     => $daily->where('type', 'work')->count(),
      'days_on_vacation'  => $daily->where('type', 'vacation')->count(),
      'expected_hours'    => $this->toHm($totalExpected),
      'hours_worked'      => $this->toHm($totalWorked),
      'balance'           => $this->toHm(round($totalWorked - $totalExpected, 2)),
      'daily'             => $daily->values(),
    ]);
  }

  public function sendAbsentReport(?string $date = null, ?int $partnerUserId = null, ?string $overrideEmail = null): array
  {
    $targetDate = $date
      ? Carbon::createFromFormat('Y-m-d', $date)
      : now('America/Lima');

    $dateStr = $targetDate->toDateString();

    SyncAttendanceJob::dispatchSync($dateStr);

    $recipient = $this->resolveReportRecipient($partnerUserId, $overrideEmail);
    if (!$recipient) {
      return ['sent' => false, 'message' => "No se encontró destinatario para user_id={$partnerUserId}."];
    }

    // Build the company-scope filter (null = all companies)
    $sedeIds = ($partnerUserId && $partnerUserId !== Constants::ATTENDANCE_ALL_ACCESS_USER_ID)
      ? $this->getPartnerCompanySedes($partnerUserId)
      : null;

    $absentRows = $this->buildAbsentRows($dateStr, $sedeIds);
    $lateRows = $this->buildLateRows($dateStr, $sedeIds);

    if ($absentRows->isEmpty() && $lateRows->isEmpty()) {
      return [
        'sent'    => false,
        'count'   => 0,
        'message' => "Sin ausentes ni tardanzas para {$dateStr} (user_id={$partnerUserId}).",
      ];
    }

    $export = new AttendanceReportExport($absentRows, $lateRows);
    $raw = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
    $tmpPath = tempnam(sys_get_temp_dir(), 'attendance_') . '.xlsx';
    file_put_contents($tmpPath, $raw);
    $filename = 'asistencias_' . $dateStr . '.xlsx';

    $isLocal = app()->environment('local');

    $emailService = new EmailService();
    $emailService->send([
      'to'          => $isLocal ? Constants::EMAIL_TEST : $recipient->email,
      'cc'          => $isLocal ? [] : ['ymontalvop@grupopakatnamu.com'],
      'subject'     => "Reporte de Asistencias {$targetDate->format('d/m/Y')} — {$absentRows->count()} sin marcar, {$lateRows->count()} tardanzas",
      'template'    => 'emails.attendance-absent-report',
      'data'        => [
        'report_date'  => $targetDate->format('d/m/Y'),
        'absent_count' => $absentRows->count(),
        'late_count'   => $lateRows->count(),
        'workers'      => $absentRows->map(fn($r) => ['dni' => $r['dni'], 'full_name' => $r['full_name']])->toArray(),
      ],
      'attachments' => [[
        'path' => $tmpPath,
        'name' => $filename,
        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ]],
    ]);

    @unlink($tmpPath);

    return [
      'sent'         => true,
      'absent_count' => $absentRows->count(),
      'late_count'   => $lateRows->count(),
      'date'         => $dateStr,
      'recipient'    => $recipient->email,
      'message'      => "Reporte enviado a {$recipient->email}: {$absentRows->count()} ausente(s) y {$lateRows->count()} tardanza(s) para {$dateStr}.",
    ];
  }

  public function reportAbsent(Request $request): JsonResponse
  {
    $request->validate([
      'date' => ['nullable', 'date_format:Y-m-d'],
    ]);

    $results = [];
    foreach (Constants::ATTENDANCE_REPORT_USER_IDS as $userId) {
      $results[] = $this->sendAbsentReport($request->date, $userId);
    }

    return response()->json($results);
  }

  private function resolveReportRecipient(?int $partnerUserId, ?string $overrideEmail = null): ?object
  {
    if (!$partnerUserId) {
      return (object)['email' => $overrideEmail ?? Constants::EMAIL_TEST, 'full_name' => 'TEST'];
    }

    $row = DB::table('usr_users as u')
      ->join('rrhh_persona as p', 'p.id', '=', 'u.partner_id')
      ->where('u.id', $partnerUserId)
      ->select(
        DB::raw("p.email2 AS email"),
        DB::raw("UPPER(COALESCE(p.nombre_completo, u.name)) AS full_name"),
      )
      ->first();

    if ($row && $overrideEmail) {
      $row->email = $overrideEmail;
    }

    return $row;
  }

  private function getPartnerCompanySedes(int $partnerUserId): array
  {
    // Get all sedes of the same companies as the user's assigned sedes
    $empresaIds = DB::table('assigment_user_sede as aus')
      ->join('config_sede as cs', 'cs.id', '=', 'aus.sede_id')
      ->where('aus.user_id', $partnerUserId)
      ->whereNull('aus.deleted_at')
      ->pluck('cs.empresa_id')
      ->unique()
      ->toArray();

    if (empty($empresaIds)) {
      return [];
    }

    return DB::table('config_sede')
      ->whereIn('empresa_id', $empresaIds)
      ->pluck('id')
      ->toArray();
  }

  private function buildAbsentRows(string $dateStr, ?array $sedeIds): \Illuminate\Support\Collection
  {
    $query = DB::table('rrhh_persona as p')
      ->leftJoin('attendance_sync as a', function ($join) use ($dateStr) {
        $join->on(function ($j) {
          $j->on('a.person_id', '=', 'p.id')
            ->orOn('a.emp_code', '=', 'p.vat');
        })
          ->where('a.date', '=', $dateStr)
          ->where('a.mark_type', '=', 'check_in');
      })
      ->leftJoin('rrhh_cargo as rc', 'rc.id', '=', 'p.cargo_id')
      ->leftJoin('rrhh_area as ra', 'ra.id', '=', 'p.area_id')
      ->leftJoin('config_sede as cs', 'cs.id', '=', 'p.sede_id')
      ->leftJoin('rrhh_persona as jefe_p', 'jefe_p.id', '=', 'p.jefe_id')
      ->whereNull('a.id')
      ->where('p.status_id', Constants::WORKER_ACTIVE)
      ->where(fn($q) => $q->whereNull('rc.no_attendance_required')->orWhere('rc.no_attendance_required', 0))
      ->select(
        DB::raw("COALESCE(p.vat, '') AS dni"),
        DB::raw("UPPER(COALESCE(p.nombre_completo, '')) AS full_name"),
        DB::raw("UPPER(COALESCE(jefe_p.nombre_completo, '')) AS jefe_directo"),
        DB::raw("COALESCE(rc.name, '') AS cargo"),
        DB::raw("COALESCE(ra.name, '') AS area"),
        DB::raw("COALESCE(cs.abreviatura, cs.localidad, '') AS sede"),
      )
      ->orderBy('p.nombre_completo');

    if (!empty($sedeIds)) {
      $query->whereIn('p.sede_id', $sedeIds);
    }

    return $query->get()->map(fn($r) => (array)$r);
  }

  private function buildLateRows(string $dateStr, ?array $sedeIds): \Illuminate\Support\Collection
  {
    $query = DB::table('attendance_sync as a')
      ->join('rrhh_persona as p', function ($join) {
        $join->on('p.id', '=', 'a.person_id')
          ->orOn('p.vat', '=', 'a.emp_code');
      })
      ->leftJoin('work_schedules as ws', 'ws.id', '=', 'p.work_schedule_id')
      ->leftJoin('rrhh_cargo as rc', 'rc.id', '=', 'p.cargo_id')
      ->leftJoin('rrhh_area as ra', 'ra.id', '=', 'p.area_id')
      ->leftJoin('config_sede as cs', 'cs.id', '=', 'p.sede_id')
      ->leftJoin('rrhh_persona as jefe_p', 'jefe_p.id', '=', 'p.jefe_id')
      ->whereDate('a.date', $dateStr)
      ->where('a.mark_type', 'check_in')
      ->whereRaw("a.time > ADDTIME(COALESCE(ws.checkin, '08:00:00'), '00:15:00')")
      ->where('p.status_id', Constants::WORKER_ACTIVE)
      ->where(fn($q) => $q->whereNull('rc.no_attendance_required')->orWhere('rc.no_attendance_required', 0))
      ->select(
        DB::raw("COALESCE(p.vat, '') AS dni"),
        DB::raw("UPPER(COALESCE(p.nombre_completo, '')) AS full_name"),
        DB::raw("UPPER(COALESCE(jefe_p.nombre_completo, '')) AS jefe_directo"),
        'a.time AS check_in',
        DB::raw("COALESCE(ws.checkin, '08:00:00') AS schedule"),
        DB::raw("TIMESTAMPDIFF(MINUTE, COALESCE(ws.checkin, '08:00:00'), a.time) AS minutes_late"),
        DB::raw("COALESCE(rc.name, '') AS cargo"),
        DB::raw("COALESCE(ra.name, '') AS area"),
        DB::raw("COALESCE(cs.abreviatura, cs.localidad, '') AS sede"),
      )
      ->orderBy('p.nombre_completo');

    if (!empty($sedeIds)) {
      $query->whereIn('p.sede_id', $sedeIds);
    }

    return $query->get()->map(fn($r) => (array)$r);
  }

  // ─────────────────────────────────────────────────────────────────────────

  private function buildPivotedRows(string $dateFrom, string $dateTo, ?int $personId = null, ?Request $request = null): \Illuminate\Support\Collection
  {
    $query = DB::table('attendance_sync as a')
      ->leftJoin('rrhh_persona as p', 'p.id', '=', 'a.person_id')
      ->leftJoin('work_schedules as ws', 'ws.id', '=', 'p.work_schedule_id')
      ->leftJoin('work_schedule_details as wsd', function ($join) {
        $join->on('wsd.work_schedule_id', '=', 'p.work_schedule_id')
             ->whereRaw('wsd.day_of_week = DAYOFWEEK(a.date)');
      })
      ->select([
        'a.date',
        'a.emp_code',
        DB::raw("UPPER(COALESCE(p.nombre_completo, a.full_name)) AS full_name"),
        'a.person_id',
        'p.vat',
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.checkin  ELSE COALESCE(ws.checkin,   '08:00:00') END AS schedule_checkin"),
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.lunch_out ELSE COALESCE(ws.lunch_out, '13:00:00') END AS schedule_lunch_out"),
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.lunch_in  ELSE COALESCE(ws.lunch_in,  '14:24:00') END AS schedule_lunch_in"),
        DB::raw("CASE WHEN wsd.work_schedule_id IS NOT NULL THEN wsd.checkout  ELSE COALESCE(ws.checkout,  '18:00:00') END AS schedule_checkout"),
        DB::raw("MIN(CASE WHEN a.mark_type = 'check_in'  THEN a.time END) AS check_in"),
        DB::raw("MIN(CASE WHEN a.mark_type = 'lunch_out' THEN a.time END) AS lunch_out"),
        DB::raw("MIN(CASE WHEN a.mark_type = 'lunch_in'  THEN a.time END) AS lunch_in"),
        DB::raw("MAX(CASE WHEN a.mark_type = 'check_out' THEN a.time END) AS check_out"),
      ])
      ->whereDate('a.date', '>=', $dateFrom)
      ->whereDate('a.date', '<=', $dateTo)
      ->where('p.status_id', 22)
      ->groupBy(
        'a.date', 'a.emp_code', 'a.person_id',
        'p.vat', 'p.nombre_completo', 'a.full_name',
        'ws.checkin', 'ws.lunch_out', 'ws.lunch_in', 'ws.checkout',
        'wsd.work_schedule_id', 'wsd.checkin', 'wsd.lunch_out', 'wsd.lunch_in', 'wsd.checkout'
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
        $dayOfWeek = Carbon::parse($row->date)->dayOfWeek;
        $isSaturday = $dayOfWeek === 6;
        $marks = $this->resolveMarks($row, $isSaturday);
        [$lunchMinutes, $expectedHours] = $this->computeScheduleExpectation($row, $isSaturday);

        $hoursWorked = null;
        if ($marks['checkIn'] && $marks['checkOut']) {
          $grossMinutes = (Carbon::parse($marks['checkOut'])->getTimestamp() - Carbon::parse($marks['checkIn'])->getTimestamp()) / 60;
          $hoursWorked = round(max(0, $grossMinutes - $lunchMinutes) / 60, 2);
        }

        $balance = $hoursWorked !== null ? round($hoursWorked - $expectedHours, 2) : null;

        return [
          'date'           => $row->date,
          'check_in'       => $marks['checkIn'],
          'lunch_out'      => $marks['lunchOut'],
          'lunch_in'       => $marks['lunchIn'],
          'check_out'      => $marks['checkOut'],
          'is_estimated'   => $marks['isEstimated'],
          '_worked_raw'    => $hoursWorked,
          '_expected_raw'  => $expectedHours,
          'hours_worked'   => $this->toHm($hoursWorked),
          'expected_hours' => $this->toHm($expectedHours),
          'balance'        => $this->toHm($balance),
        ];
      })->sortBy('date')->values();

      $first = $dayRows->first();
      $withData = $daily->whereNotNull('_worked_raw');
      $totalWorked = round($withData->sum('_worked_raw'), 2);
      $totalExpected = round($withData->sum('_expected_raw'), 2);
      $daily = $daily->map(fn($d) => array_diff_key($d, array_flip(['_worked_raw', '_expected_raw'])));
      $daysPresent = $daily->count();

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
    $sign = $hours < 0 ? '-' : '';
    $total = (int)round(abs($hours) * 60);
    return "{$sign}" . intdiv($total, 60) . 'h ' . ($total % 60) . 'min';
  }

  private function generateScheduledTimes(object $row): array
  {
    if (!$row->check_in) {
      return ['check_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'check_out' => null];
    }

    $isSaturday = Carbon::parse($row->date)->dayOfWeek === 6;
    $offsets = [];

    foreach (['check_in', 'lunch_out', 'lunch_in', 'check_out'] as $markType) {
      mt_srand(crc32($row->date . $row->emp_code . $markType));
      $offsets[$markType] = ['minutes' => mt_rand(3, 10), 'seconds' => mt_rand(0, 59)];
      mt_srand();
    }

    $checkIn = $this->addTimeOffset($row->schedule_checkin, $offsets['check_in']);
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
      'lunch_in'  => $this->addTimeOffset($row->schedule_lunch_in, $offsets['lunch_in']),
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

  private function loadHolidays(string $dateFrom, string $dateTo): array
  {
    return Holidays::for('pe')->getInRange($dateFrom, $dateTo);
  }

  /**
   * Devuelve un mapa de date => vacation_id para los días de vacación del empleado
   * en el rango dado. Solo considera vacaciones aprobadas por RRHH.
   */
  private function loadPersonVacations(int $personId, string $dateFrom, string $dateTo): array
  {
    $records = DB::table('rrhh_vacaciones')
      ->where('empleado_id', $personId)
      ->where('status_deleted', 1)
      ->where('aprobacion_rrhh', 1)
      ->where('fecha_inicio', '<=', $dateTo)
      ->where('fecha_fin', '>=', $dateFrom)
      ->select(['id', 'fecha_inicio', 'fecha_fin'])
      ->get();

    $map = [];
    foreach ($records as $vacation) {
      foreach (CarbonPeriod::create($vacation->fecha_inicio, $vacation->fecha_fin) as $day) {
        $dateStr = $day->toDateString();
        if ($dateStr >= $dateFrom && $dateStr <= $dateTo) {
          $map[$dateStr] = $vacation->id;
        }
      }
    }

    return $map;
  }

  private function getPersonScheduleRow(int $personId): ?object
  {
    $row = DB::table('rrhh_persona as p')
      ->leftJoin('work_schedules as ws', 'ws.id', '=', 'p.work_schedule_id')
      ->where('p.id', $personId)
      ->select([
        'p.id as person_id',
        'p.work_schedule_id',
        'p.nombre_completo as full_name',
        'p.vat',
        DB::raw("COALESCE(ws.checkin,   '08:00:00') AS schedule_checkin"),
        DB::raw("COALESCE(ws.lunch_out, '13:00:00') AS schedule_lunch_out"),
        DB::raw("COALESCE(ws.lunch_in,  '14:24:00') AS schedule_lunch_in"),
        DB::raw("COALESCE(ws.checkout,  '18:00:00') AS schedule_checkout"),
      ])
      ->first();

    if ($row) {
      // Pre-load per-day overrides keyed by MySQL DAYOFWEEK (1=Sun…7=Sat)
      $row->day_overrides = $row->work_schedule_id
        ? DB::table('work_schedule_details')
            ->where('work_schedule_id', $row->work_schedule_id)
            ->get()
            ->keyBy('day_of_week')
        : collect();
    }

    return $row;
  }

  private function makeSyntheticRow(string $date, int $personId, object $schedule): object
  {
    // Carbon dayOfWeek (0=Sun, 6=Sat) + 1 = MySQL DAYOFWEEK (1=Sun, 7=Sat)
    $mysqlDow = Carbon::parse($date)->dayOfWeek + 1;
    $override = $schedule->day_overrides->get($mysqlDow);

    return (object)[
      'date'               => $date,
      'person_id'          => $personId,
      'emp_code'           => null,
      'full_name'          => strtoupper($schedule->full_name ?? ''),
      'vat'                => $schedule->vat,
      'check_in'           => null,
      'lunch_out'          => null,
      'lunch_in'           => null,
      'check_out'          => null,
      'schedule_checkin'   => $override ? $override->checkin   : $schedule->schedule_checkin,
      'schedule_lunch_out' => $override ? $override->lunch_out : $schedule->schedule_lunch_out,
      'schedule_lunch_in'  => $override ? $override->lunch_in  : $schedule->schedule_lunch_in,
      'schedule_checkout'  => $override ? $override->checkout  : $schedule->schedule_checkout,
    ];
  }

  private function computeScheduleExpectation(object $row, bool $isSaturday): array
  {
    $schedIn  = Carbon::createFromFormat('H:i:s', $row->schedule_checkin);
    $schedOut = Carbon::createFromFormat('H:i:s', $row->schedule_checkout);
    $lunchMinutes = (!$isSaturday && ($row->schedule_lunch_out ?? null) && ($row->schedule_lunch_in ?? null))
      ? (int) Carbon::createFromFormat('H:i:s', $row->schedule_lunch_in)
              ->diffInMinutes(Carbon::createFromFormat('H:i:s', $row->schedule_lunch_out))
      : 0;
    $expectedHours = round(max(0, $schedIn->diffInMinutes($schedOut) - $lunchMinutes) / 60, 2);
    return [$lunchMinutes, $expectedHours];
  }

  private function resolveMarks(object $row, bool $isSaturday): array
  {
    $checkIn  = $row->check_in  ?? null;
    $checkOut = $row->check_out ?? null;

    // Fill checkout with schedule value when the worker checked in but never checked out
    // and the scheduled checkout time has already passed (past days always qualify).
    if ($checkIn && !$checkOut) {
      $isPastDay = $row->date < now()->toDateString();
      $isToday   = $row->date === now()->toDateString();
      if ($isPastDay || ($isToday && now()->gt(Carbon::createFromFormat('H:i:s', $row->schedule_checkout)))) {
        $checkOut = $row->schedule_checkout;
      }
    }

    $lunchOut = ($checkIn && $checkOut && !$isSaturday) ? ($row->lunch_out ?? $row->schedule_lunch_out) : null;
    $lunchIn  = ($checkIn && $checkOut && !$isSaturday) ? ($row->lunch_in  ?? $row->schedule_lunch_in)  : null;

    $isEstimated = !$row->check_in || !$row->check_out;

    return compact('checkIn', 'checkOut', 'lunchOut', 'lunchIn', 'isEstimated');
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

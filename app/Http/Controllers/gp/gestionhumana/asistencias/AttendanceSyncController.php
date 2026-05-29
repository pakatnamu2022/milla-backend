<?php

namespace App\Http\Controllers\gp\gestionhumana\asistencias;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAttendanceJob;
use App\Models\gp\gestionhumana\asistencias\AttendanceSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSyncController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $query = AttendanceSync::query()->with('person');

    if ($request->filled('date')) {
      $query->whereDate('date', $request->date);
    } else {
      if ($request->filled('date_from')) {
        $query->whereDate('date', '>=', $request->date_from);
      }
      if ($request->filled('date_to')) {
        $query->whereDate('date', '<=', $request->date_to);
      }
    }

    if ($request->filled('emp_code')) {
      $query->where('emp_code', $request->emp_code);
    }

    if ($request->filled('person_id')) {
      $query->where('person_id', $request->person_id);
    }

    if ($request->filled('mark_type')) {
      $query->where('mark_type', $request->mark_type);
    }

    $perPage = min((int) $request->get('per_page', 50), 200);

    $records = $query
      ->orderBy('date', 'desc')
      ->orderBy('emp_code')
      ->orderBy('time')
      ->paginate($perPage);

    return response()->json($records);
  }

  public function show(int $id): JsonResponse
  {
    $record = AttendanceSync::with('person')->findOrFail($id);

    return response()->json($record);
  }

  public function sync(): JsonResponse
  {
    $date   = now()->toDateString();
    $before = AttendanceSync::whereDate('date', $date)->count();

    SyncAttendanceJob::dispatchSync($date);

    $after = AttendanceSync::whereDate('date', $date)->count();

    return response()->json([
      'message'       => "Sync completed for {$date}.",
      'new_records'   => max(0, $after - $before),
      'total_for_day' => $after,
    ]);
  }
}

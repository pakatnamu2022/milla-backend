<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\tp\comercial\DriverStatusLog;
use App\Models\tp\Driver;
use Illuminate\Http\Request;

class DriverStatusLogService extends BaseService
{
    public function index(Request $request)
    {
        $logs = DriverStatusLog::with('driver')
               ->when($request->driver_id, function($query, $driverId) {
                    return $query->where('driver_id', $driverId);
               })
               ->when($request->status, function($query, $status) {
                    return $query->where('status', $status);
               })
               ->when($request->from_date, function($query, $date) {
                    return $query->where('created_at', '>=', $date);
               })
               ->when($request->to_date, function($query, $date) {
                    return $query->where('created_at', '<=', $date);
               })
               ->orderBy('changed_at', 'desc')
               ->paginate($request->per_page ?? 50);
        
        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function byDriver($driverId)
    {
        $logs = DriverStatusLog::where('driver_id', $driverId)
        ->orderBy('changed_at', 'desc')
        ->limit(100)
        ->get();

        return response()->json([
            'success'=> true,
            'data'=> $logs
        ]);
    }

    public function logStatusChange(Driver $driver, string $status)
    {
        DriverStatusLog::create([
            'driver_id' => $driver->id,
            'status' => $status,
            'changed_at' => now()
        ]);
    }
}
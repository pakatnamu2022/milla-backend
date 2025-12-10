<?php

namespace App\Http\Controllers\Api\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Models\gp\gestionhumana\viaticos\HotelAgreement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class HotelAgreementController extends Controller
{
    /**
     * Get all hotel agreements (can filter by city)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = HotelAgreement::query();

            if ($request->has('city')) {
                $query->where('city', 'like', '%' . $request->input('city') . '%');
            }

            $agreements = $query->orderBy('hotel_name')->get();

            return response()->json([
                'success' => true,
                'data' => $agreements,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener convenios de hotel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get only active hotel agreements
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $query = HotelAgreement::where('active', true);

            if ($request->has('city')) {
                $query->where('city', 'like', '%' . $request->input('city') . '%');
            }

            $agreements = $query->orderBy('hotel_name')->get();

            return response()->json([
                'success' => true,
                'data' => $agreements,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener convenios activos de hotel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

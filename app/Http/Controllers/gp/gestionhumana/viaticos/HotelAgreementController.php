<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexHotelAgreementRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelAgreementResource;
use App\Models\gp\gestionhumana\viaticos\HotelAgreement;

class HotelAgreementController extends Controller
{
    /**
     * Display a listing of all hotel agreements
     */
    public function index(IndexHotelAgreementRequest $request)
    {
        try {
            $agreements = HotelAgreement::with('district')->orderBy('hotel_name')->get();

            return response()->json([
                'success' => true,
                'data' => HotelAgreementResource::collection($agreements)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display active hotel agreements only
     */
    public function active()
    {
        try {
            $agreements = HotelAgreement::where('active', true)
                ->with('district')
                ->orderBy('hotel_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => HotelAgreementResource::collection($agreements)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

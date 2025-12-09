<?php

namespace App\Http\Controllers\Api\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Resources\gp\gestionhumana\viaticos\ExpenseTypeResource;
use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use Illuminate\Http\JsonResponse;
use Exception;

class ExpenseTypeController extends Controller
{
    /**
     * Get all expense types
     */
    public function index(): JsonResponse
    {
        try {
            $expenseTypes = ExpenseType::with('parent')->withCount('children')->get();

            return response()->json([
                'success' => true,
                'data' => ExpenseTypeResource::collection($expenseTypes),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de gasto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get only active expense types
     */
    public function active(): JsonResponse
    {
        try {
            $expenseTypes = ExpenseType::where('active', true)
                ->with('parent')
                ->withCount('children')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ExpenseTypeResource::collection($expenseTypes),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de gasto activos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get only parent expense types (no parent_id)
     */
    public function parents(): JsonResponse
    {
        try {
            $expenseTypes = ExpenseType::whereNull('parent_id')
                ->where('active', true)
                ->withCount('children')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ExpenseTypeResource::collection($expenseTypes),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de gasto padre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

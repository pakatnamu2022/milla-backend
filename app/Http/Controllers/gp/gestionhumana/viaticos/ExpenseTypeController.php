<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexExpenseTypeRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\ExpenseTypeResource;
use App\Models\gp\gestionhumana\viaticos\ExpenseType;

class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of all expense types
     */
    public function index(IndexExpenseTypeRequest $request)
    {
        try {
            $expenseTypes = ExpenseType::with('parent')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => ExpenseTypeResource::collection($expenseTypes)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display active expense types only
     */
    public function active()
    {
        try {
            $expenseTypes = ExpenseType::where('active', true)
                ->with('parent')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ExpenseTypeResource::collection($expenseTypes)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display parent expense types only
     */
    public function parents()
    {
        try {
            $expenseTypes = ExpenseType::whereNull('parent_id')
                ->where('active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ExpenseTypeResource::collection($expenseTypes)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

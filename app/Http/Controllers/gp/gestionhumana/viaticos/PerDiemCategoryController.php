<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemCategoryResource;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use Exception;
use Illuminate\Http\JsonResponse;

class PerDiemCategoryController extends Controller
{
  /**
   * Get all categories
   */
  public function index(): JsonResponse
  {
    try {
      $categories = PerDiemCategory::all();

      return response()->json([
        'success' => true,
        'data' => PerDiemCategoryResource::collection($categories),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener categorías',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get only active categories
   */
  public function active(): JsonResponse
  {
    try {
      $categories = PerDiemCategory::where('active', true)->get();

      return response()->json([
        'success' => true,
        'data' => PerDiemCategoryResource::collection($categories),
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener categorías activas',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}

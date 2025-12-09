<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemCategoryResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerDiemCategoryService extends BaseService
{
  public function index(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      PerDiemCategory::class,
      $request,
      PerDiemCategory::filters,
      PerDiemCategory::sorts,
      PerDiemCategoryResource::class,
    );
  }

  public function active(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      PerDiemCategory::where('active', true),
      $request,
      PerDiemCategory::filters,
      PerDiemCategory::sorts,
      PerDiemCategoryResource::class,
    );
  }
}

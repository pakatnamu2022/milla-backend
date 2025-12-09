<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemCategoryRequest;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemCategoryService;
use Illuminate\Http\Request;
use Throwable;

class PerDiemCategoryController extends Controller
{
  protected PerDiemCategoryService $service;

  public function __construct(PerDiemCategoryService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of all categories
   */
  public function index(IndexPerDiemCategoryRequest $request)
  {
    try {
      return $this->service->index($request);
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display active categories only
   */
  public function active(Request $request)
  {
    try {
      return $this->service->active($request);
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}

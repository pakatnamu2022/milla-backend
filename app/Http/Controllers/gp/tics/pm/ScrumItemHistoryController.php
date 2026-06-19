<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumItemHistoryRequest;
use App\Http\Services\gp\tics\pm\ScrumItemHistoryService;
use Illuminate\Http\JsonResponse;

class ScrumItemHistoryController extends Controller
{
  public function __construct(private ScrumItemHistoryService $service) {}

  public function index(IndexScrumItemHistoryRequest $request): JsonResponse
  {
    return response()->json($this->service->list($request));
  }
}

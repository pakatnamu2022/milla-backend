<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumItemHistoryRequest;
use App\Http\Services\gp\tics\pm\ScrumItemHistoryService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScrumItemHistoryController extends Controller
{
  public function __construct(private ScrumItemHistoryService $service) {}

  public function index(IndexScrumItemHistoryRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}

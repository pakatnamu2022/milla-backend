<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\StoreAssetRequest;
use App\Http\Services\ap\comercial\AssetService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AssetController extends Controller
{
  use HasApiResponse;

  public function __construct(protected AssetService $service) {}

  public function index(Request $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function eligibleVehicles(Request $request): JsonResponse
  {
    try {
      return $this->success($this->service->eligibleVehicles($request));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreAssetRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->errorValidation($th->getMessage());
    }
  }

  public function dispatchMigration(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->dispatchMigration($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}

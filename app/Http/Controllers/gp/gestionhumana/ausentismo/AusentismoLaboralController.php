<?php

namespace App\Http\Controllers\gp\gestionhumana\ausentismo;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\ausentismo\StoreAusentismoLaboralRequest;
use App\Http\Requests\gp\gestionhumana\ausentismo\UpdateAusentismoLaboralRequest;
use App\Http\Services\gp\gestionhumana\ausentismo\AusentismoLaboralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AusentismoLaboralController extends Controller
{
  public function __construct(protected AusentismoLaboralService $service) {}

  public function index(Request $request): JsonResponse
  {
    return $this->service->list($request);
  }

  public function show(int $id): JsonResponse
  {
    return $this->service->show($id);
  }

  public function store(StoreAusentismoLaboralRequest $request): JsonResponse
  {
    return $this->service->store($request);
  }

  public function update(UpdateAusentismoLaboralRequest $request, int $id): JsonResponse
  {
    return $this->service->update($request, $id);
  }

  public function destroy(int $id): JsonResponse
  {
    return $this->service->destroy($id);
  }
}

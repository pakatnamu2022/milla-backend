<?php

namespace App\Http\Controllers\gp\gestionhumana\contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\contratos\IndexSignerRequest;
use App\Http\Requests\gp\gestionhumana\contratos\StoreSignerRequest;
use App\Http\Requests\gp\gestionhumana\contratos\UpdateSignerRequest;
use App\Http\Services\gp\gestionhumana\contratos\SignerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignerController extends Controller
{
  public function __construct(protected SignerService $service) {}

  public function index(IndexSignerRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function store(StoreSignerRequest $request): JsonResponse
  {
    try {
      $data = $request->safe()->except(['file', 'key', 'firmaimg']);
      $files = [
        'file'     => $request->file('file'),
        'key'      => $request->file('key'),
        'firmaimg' => $request->file('firmaimg'),
      ];

      return $this->success($this->service->store($data, $files), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateSignerRequest $request, int $id): JsonResponse
  {
    try {
      $data = $request->safe()->except(['file', 'key', 'firmaimg']);
      $files = [
        'file'     => $request->file('file'),
        'key'      => $request->file('key'),
        'firmaimg' => $request->file('firmaimg'),
      ];

      return $this->success($this->service->update($id, $data, $files));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Firmante anulado.']);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}

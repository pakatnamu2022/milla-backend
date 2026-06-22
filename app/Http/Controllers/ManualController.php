<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexManualRequest;
use App\Http\Requests\StoreManualRequest;
use App\Http\Requests\UpdateManualRequest;
use App\Http\Services\ManualService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ManualController extends Controller
{
    public function __construct(private ManualService $service) {}

    public function index(IndexManualRequest $request): JsonResponse
    {
        try {
            return $this->service->list($request);
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

    public function store(StoreManualRequest $request): JsonResponse
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateManualRequest $request, int $id): JsonResponse
    {
        try {
            $data       = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->destroy($id);
            return $this->success(['message' => 'Manual eliminado correctamente.']);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}

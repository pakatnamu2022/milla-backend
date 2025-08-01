<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAccessRequest;
use App\Http\Requests\StoreAccessRequest;
use App\Http\Requests\StoreManyPermissionsRequest;
use App\Http\Requests\UpdateAccessRequest;
use App\Http\Services\gp\gestionsistema\AccessService;

class AccessController extends Controller
{
    protected AccessService $service;

    public function __construct(AccessService $service)
    {
        $this->service = $service;
    }

    public function index(IndexAccessRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreAccessRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function storeMany(StoreManyPermissionsRequest $request, $roleId)
    {
        try {
            return $this->success($this->service->storeMany([
                'role_id' => $roleId,
                'accesses' => $request->validated(),
            ]));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateAccessRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            return $this->service->destroy($id);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}

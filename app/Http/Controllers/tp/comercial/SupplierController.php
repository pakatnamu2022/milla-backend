<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreSupplierRequest;
use App\Http\Requests\tp\comercial\UpdateSupplierRequest;
use App\Http\Services\tp\comercial\SupplierService;
use Illuminate\Http\Request;
use Throwable;

class SupplierController extends Controller
{
    protected SupplierService $service;

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            return response()->json($this->service->list($request));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreSupplierRequest $request)
    {
        try {
            $data = $request->validated();

            return response()->json($this->service->store($data), 201);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateSupplierRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;

            return response()->json($this->service->update($data));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            return response()->json($this->service->destroy($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function active()
    {
        try {
            return response()->json($this->service->getActiveSuppliers());
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}

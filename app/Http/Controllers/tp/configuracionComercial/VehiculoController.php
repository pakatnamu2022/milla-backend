<?php

namespace App\Http\Controllers\tp\configuracionComercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\configuracionComercial\StoreVehiculoRequest;
use App\Http\Requests\tp\configuracionComercial\UpdateVehiculoRequest;
use App\Http\Services\tp\configuracionComercial\VehiculoService;
use Illuminate\Http\Request;
use Throwable;

class VehiculoController extends Controller
{
    protected VehiculoService $service;

    public function __construct(VehiculoService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar vehículos
     */
    public function index(Request $request)
    {
        try {
            return response()->json($this->service->list($request));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Obtener datos para formularios
     */
    public function getFormData()
    {
        try {
            return response()->json($this->service->getFormData());
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Almacenar un nuevo vehículo
     */
    public function store(StoreVehiculoRequest $request)
    {
        try {
            $data = $request->validated();
            return response()->json($this->service->store($data), 201);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Mostrar un vehículo específico
     */
    public function show($id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Actualizar un vehículo
     */
    public function update(UpdateVehiculoRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return response()->json($this->service->update($data));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Eliminar un vehículo
     */
    public function destroy($id)
    {
        try {
            return response()->json($this->service->destroy($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Cambiar estado del vehículo
     */
    public function changeStatus(Request $request, $id)
    {
        try {
            $status = $request->input('status');
            return response()->json($this->service->changeStatus($id, $status));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
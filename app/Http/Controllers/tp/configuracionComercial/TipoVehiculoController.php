<?php

namespace App\Http\Controllers\tp\configuracionComercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\UpdateTipoVehiculoRequest;
use App\Http\Requests\tp\configuracionComercial\StoreTipoVehiculoRequest;
use App\Http\Services\tp\configuracionComercial\TipoVehiculoService;
use Illuminate\Http\Request;
use Throwable;

class TipoVehiculoController extends Controller
{
    protected TipoVehiculoService $service;

    public function __construct(TipoVehiculoService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar tipos de vehículo
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
     * Almacenar un nuevo tipo de vehículo
     */
    public function store(StoreTipoVehiculoRequest $request)
    {
        try {
            $data = $request->validated();
            return response()->json($this->service->store($data), 201);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Mostrar un tipo de vehículo específico
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
     * Actualizar un tipo de vehículo
     */
    public function update(UpdateTipoVehiculoRequest $request, $id)
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
     * Eliminar un tipo de vehículo
     */
    public function destroy($id)
    {
        try {
            return response()->json($this->service->destroy($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
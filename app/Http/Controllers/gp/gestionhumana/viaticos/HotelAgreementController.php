<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexHotelAgreementRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreHotelAgreementRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdateHotelAgreementRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelAgreementResource;
use App\Services\gp\gestionhumana\viaticos\HotelAgreementService;
use Illuminate\Http\Request;
use Throwable;

class HotelAgreementController extends Controller
{
    protected HotelAgreementService $service;

    public function __construct(HotelAgreementService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of all hotel agreements
     */
    public function index(IndexHotelAgreementRequest $request)
    {
        try {
            return $this->service->index($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display active hotel agreements only
     */
    public function active(Request $request)
    {
        try {
            return $this->service->active($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Store a newly created hotel agreement
     */
    public function store(StoreHotelAgreementRequest $request)
    {
        try {
            $agreement = $this->service->store($request->validated());
            return $this->success([
                'data' => new HotelAgreementResource($agreement),
                'message' => 'Convenio hotelero creado exitosamente'
            ]);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display the specified hotel agreement
     */
    public function show(int $id)
    {
        try {
            $agreement = $this->service->show($id);
            if (!$agreement) {
                return $this->error('Convenio hotelero no encontrado');
            }
            return $this->success(new HotelAgreementResource($agreement));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Update the specified hotel agreement
     */
    public function update(UpdateHotelAgreementRequest $request, int $id)
    {
        try {
            $agreement = $this->service->update($id, $request->validated());
            return $this->success([
                'data' => new HotelAgreementResource($agreement),
                'message' => 'Convenio hotelero actualizado exitosamente'
            ]);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Remove the specified hotel agreement
     */
    public function destroy(int $id)
    {
        try {
            $this->service->destroy($id);
            return $this->success(['message' => 'Convenio hotelero eliminado exitosamente']);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}

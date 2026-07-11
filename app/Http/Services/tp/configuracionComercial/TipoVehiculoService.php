<?php

namespace App\Http\Services\tp\configuracionComercial;

use App\Http\Resources\tp\configuracionComercial\TipoVehiculoResource;
use App\Http\Services\BaseService;
use App\Models\tp\configuracionComercial\vehiculo\TipoVehiculo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TipoVehiculoService extends BaseService
{
    /**
     * Listar tipos de vehículo con filtros
     */
    public function list(Request $request)
    {
        try {
            $query = TipoVehiculo::active();

            // Filtro por estado
            if ($request->has('status_deleted') && $request->input('status_deleted') !== 'all') {
                $statusId = $request->input('status_deleted');
                if (in_array($statusId, ['0', '1'])) {
                    $query->where('status_deleted', $statusId);
                }
            }

            // Filtro de búsqueda
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->search($searchTerm);
            }

            // Ordenamiento
            $query->orderBy('id', 'desc');

            // Paginación
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => TipoVehiculoResource::collection($paginated->items()),
                'links' => [
                    'first' => $paginated->url(1),
                    'last' => $paginated->url($paginated->lastPage()),
                    'prev' => $paginated->previousPageUrl(),
                    'next' => $paginated->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                ]
            ];

        } catch (Throwable $th) {
            Log::error('Error en TipoVehiculoService::list:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all()
            ]);
            throw new Exception("Error al listar tipos de vehículo: " . $th->getMessage());
        }
    }

    /**
     * Obtener datos para formularios
     */
    public function getFormData()
    {
        try {
            return [
                'vehicleTypes' => TipoVehiculo::active()
                    ->select('id', 'descripcion')
                    ->orderBy('descripcion')
                    ->get(),
            ];
        } catch (Throwable $th) {
            Log::error('Error en TipoVehiculoService::getFormData:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            throw new Exception("Error al obtener datos del formulario: " . $th->getMessage());
        }
    }

    /**
     * Crear un nuevo tipo de vehículo
     */
    public function store($data)
    {
        DB::beginTransaction();

        try {
            $tipoVehiculo = TipoVehiculo::create([
                'descripcion' => $data['descripcion'],
                'write_id' => Auth::id(),
                'status_deleted' => 1,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new TipoVehiculoResource($tipoVehiculo),
                'message' => 'Tipo de vehículo creado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en TipoVehiculoService::store:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'data' => $data
            ]);
            throw new Exception("Error al guardar el tipo de vehículo: " . $th->getMessage());
        }
    }

    /**
     * Mostrar un tipo de vehículo específico
     */
    public function show($id)
    {
        try {
            $tipoVehiculo = TipoVehiculo::active()->findOrFail($id);
            return new TipoVehiculoResource($tipoVehiculo);
        } catch (Throwable $th) {
            Log::error('Error en TipoVehiculoService::show:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'id' => $id
            ]);
            throw new Exception("Error al obtener el tipo de vehículo: " . $th->getMessage());
        }
    }

    /**
     * Actualizar un tipo de vehículo
     */
    public function update($data)
    {
        DB::beginTransaction();

        try {
            $tipoVehiculo = TipoVehiculo::active()->findOrFail($data['id']);

            $tipoVehiculo->update([
                'descripcion' => $data['descripcion'],
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new TipoVehiculoResource($tipoVehiculo->fresh()),
                'message' => 'Tipo de vehículo actualizado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en TipoVehiculoService::update:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'data' => $data
            ]);
            throw new Exception("Error al actualizar el tipo de vehículo: " . $th->getMessage());
        }
    }

    /**
     * Eliminar (soft delete) un tipo de vehículo
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $tipoVehiculo = TipoVehiculo::active()->findOrFail($id);
            $tipoVehiculo->update(['status_deleted' => 0]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tipo de vehículo eliminado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en TipoVehiculoService::destroy:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'id' => $id
            ]);
            throw new Exception("Error al eliminar el tipo de vehículo: " . $th->getMessage());
        }
    }
}
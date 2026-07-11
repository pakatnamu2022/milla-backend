<?php

namespace App\Http\Services\tp\configuracionComercial;

use App\Http\Resources\tp\configuracionComercial\VehiculoResource;
use App\Http\Services\BaseService;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\tp\configuracionComercial\vehiculo\TipoVehiculo;
use App\Models\tp\configuracionComercial\vehiculo\Vehiculo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class VehiculoService extends BaseService
{
    /**
     * Listar vehículos con filtros
     */
    public function list(Request $request)
    {
        try {
            $query = Vehiculo::with(['tipo_vehiculo', 'sede'])->active();

            // Filtro por estado
            if ($request->has('status_deleted') && $request->input('status_deleted') !== 'all') {
                $statusId = $request->input('status_deleted');
                if (in_array($statusId, ['0', '1'])) {
                    $query->where('status_deleted', $statusId);
                }
            }

            // Filtro por estado del vehículo
            if ($request->has('vehiculo_status') && $request->input('vehiculo_status') !== 'all') {
                $status = $request->input('vehiculo_status');
                if (in_array($status, ['0', '1'])) {
                    $query->where('vehiculo_status', $status);
                }
            }

            // Filtro por tipo de vehículo
            if ($request->has('tipo_vehiculo_id') && !empty($request->input('tipo_vehiculo_id'))) {
                $query->where('tipo_vehiculo_id', $request->input('tipo_vehiculo_id'));
            }

            // Filtro por sede
            if ($request->has('sede_id') && !empty($request->input('sede_id'))) {
                $query->where('sede_id', $request->input('sede_id'));
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
                'data' => VehiculoResource::collection($paginated->items()),
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
            throw new Exception("Error al listar vehículos: " . $th->getMessage());
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
                'sedes' => Sede::where('status_deleted', 1)
                    ->select('id', 'abreviatura')
                    ->orderBy('abreviatura')
                    ->get(),
            ];
        } catch (Throwable $th) {
            throw new Exception("Error al obtener datos del formulario: " . $th->getMessage());
        }
    }

    /**
     * Crear un nuevo vehículo
     */
    public function store($data)
    {
        DB::beginTransaction();

        try {
            $vehiculo = Vehiculo::create([
                'tipo_vehiculo_id' => $data['tipo_vehiculo_id'],
                'placa' => strtoupper($data['placa']),
                'modelo' => $data['modelo'] ?? null,
                'marca' => $data['marca'] ?? null,
                'serie_chasis' => $data['serie_chasis'] ?? null,
                'motor' => $data['motor'] ?? null,
                'num_mtc' => $data['num_mtc'] ?? null,
                'tarjeta_circulacion' => $data['tarjeta_circulacion'] ?? null,
                'kilometraje' => $data['kilometraje'] ?? 0,
                'tercero' => $data['tercero'] ?? 0,
                'capacidad' => $data['capacidad'] ?? null,
                'capacidad_bruta' => $data['capacidad_bruta'] ?? null,
                'reserva' => $data['reserva'] ?? null,
                'capacidad_util' => $data['capacidad_util'] ?? null,
                'vehiculo_status' => $data['vehiculo_status'] ?? 1,
                'status_geotab_km' => $data['status_geotab_km'] ?? 0,
                'status_matpel' => $data['status_matpel'] ?? 1,
                'status_ubicacion' => $data['status_ubicacion'] ?? 1,
                'sede_id' => $data['sede_id'],
                'write_id' => Auth::id(),
                'status_deleted' => 1,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new VehiculoResource($vehiculo),
                'message' => 'Vehículo creado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en VehiculoService::store:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'data' => $data
            ]);
            throw new Exception("Error al guardar el vehículo: " . $th->getMessage());
        }
    }

    /**
     * Mostrar un vehículo específico
     */
    public function show($id)
    {
        try {
            $vehiculo = Vehiculo::with(['tipo_vehiculo', 'sede', 'creator'])
                ->active()
                ->findOrFail($id);
            return new VehiculoResource($vehiculo);
        } catch (Throwable $th) {
            Log::error('Error en VehiculoService::show:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'id' => $id
            ]);
            throw new Exception("Error al obtener el vehículo: " . $th->getMessage());
        }
    }

    /**
     * Actualizar un vehículo
     */
    public function update($data)
    {
        DB::beginTransaction();

        try {
            $vehiculo = Vehiculo::active()->findOrFail($data['id']);

            $vehiculo->update([
                'tipo_vehiculo_id' => $data['tipo_vehiculo_id'],
                'placa' => strtoupper($data['placa']),
                'modelo' => $data['modelo'] ?? null,
                'marca' => $data['marca'] ?? null,
                'serie_chasis' => $data['serie_chasis'] ?? null,
                'motor' => $data['motor'] ?? null,
                'num_mtc' => $data['num_mtc'] ?? null,
                'tarjeta_circulacion' => $data['tarjeta_circulacion'] ?? null,
                'kilometraje' => $data['kilometraje'] ?? 0,
                'tercero' => $data['tercero'] ?? 0,
                'capacidad' => $data['capacidad'] ?? null,
                'capacidad_bruta' => $data['capacidad_bruta'] ?? null,
                'reserva' => $data['reserva'] ?? null,
                'capacidad_util' => $data['capacidad_util'] ?? null,
                'vehiculo_status' => $data['vehiculo_status'] ?? 1,
                'status_geotab_km' => $data['status_geotab_km'] ?? 0,
                'status_matpel' => $data['status_matpel'] ?? 1,
                'status_ubicacion' => $data['status_ubicacion'] ?? 1,
                'sede_id' => $data['sede_id'],
                'ult_manteniento_realizado' => $data['ult_manteniento_realizado'] ?? null,
                'km_mantenimiento' => $data['km_mantenimiento'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new VehiculoResource($vehiculo->fresh()),
                'message' => 'Vehículo actualizado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en VehiculoService::update:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'data' => $data
            ]);
            throw new Exception("Error al actualizar el vehículo: " . $th->getMessage());
        }
    }

    /**
     * Eliminar (soft delete) un vehículo
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $vehiculo = Vehiculo::active()->findOrFail($id);
            $vehiculo->update(['status_deleted' => 0]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Vehículo eliminado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en VehiculoService::destroy:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'id' => $id
            ]);
            throw new Exception("Error al eliminar el vehículo: " . $th->getMessage());
        }
    }

    /**
     * Cambiar estado del vehículo
     */
    public function changeStatus($id, $status)
    {
        DB::beginTransaction();

        try {
            $vehiculo = Vehiculo::active()->findOrFail($id);
            $vehiculo->update(['vehiculo_status' => $status]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Estado del vehículo actualizado exitosamente'
            ];

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en VehiculoService::changeStatus:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'id' => $id,
                'status' => $status
            ]);
            throw new Exception("Error al cambiar el estado del vehículo: " . $th->getMessage());
        }
    }
}
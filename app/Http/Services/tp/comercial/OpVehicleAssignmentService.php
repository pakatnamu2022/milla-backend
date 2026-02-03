<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\OpVehicleAssignmentResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\OpVehicleAssignment;
use App\Models\tp\comercial\Vehicle;
use App\Models\tp\Driver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class OpVehicleAssignmentService extends BaseService
{
    public function list(Request $request)
    {
        $query = OpVehicleAssignment::query();

        if ($request->has('status_id')) {
            $statusId = $request->input('status_id');

            switch ($statusId) {
                case '0':
                    $query->where('status_deleted', 0);
                    break;
                case '1':
                    $query->where('status_deleted', 1);
                    break;
                case 'all':
                    break;
                default:
                    $query->where('status_deleted', 1);
            }
        } else {
            $query->where('status_deleted', 1);
        }

        $response = $this->getFilteredResults(
            $query,
            $request,
            OpVehicleAssignment::filters,
            OpVehicleAssignment::sorts,
            OpVehicleAssignmentResource::class
        );

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            return response()->json($data);
        }

        return $response;
    }

    public function getFormData()
    {
        return [
            'drivers' => Driver::select('id', 'nombre_completo', 'vat')
                ->orderBy('nombre_completo')
                ->limit(50)
                ->get(),
            'tractors' => Vehicle::where('status_deleted', "1")
                ->get(['id', 'placa']),

        ];
    }

    public function searchDrivers(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);

            $query = Driver::select('id', 'nombre_completo', 'vat')
                ->orderBy('nombre_completo');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('vat', 'like', "%{$search}%");
                });
            }

            $drivers = $query->limit($limit)->get();

            return [
                'success' => true,
                'data' => $drivers,
                'total' => Driver::count()
            ];
        } catch (Throwable $th) {
            Log::error("Error searching drivers: " . $th->getMessage());
            throw new Exception("Error al buscar los conductores: " . $th->getMessage());
        }
    }

    public function store($data)
    {
        DB::beginTransaction();

        try {
            $opVehicleAssignment = OpVehicleAssignment::create([
                'tracto_id' => $data['vehicle'],
                'conductor_id' => $data['driver'],
                'write_id' => auth()->id(),
                'status_deleted' => 1,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new OpVehicleAssignmentResource($opVehicleAssignment),
                'message' => 'Asignacion de Vehiculo creado exitosamente'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en el servicio de asignacion de vehiculo: " . $th->getMessage());
            throw new Exception("Error al guardar la asignacion de vehiculo: " . $th->getMessage());
        }
    }

    public function show($id)
    {
        $opVehicleAssignment = OpVehicleAssignment::with(['driver', 'tractor'])
            ->where('status_deleted', 1)
            ->findOrFail($id);
        return new OpVehicleAssignmentResource($opVehicleAssignment);
    }

    public function update($data)
    {
        DB::beginTransaction();

        try {

            $opVehicleAssignment = OpVehicleAssignment::where('status_deleted', 1)
                ->findOrFail($data['id']);
            if (!$opVehicleAssignment) {
                throw new Exception('Asignacion de vehiculo no encontrado');
            }

            $opVehicleAssignment->update([
                'tracto_id' => $data['vehicle'],
                'conductor_id' => $data['driver'],
                'write_id' => auth()->id(),
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => new OpVehicleAssignmentResource($opVehicleAssignment->fresh()),
                'message' => 'Asignacion de Vehiculo creado exitosamente'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en el servicio de asignacion de vehiculos: " . $th->getMessage());
            throw new Exception("Error al actualizar la asignacion de vehiculos: " . $th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $opVehicleAssignment = OpVehicleAssignment::where('status_deleted', 1)
                ->findOrFail($id);
            $opVehicleAssignment->update(['status_deleted' => 0]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Asignacion de Vehiculo eliminado exitosamente'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en el servicio de asignacion de vehiculos: " . $th->getMessage());
            throw new Exception("Error al eliminar la asignacion de vehiculos: " . $th->getMessage());
        }
    }
}

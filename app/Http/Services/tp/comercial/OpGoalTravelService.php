<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\OpGoalTravelResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\OpGoalTravel;
use App\Models\tp\comercial\Vehicle;
use App\Models\tp\Driver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpGoalTravelService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredGoalTravel($request);
    }

    private function getFilteredGoalTravel($request)
    {
        try{

            $query = OpGoalTravel::where('status_deleted', 1);

            if($request->has('status_id') && $request->input('status_id') !== 'all'){
                $statusId = $request->input('status_id');

                if($statusId === '1' || $statusId === '0'){
                    $query->where('status_deleted', $statusId);
                }
            }

            if($request->has('search') && !empty($request->input('search'))){
                $searchTerm = $request->input('search');

                $query->where(function($q) use ($searchTerm){
                    $q->where('meta_conductor', 'like', "%{$searchTerm}%")
                    ->orWhere('meta_vehiculo', 'like' , "%{$searchTerm}%")
                    ->orWhere('total', 'like', "%{$searchTerm}%");
                });

            }

            $query->orderBy('id', 'desc');
            $query->orderBy('fecha', 'desc');

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => OpGoalTravelResource::collection($paginated->items()),
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


        }catch(Throwable $th){
            Log::error('Error in getFilteredGoalTravel:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all()
            ]);
            throw new Exception("Error al filtrar meta de viajes: " . $th->getMessage());
        }
    }


    public function store($data){
        DB::beginTransaction();
        try{
            $driverCount = Driver::where('sede_id', 1)->count();
            $vehicleCount = Vehicle::where('tercero', 0)
                              ->where('sede_id', 1)
                              ->where('tipo_vehiculo_id', 1)
                              ->where('vehiculo_status', 1)
                              ->where('status_deleted', 1)
                              ->count();

            if($driverCount === 0 || $vehicleCount === 0)
            {
                throw new Exception("No hay conductores o vehículos activos para calcular las metas.");
            }

            $goalTravel = OpGoalTravel::create([
                'fecha' => $data['date'],
                'total' => $data['total'],
                'meta_conductor' => $data['total'] / $driverCount,
                'meta_vehiculo' => $data['total'] / $vehicleCount ,
                'total_unidades' => $vehicleCount ,
                'status_deleted' => 1,
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => new OpGoalTravelResource($goalTravel),
                'message' => 'Meta creada exitosamente'
            ];

        }catch(Throwable $th){
            DB::rollBack();
            Log::error("Error en el servicio de metas de viajes" . $th->getMessage());
            throw $th;
        }
    }

    public function show($id)
    {
        $goalTravel = OpGoalTravel::where('status_deleted', 1)
                                    ->findOrFail($id);
        return new OpGoalTravelResource($goalTravel);
    }

    public function update($data)
    {
        DB::beginTransaction();
        try{
            $goalTravel = OpGoalTravel::where('status_deleted', 1)
                                      ->findOrFail($data['id']);
            
            if(!$goalTravel){
                throw new Exception('Meta no encontrado');
            }
            $driverCount = Driver::where('sede_id', 1)->count();
            $vehicleCount = Vehicle::where('tercero', 0)
                              ->where('sede_id', 1)
                              ->where('tipo_vehiculo_id', 1)
                              ->where('vehiculo_status', 1)
                              ->where('status_deleted', 1)
                              ->count();

            if($driverCount === 0 || $vehicleCount === 0)
            {
                throw new Exception("No hay conductores o vehículos activos para calcular las metas.");
            }

            $goalTravel->update([
                'fecha' => $data['date'],
                'total' => $data['total'],
                'meta_conductor' => $data['total'] / $driverCount,
                'meta_vehiculo' => $data['total'] / $vehicleCount ,
                'total_unidades' => $vehicleCount ,
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => new OpGoalTravelResource($goalTravel->fresh()),
                'message' => 'Meta actualizada exitosamente'
            ];

        } catch(Throwable $th){
            DB::rollBack();
            Log::error("Error en el servicio de meta de viajes: ".$th->getMessage());
            throw $th;
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $freight = OpGoalTravel::where('status_deleted', 1)
                ->findOrFail($id);

            $freight->update(['status_deleted' => 0]);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Meta eliminado exitosamente'
            ];
        }catch(Throwable $th){
            DB::rollBack();
            Log::error("Error en el servicio de meta: ".$th->getMessage());
            throw new Exception("Error al eliminar el meta: ".$th->getMessage());
        }
    }

}
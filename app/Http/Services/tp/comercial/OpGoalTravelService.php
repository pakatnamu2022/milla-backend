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
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class OpGoalTravelService extends BaseService
{
    public function list(Request $request)
    {
        $query = OpGoalTravel::query();
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

        $availableYears = $this->getAvailableYears();

        $response = $this->getFilteredResults(
            $query,
            $request,
            OpGoalTravel::filters,
            OpGoalTravel::sorts,
            OpGoalTravelResource::class
        );

        if($response instanceof JsonResponse){
            $data = $response->getData(true);


            $data['available_years'] = $availableYears;

            return response()->json($data);
        }

        return $response;
    }
    private function getAvailableYears()
    {
        try {

            $years = OpGoalTravel::where('status_deleted', 1)
                    ->get()
                    ->map(function($item){
                        if($item->fecha && !is_null($item->fecha)){
                            try{

                                return $item->fecha->year;

                            }catch(Exception $e){
                                return null;
                            }
                        }

                        return null;
                    })
                    ->filter()
                    ->unique()
                    ->sortDesc()
                    ->values()
                    ->toArray();

        return $years;
        
        } catch (\Exception $e) {
            Log::error('Error getting available years: ' . $e->getMessage());
            return [];
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
<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\OpFreightResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\FacCitySales;
use Illuminate\Http\Request;
use App\Models\tp\comercial\OpFreight;
use App\Models\tp\Customer;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpFreightService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredFreight($request);
    }

    private function getFilteredFreight($request)
    {
        try {
            $query = OpFreight::with(['customer', 'startPoint', 'endPoint'])
                        ->where('status_deleted', 1);

            if($request->has('status_id') && $request->input('status_id') !== 'all'){
                $statusId = $request->input('status_id');
                
                if($statusId === '1' || $statusId === '0'){
                    $query->where('status_deleted', $statusId);
                }
            
            
            }
                        

            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                
                $query->where(function($q) use ($searchTerm) {
                    $q->where('flete', 'like', "%{$searchTerm}%")
                    ->orWhere('tipo_flete', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function($customerQuery) use ($searchTerm) {
                        $customerQuery->where('nombre_completo', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('startPoint', function($cityQuery) use ($searchTerm) {
                        $cityQuery->where('descripcion', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('endPoint', function($cityQuery) use ($searchTerm) {
                        $cityQuery->where('descripcion', 'like', "%{$searchTerm}%");
                    });
                });
            }
            $query->orderBy('id', 'desc');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            $paginated = $query->paginate($perPage, ['*'], 'page', $page);
            return [
                'data' => OpFreightResource::collection($paginated->items()),
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
            Log::error('Error in getFilteredFreight:', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all()
            ]);
            throw new Exception("Error al filtrar fletes: " . $th->getMessage());
        }
    }

    public function getFormData()
    {
        return [
            'customers' => Customer::select('id', 'nombre_completo', 'vat')
                        ->orderBy('nombre_completo')
                        ->limit(50)
                        ->get(),
            'cities' => FacCitySales::where("status_deleted", "1")
                        ->get(['id', 'descripcion']),
        ];
    }

    public function searchCustomers(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);
            
            $query = Customer::select('id', 'nombre_completo', 'vat')
                ->orderBy('nombre_completo');
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre_completo', 'like', "%{$search}%")
                      ->orWhere('vat', 'like', "%{$search}%");
                });
            }
            
            $customers = $query->limit($limit)->get();
            
            return [
                'success' => true,
                'data' => $customers,
                'total' => Customer::count() // Información adicional
            ];
            
        }catch(Throwable $th){
            Log::error("Error searching customers: " . $th->getMessage());
            throw new Exception("Error al buscar clientes: " . $th->getMessage());

        }
    }

    public function store($data){

        DB::beginTransaction();

        try{
            $freight = OpFreight::create([
                'cliente_id' => $data['customer'],
                'idorigen' => $data['startPoint'],
                'iddestino' => $data['endPoint'],
                'tipo_flete' => $data['tipo_flete'],
                'flete' => $data['freight'],
                'status_deleted' => 1,
            ]);

            DB::commit();
            return[
                'success' => true,
                'data' => new OpFreightResource($freight),
                'message' => 'Flete creado exitosamente'
            ];
        }catch(Throwable $th){
            DB::rollBack();
            Log::error("Error en el servicio de fletes: ".$th->getMessage());
            throw new Exception("Error al guardar el flete: ".$th->getMessage());
        }
       
    }

    public function show($id){
        $freight = OpFreight::with(['customer', 'startPoint', 'endPoint'])
                ->where('status_deleted', 1)
                ->findOrFail($id);
        return new OpFreightResource($freight);
    }

    public function update($data)
    {
        DB::beginTransaction();
        try {
            $freight = OpFreight::where('status_deleted', 1)
                ->findOrFail($data['id']);

            if(!$freight){
                throw new Exception('Flete no encontrado');
            }

            Log::info("valor de customer", [
                'id' => $data['id']
            ]);

            $freight->update([
                'cliente_id' => $data['customer'],
                'idorigen' => $data['startPoint'],
                'iddestino' => $data['endPoint'],
                'tipo_flete' => $data['tipo_flete'],
                'flete' => $data['freight'],
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => new OpFreightResource($freight->fresh()),
                'message' => 'Flete actualizado exitosamente'
            ];
        } catch(Throwable $th){
            DB::rollBack();
            Log::error("Error en el servicio de fletes: ".$th->getMessage());
            throw new Exception("Error al actualizar el flete: ".$th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $freight = OpFreight::where('status_deleted', 1)
                ->findOrFail($id);

            $freight->update(['status_deleted' => 0]);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Flete eliminado exitosamente'
            ];
        }catch(Throwable $th){
            DB::rollBack();
            Log::error("Error en el servicio de fletes: ".$th->getMessage());
            throw new Exception("Error al eliminar el flete: ".$th->getMessage());
        }
    }
}
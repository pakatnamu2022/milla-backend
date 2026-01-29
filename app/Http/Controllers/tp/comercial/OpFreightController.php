<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreOpFreightRequest;
use App\Http\Requests\tp\comercial\UpdateOpFreightRequest;
use App\Http\Services\tp\comercial\OpFreightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpFreightController extends Controller
{
   
    protected OpFreightService $service;

    public function __construct(OpFreightService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try{
            return response()->json($this->service->list($request));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    public function getFormData()
    {
        try{
            return response()->json($this->service->getFormData());

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    public function searchCustomers(Request $request)
    {
         try {
            return response()->json($this->service->searchCustomers($request));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOpFreightRequest $request)
    {
        try{
            $data = $request->validated();
            return response()->json($this->service->store($data), 201);
        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try{
            return response()->json($this->service->show($id));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateOpFreightRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;
            Log::info("valor de data", $data);
            return response()->json($this->service->update($data));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try{
            return response()->json($this->service->destroy($id));
        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }
}

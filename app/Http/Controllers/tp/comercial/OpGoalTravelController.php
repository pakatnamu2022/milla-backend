<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreOpGoalTravelRequest;
use App\Http\Requests\tp\comercial\UpdateOpGoalTravelRequest;
use App\Http\Services\tp\comercial\OpGoalTravelService;
use App\Models\tp\comercial\OpGoalTravel;
use Illuminate\Http\Request;
use Throwable;

class OpGoalTravelController extends Controller
{
    protected OpGoalTravelService $service;

    public function __construct(OpGoalTravelService $service)
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
    public function store(StoreOpGoalTravelRequest $request)
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OpGoalTravel $opGoalTravel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOpGoalTravelRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;
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

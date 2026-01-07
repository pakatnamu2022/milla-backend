<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreTravelPhotoRequest;
use App\Http\Requests\tp\comercial\UpdateTpTravelPhotoRequest;
use App\Http\Services\tp\comercial\TpTravelPhotoService;
use App\Models\tp\comercial\TpTravelPhoto;
use Illuminate\Http\Request;
use Throwable;

class TpTravelPhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $photoService;


    public function __construct(TpTravelPhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    public function index(Request $request, $id)
    {
       try{
            return response()->json($this->photoService->list($request, $id));

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
    public function store(Request $request, $id)
    {

        try{
            
            return response()->json($this->photoService->store($request, $id), 201);

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
            return response()->json($this->photoService->show($id));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TpTravelPhoto $tpTravelPhoto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTpTravelPhotoRequest $request, $id)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try{
            return response()->json($this->photoService->destroy($id));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }


    public function photoStatistics($id)
    {
        try{
            return response()->json($this->photoService->photoStatistics($id));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }
}

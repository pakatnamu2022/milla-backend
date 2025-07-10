<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sede\StoreSedeRequest;
use App\Http\Requests\Sede\UpdateSedeRequest;
use App\Http\Services\SedeService;
use App\Models\Sede;

class SedeController extends Controller
{

    protected SedeService $service;

    public function __construct(SedeService $service)
    {
        $this->service = $service;
    }


    public function index()
    {
        return $this->service->list(request());
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
    public function store(StoreSedeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Sede $sede)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sede $sede)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSedeRequest $request, Sede $sede)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sede $sede)
    {
        //
    }
}

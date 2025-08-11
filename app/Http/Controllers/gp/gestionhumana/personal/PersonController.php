<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\personal\PersonService;
use App\Models\gp\gestionhumana\personal\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    protected PersonService $service;

    public function __construct(PersonService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        //
    }

    public function birthdays(Request $request)
    {
        try {
            return $this->service->listBirthdays($request);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Person $person)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Person $person)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Person $person)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Person $person)
    {
        //
    }
}

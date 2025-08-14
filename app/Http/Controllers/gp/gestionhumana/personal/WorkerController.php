<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\personal\IndexWorkerRequest;
use App\Http\Requests\gp\gestionhumana\personal\StoreWorkerRequest;
use App\Http\Requests\gp\gestionhumana\personal\UpdateWorkerRequest;
use App\Http\Services\gp\gestionhumana\personal\WorkerService;

class WorkerController extends Controller
{
    protected WorkerService $service;

    public function __construct(WorkerService $service)
    {
        $this->service = $service;
    }


    public function index(IndexWorkerRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StoreWorkerRequest $request)
    {
        //
    }

    public function show(int $id)
    {
        //
    }

    public function update(UpdateWorkerRequest $request, int $id)
    {
        //
    }

    public function destroy(int $id)
    {
        //
    }
}
